<?php
namespace App\Controllers;

use App\Config\TenantContext;
use App\Helpers\Response;
use App\Helpers\Validator;

class BatchMaterialController extends TenantAwareController {

    /**
     * Verify batch material belongs to a batch owned by current brand (for writes)
     */
    private function verifyBatchMaterialOwnershipAsBrand(int|string $batchMaterialId): bool {
        if (!TenantContext::isBrand()) {
            return false;
        }

        $brandId = TenantContext::getBrandId();
        $stmt = $this->db->prepare(
            'SELECT bm.id FROM batch_materials bm
             JOIN batches b ON bm.batch_id = b.id
             WHERE bm.id = ? AND b.brand_id = ?'
        );
        $stmt->execute([$batchMaterialId, $brandId]);
        return (bool) $stmt->fetch();
    }

    /**
     * Check if current user can read this batch
     * Brands: own batches
     * Suppliers: batches containing their materials
     */
    private function canReadBatch(int|string $batchId): bool {
        if (TenantContext::isBrand()) {
            return $this->verifyBatchOwnership($batchId);
        }

        if (TenantContext::isSupplier()) {
            return $this->canAccessBatchAsSupplier($batchId);
        }

        return false;
    }

    /**
     * Check if current user can read a batch material
     */
    private function canReadBatchMaterial(int|string $batchMaterialId): bool {
        // Get batch_id and factory_material_id
        $stmt = $this->db->prepare(
            'SELECT batch_id, factory_material_id FROM batch_materials WHERE id = ?'
        );
        $stmt->execute([$batchMaterialId]);
        $bm = $stmt->fetch();

        if (!$bm) {
            return false;
        }

        if (TenantContext::isBrand()) {
            return $this->verifyBatchOwnership($bm['batch_id']);
        }

        if (TenantContext::isSupplier()) {
            // Suppliers can only see batch materials for their own materials
            return $this->verifyMaterialOwnership($bm['factory_material_id']);
        }

        return false;
    }

    public function index(array $params): void {
        if (!$this->canReadBatch($params['batchId'])) {
            Response::error('Batch not found', 404);
        }

        if (TenantContext::isBrand()) {
            // Brands see all materials in their batch
            $stmt = $this->db->prepare(
                'SELECT bm.*, fm.material_name, fm.material_type, fm.description
                 FROM batch_materials bm
                 JOIN factory_materials fm ON bm.factory_material_id = fm.id
                 WHERE bm.batch_id = ?
                 ORDER BY bm.component'
            );
            $stmt->execute([$params['batchId']]);
        } else {
            // Suppliers only see their own materials in the batch
            $supplierId = TenantContext::getSupplierId();
            $stmt = $this->db->prepare(
                'SELECT bm.*, fm.material_name, fm.material_type, fm.description
                 FROM batch_materials bm
                 JOIN factory_materials fm ON bm.factory_material_id = fm.id
                 WHERE bm.batch_id = ? AND fm.supplier_id = ?
                 ORDER BY bm.component'
            );
            $stmt->execute([$params['batchId'], $supplierId]);
        }

        Response::success($stmt->fetchAll());
    }

    public function create(array $params): void {
        // Write operations require brand authentication
        $this->requireBrand();

        $data = Validator::getJsonBody();
        Validator::required($data, ['factory_material_id']);

        // Verify batch exists and belongs to this brand
        if (!$this->verifyBatchOwnership($params['batchId'])) {
            Response::error('Batch not found', 404);
        }

        // Verify material exists (materials are accessible via supplier relationship)
        $stmt = $this->db->prepare('SELECT id FROM factory_materials WHERE id = ?');
        $stmt->execute([$data['factory_material_id']]);
        if (!$stmt->fetch()) {
            Response::error('Factory material not found', 404);
        }

        $stmt = $this->db->prepare(
            'INSERT INTO batch_materials (
                batch_id, factory_material_id, component
            ) VALUES (?, ?, ?)'
        );
        $stmt->execute([
            $params['batchId'],
            $data['factory_material_id'],
            $data['component'] ?? null
        ]);

        $id = (int) $this->db->lastInsertId();
        $this->show(['id' => $id]);
    }

    public function show(array $params): void {
        if (!$this->canReadBatchMaterial($params['id'])) {
            Response::error('Batch material not found', 404);
        }

        $stmt = $this->db->prepare(
            'SELECT bm.*, fm.material_name, fm.material_type, fm.description
             FROM batch_materials bm
             JOIN factory_materials fm ON bm.factory_material_id = fm.id
             WHERE bm.id = ?'
        );
        $stmt->execute([$params['id']]);
        Response::success($stmt->fetch());
    }

    public function update(array $params): void {
        // Write operations require brand authentication
        $this->requireBrand();

        $data = Validator::getJsonBody();

        if (!$this->verifyBatchMaterialOwnershipAsBrand($params['id'])) {
            Response::error('Batch material not found', 404);
        }

        $stmt = $this->db->prepare(
            'UPDATE batch_materials SET
                component = COALESCE(?, component)
            WHERE id = ?'
        );
        $stmt->execute([
            $data['component'] ?? null,
            $params['id']
        ]);

        $this->show($params);
    }

    public function delete(array $params): void {
        // Write operations require brand authentication
        $this->requireBrand();

        if (!$this->verifyBatchMaterialOwnershipAsBrand($params['id'])) {
            Response::error('Batch material not found', 404);
        }

        $stmt = $this->db->prepare('DELETE FROM batch_materials WHERE id = ?');
        $stmt->execute([$params['id']]);

        Response::success(['deleted' => true]);
    }

    // Get materials by factory material ID (reverse lookup)
    // Brands: see batch materials for their batches
    // Suppliers: see batch materials for their own materials
    public function indexByMaterial(array $params): void {
        if (TenantContext::isBrand()) {
            $brandId = TenantContext::getBrandId();
            $stmt = $this->db->prepare(
                'SELECT bm.*, b.batch_number, p.product_name
                 FROM batch_materials bm
                 JOIN batches b ON bm.batch_id = b.id
                 JOIN products p ON b.product_id = p.id
                 WHERE bm.factory_material_id = ? AND b.brand_id = ?
                 ORDER BY b.created_at DESC'
            );
            $stmt->execute([$params['materialId'], $brandId]);
        } elseif (TenantContext::isSupplier()) {
            // Suppliers can only see usages of their own materials
            if (!$this->verifyMaterialOwnership($params['materialId'])) {
                Response::error('Factory material not found', 404);
            }

            $stmt = $this->db->prepare(
                'SELECT bm.*, b.batch_number, p.product_name
                 FROM batch_materials bm
                 JOIN batches b ON bm.batch_id = b.id
                 JOIN products p ON b.product_id = p.id
                 WHERE bm.factory_material_id = ?
                 ORDER BY b.created_at DESC'
            );
            $stmt->execute([$params['materialId']]);
        } else {
            Response::error('Unauthorized', 403);
        }

        Response::success($stmt->fetchAll());
    }
}
