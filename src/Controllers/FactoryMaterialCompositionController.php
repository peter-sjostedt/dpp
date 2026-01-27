<?php
namespace App\Controllers;

use App\Config\TenantContext;
use App\Helpers\Response;
use App\Helpers\Validator;

class FactoryMaterialCompositionController extends TenantAwareController {

    /**
     * Check if composition belongs to a material owned by current supplier (for writes)
     */
    private function verifyCompositionOwnership(int|string $compositionId): bool {
        if (!TenantContext::isSupplier()) {
            return false;
        }

        $supplierId = TenantContext::getSupplierId();
        $stmt = $this->db->prepare(
            'SELECT fmc.id FROM factory_material_compositions fmc
             JOIN factory_materials fm ON fmc.factory_material_id = fm.id
             WHERE fmc.id = ? AND fm.supplier_id = ?'
        );
        $stmt->execute([$compositionId, $supplierId]);
        return (bool) $stmt->fetch();
    }

    /**
     * Check if current user can read a composition
     */
    private function canReadComposition(int|string $compositionId): bool {
        $stmt = $this->db->prepare(
            'SELECT factory_material_id FROM factory_material_compositions WHERE id = ?'
        );
        $stmt->execute([$compositionId]);
        $comp = $stmt->fetch();

        if (!$comp) {
            return false;
        }

        return $this->canAccessMaterial($comp['factory_material_id']);
    }

    public function index(array $params): void {
        // Both brands and suppliers can read via canAccessMaterial
        if (!$this->canAccessMaterial($params['materialId'])) {
            Response::error('Factory material not found', 404);
        }

        $stmt = $this->db->prepare(
            'SELECT * FROM factory_material_compositions WHERE factory_material_id = ? ORDER BY percentage DESC'
        );
        $stmt->execute([$params['materialId']]);
        Response::success($stmt->fetchAll());
    }

    public function create(array $params): void {
        // Write operations require supplier authentication
        $this->requireSupplier();

        $data = Validator::getJsonBody();
        Validator::required($data, ['fiber_type', 'percentage']);

        // Only allow creating compositions for OWN materials
        if (!$this->verifyMaterialOwnership($params['materialId'])) {
            Response::error('Factory material not found', 404);
        }

        $stmt = $this->db->prepare(
            'INSERT INTO factory_material_compositions (
                factory_material_id, fiber_type, percentage, fiber_source,
                material_trademark, is_recycled, recycled_percentage, recycled_source
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $params['materialId'],
            $data['fiber_type'],
            $data['percentage'],
            $data['fiber_source'] ?? null,
            $data['material_trademark'] ?? null,
            $data['is_recycled'] ?? false,
            $data['recycled_percentage'] ?? null,
            $data['recycled_source'] ?? null
        ]);

        $id = (int) $this->db->lastInsertId();
        $this->show(['id' => $id]);
    }

    public function show(array $params): void {
        if (!$this->canReadComposition($params['id'])) {
            Response::error('Composition not found', 404);
        }

        $stmt = $this->db->prepare('SELECT * FROM factory_material_compositions WHERE id = ?');
        $stmt->execute([$params['id']]);
        Response::success($stmt->fetch());
    }

    public function update(array $params): void {
        // Write operations require supplier authentication
        $this->requireSupplier();

        $data = Validator::getJsonBody();

        // Only allow updating compositions for OWN materials
        if (!$this->verifyCompositionOwnership($params['id'])) {
            Response::error('Composition not found', 404);
        }

        $stmt = $this->db->prepare(
            'UPDATE factory_material_compositions SET
                fiber_type = COALESCE(?, fiber_type),
                percentage = COALESCE(?, percentage),
                fiber_source = COALESCE(?, fiber_source),
                material_trademark = COALESCE(?, material_trademark),
                is_recycled = COALESCE(?, is_recycled),
                recycled_percentage = COALESCE(?, recycled_percentage),
                recycled_source = COALESCE(?, recycled_source)
            WHERE id = ?'
        );
        $stmt->execute([
            $data['fiber_type'] ?? null,
            $data['percentage'] ?? null,
            $data['fiber_source'] ?? null,
            $data['material_trademark'] ?? null,
            $data['is_recycled'] ?? null,
            $data['recycled_percentage'] ?? null,
            $data['recycled_source'] ?? null,
            $params['id']
        ]);

        $this->show($params);
    }

    public function delete(array $params): void {
        // Write operations require supplier authentication
        $this->requireSupplier();

        // Only allow deleting compositions for OWN materials
        if (!$this->verifyCompositionOwnership($params['id'])) {
            Response::error('Composition not found', 404);
        }

        $stmt = $this->db->prepare('DELETE FROM factory_material_compositions WHERE id = ?');
        $stmt->execute([$params['id']]);

        Response::success(['deleted' => true]);
    }
}
