<?php
namespace App\Controllers;

use App\Config\TenantContext;
use App\Helpers\Response;
use App\Helpers\Validator;

/**
 * Factory Material controller with multi-tenant access control.
 * - As Supplier: Full CRUD on own materials
 * - As Brand: Read-only access to materials from related suppliers
 */
class FactoryMaterialController extends TenantAwareController
{
    public function index(array $params): void
    {
        $supplierId = (int) $params['supplierId'];

        if (TenantContext::isSupplier()) {
            // Supplier can only see their own materials
            if ($supplierId !== TenantContext::getSupplierId()) {
                Response::error('Supplier not found', 404);
                return;
            }
        } else {
            // Brand can see materials from related suppliers
            if (!$this->canAccessSupplier($supplierId)) {
                Response::error('Supplier not found', 404);
                return;
            }
        }

        $stmt = $this->db->prepare(
            'SELECT fm.*, s.supplier_name
             FROM factory_materials fm
             LEFT JOIN suppliers s ON fm.supplier_id = s.id
             WHERE fm.supplier_id = ? AND fm._is_active = TRUE
             ORDER BY fm.created_at DESC'
        );
        $stmt->execute([$supplierId]);
        Response::success($stmt->fetchAll());
    }

    public function indexAll(array $params): void
    {
        if (TenantContext::isSupplier()) {
            // Supplier sees only their own active materials
            $stmt = $this->db->prepare(
                'SELECT fm.*, s.supplier_name
                 FROM factory_materials fm
                 LEFT JOIN suppliers s ON fm.supplier_id = s.id
                 WHERE fm.supplier_id = ? AND fm._is_active = TRUE
                 ORDER BY fm.created_at DESC'
            );
            $stmt->execute([TenantContext::getSupplierId()]);
        } else {
            // Brand sees active materials from related suppliers
            $stmt = $this->db->prepare(
                'SELECT DISTINCT fm.*, s.supplier_name
                 FROM factory_materials fm
                 LEFT JOIN suppliers s ON fm.supplier_id = s.id
                 JOIN brand_suppliers bs ON s.id = bs.supplier_id
                 WHERE bs.brand_id = ? AND bs._is_active = TRUE AND fm._is_active = TRUE
                 ORDER BY s.supplier_name, fm.material_name'
            );
            $stmt->execute([TenantContext::getBrandId()]);
        }

        Response::success($stmt->fetchAll());
    }

    public function show(array $params): void
    {
        $materialId = (int) $params['id'];

        if (!$this->canAccessMaterial($materialId)) {
            Response::error('Material not found', 404);
            return;
        }

        $stmt = $this->db->prepare(
            'SELECT fm.*, s.supplier_name
             FROM factory_materials fm
             LEFT JOIN suppliers s ON fm.supplier_id = s.id
             WHERE fm.id = ?'
        );
        $stmt->execute([$materialId]);
        $material = $stmt->fetch();

        if (!$material) {
            Response::error('Material not found', 404);
            return;
        }

        Response::success($material);
    }

    public function create(array $params): void
    {
        $this->requireSupplier();

        $supplierId = (int) $params['supplierId'];

        // Can only create materials for own supplier
        if ($supplierId !== TenantContext::getSupplierId()) {
            Response::error('Supplier not found', 404);
            return;
        }

        $data = Validator::getJsonBody();

        if ($error = Validator::required($data, ['material_name'])) {
            Response::error($error);
            return;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO factory_materials (
                supplier_id, material_name, material_type, _internal_code,
                net_weight_per_meter, width_cm, _is_active
             ) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $supplierId,
            $data['material_name'],
            $data['material_type'] ?? 'textile',
            $data['_internal_code'] ?? null,
            $data['net_weight_per_meter'] ?? null,
            $data['width_cm'] ?? null,
            $data['_is_active'] ?? true
        ]);

        $id = $this->db->lastInsertId();
        $this->show(['id' => $id]);
    }

    public function update(array $params): void
    {
        $this->requireSupplier();

        $materialId = (int) $params['id'];

        if (!$this->verifyMaterialOwnership($materialId)) {
            Response::error('Material not found', 404);
            return;
        }

        $data = Validator::getJsonBody();

        $stmt = $this->db->prepare(
            'UPDATE factory_materials SET
                material_name = COALESCE(?, material_name),
                material_type = COALESCE(?, material_type),
                _internal_code = COALESCE(?, _internal_code),
                net_weight_per_meter = COALESCE(?, net_weight_per_meter),
                width_cm = COALESCE(?, width_cm),
                _is_active = COALESCE(?, _is_active)
             WHERE id = ?'
        );
        $stmt->execute([
            $data['material_name'] ?? null,
            $data['material_type'] ?? null,
            $data['_internal_code'] ?? null,
            $data['net_weight_per_meter'] ?? null,
            $data['width_cm'] ?? null,
            $data['_is_active'] ?? null,
            $materialId
        ]);

        $this->show(['id' => $materialId]);
    }

    public function delete(array $params): void
    {
        $this->requireSupplier();

        $materialId = (int) $params['id'];

        if (!$this->verifyMaterialOwnership($materialId)) {
            Response::error('Material not found', 404);
            return;
        }

        // Check if material is used in any batches
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM batch_materials WHERE factory_material_id = ?');
        $stmt->execute([$materialId]);
        $usageCount = (int) $stmt->fetchColumn();

        if ($usageCount > 0) {
            // Material is used - soft delete (deactivate)
            $stmt = $this->db->prepare('UPDATE factory_materials SET _is_active = FALSE WHERE id = ?');
            $stmt->execute([$materialId]);
            Response::success(['deactivated' => $materialId, 'reason' => 'Material is used in ' . $usageCount . ' batch(es)']);
        } else {
            // Material is not used - hard delete
            $stmt = $this->db->prepare('DELETE FROM factory_materials WHERE id = ?');
            $stmt->execute([$materialId]);
            Response::success(['deleted' => $materialId]);
        }
    }
}
