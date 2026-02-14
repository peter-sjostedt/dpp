<?php
namespace App\Controllers;

use App\Config\TenantContext;
use App\Helpers\Response;
use App\Helpers\Validator;

/**
 * Batch Material controller with multi-tenant access control.
 *
 * NEW FLOW: Supplier has CRUD on batch_materials (they choose which
 * fabric rolls to use). Brand has read-only access.
 *
 * Access:
 * - Brand: Read batch materials for own POs
 * - Supplier: CRUD on batch materials for own batches (via PO)
 */
class BatchMaterialController extends TenantAwareController
{
    /**
     * List materials in a batch
     */
    public function index(array $params): void
    {
        $batchId = (int) $params['batchId'];

        if (!$this->canAccessBatch($batchId)) {
            Response::error('Batch not found', 404);
            return;
        }

        $stmt = $this->db->prepare(
            'SELECT bm.*, fm.material_name, fm.material_type, fm.description
             FROM batch_materials bm
             JOIN factory_materials fm ON bm.factory_material_id = fm.id
             WHERE bm.batch_id = ?
             ORDER BY bm.component'
        );
        $stmt->execute([$batchId]);
        Response::success($stmt->fetchAll());
    }

    /**
     * Create batch material (supplier only)
     */
    public function create(array $params): void
    {
        $this->requireSupplier();

        $batchId = (int) $params['batchId'];
        $data = Validator::getJsonBody();

        if ($error = Validator::required($data, ['factory_material_id'])) {
            Response::error($error);
            return;
        }

        // Verify batch belongs to this supplier's PO
        if (!$this->canAccessBatchAsCurrentSupplier($batchId)) {
            Response::error('Batch not found', 404);
            return;
        }

        // Verify material belongs to this supplier and is active
        $stmt = $this->db->prepare(
            'SELECT id FROM factory_materials WHERE id = ? AND supplier_id = ? AND _is_active = TRUE'
        );
        $stmt->execute([$data['factory_material_id'], TenantContext::getSupplierId()]);
        if (!$stmt->fetch()) {
            Response::error('Factory material not found or inactive', 404);
            return;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO batch_materials (batch_id, factory_material_id, component)
             VALUES (?, ?, ?)'
        );
        $stmt->execute([
            $batchId,
            $data['factory_material_id'],
            $data['component'] ?? null
        ]);

        $id = (int) $this->db->lastInsertId();
        $this->show(['id' => $id]);
    }

    /**
     * Show single batch material
     */
    public function show(array $params): void
    {
        $bmId = (int) $params['id'];

        if (!$this->canReadBatchMaterial($bmId)) {
            Response::error('Batch material not found', 404);
            return;
        }

        $stmt = $this->db->prepare(
            'SELECT bm.*, fm.material_name, fm.material_type, fm.description
             FROM batch_materials bm
             JOIN factory_materials fm ON bm.factory_material_id = fm.id
             WHERE bm.id = ?'
        );
        $stmt->execute([$bmId]);
        $result = $stmt->fetch();

        if (!$result) {
            Response::error('Batch material not found', 404);
            return;
        }

        Response::success($result);
    }

    /**
     * Update batch material (supplier only)
     */
    public function update(array $params): void
    {
        $this->requireSupplier();

        $bmId = (int) $params['id'];

        if (!$this->verifyBatchMaterialOwnershipAsSupplier($bmId)) {
            Response::error('Batch material not found', 404);
            return;
        }

        $data = Validator::getJsonBody();

        // If changing material, verify it belongs to this supplier
        if (isset($data['factory_material_id'])) {
            $stmt = $this->db->prepare(
                'SELECT id FROM factory_materials WHERE id = ? AND supplier_id = ? AND _is_active = TRUE'
            );
            $stmt->execute([$data['factory_material_id'], TenantContext::getSupplierId()]);
            if (!$stmt->fetch()) {
                Response::error('Factory material not found or inactive', 404);
                return;
            }
        }

        $stmt = $this->db->prepare(
            'UPDATE batch_materials SET
                factory_material_id = COALESCE(?, factory_material_id),
                component = COALESCE(?, component)
             WHERE id = ?'
        );
        $stmt->execute([
            $data['factory_material_id'] ?? null,
            $data['component'] ?? null,
            $bmId
        ]);

        $this->show(['id' => $bmId]);
    }

    /**
     * Delete batch material (supplier only)
     */
    public function delete(array $params): void
    {
        $this->requireSupplier();

        $bmId = (int) $params['id'];

        if (!$this->verifyBatchMaterialOwnershipAsSupplier($bmId)) {
            Response::error('Batch material not found', 404);
            return;
        }

        $stmt = $this->db->prepare('DELETE FROM batch_materials WHERE id = ?');
        $stmt->execute([$bmId]);

        Response::success(['deleted' => $bmId]);
    }

    /**
     * Reverse lookup: list batches using a specific material
     * Brand: sees batch materials for own batches
     * Supplier: sees batch materials for own materials
     */
    public function indexByMaterial(array $params): void
    {
        $materialId = (int) $params['materialId'];

        if (TenantContext::isBrand()) {
            $stmt = $this->db->prepare(
                'SELECT bm.*, b.batch_number, po.po_number, p.product_name
                 FROM batch_materials bm
                 JOIN batches b ON bm.batch_id = b.id
                 JOIN purchase_orders po ON b.purchase_order_id = po.id
                 JOIN products p ON po.product_id = p.id
                 WHERE bm.factory_material_id = ? AND po.brand_id = ?
                 ORDER BY b.created_at DESC'
            );
            $stmt->execute([$materialId, TenantContext::getBrandId()]);
        } elseif (TenantContext::isSupplier()) {
            if (!$this->verifyMaterialOwnership($materialId)) {
                Response::error('Factory material not found', 404);
                return;
            }
            $stmt = $this->db->prepare(
                'SELECT bm.*, b.batch_number, po.po_number, p.product_name, br.brand_name
                 FROM batch_materials bm
                 JOIN batches b ON bm.batch_id = b.id
                 JOIN purchase_orders po ON b.purchase_order_id = po.id
                 JOIN products p ON po.product_id = p.id
                 JOIN brands br ON po.brand_id = br.id
                 WHERE bm.factory_material_id = ?
                 ORDER BY b.created_at DESC'
            );
            $stmt->execute([$materialId]);
        } else {
            Response::error('Unauthorized', 403);
            return;
        }

        Response::success($stmt->fetchAll());
    }

    // ========== Access helpers ==========

    private function canAccessBatch(int $batchId): bool
    {
        if (TenantContext::isBrand()) {
            $stmt = $this->db->prepare(
                'SELECT b.id FROM batches b
                 JOIN purchase_orders po ON b.purchase_order_id = po.id
                 WHERE b.id = ? AND po.brand_id = ?'
            );
            $stmt->execute([$batchId, TenantContext::getBrandId()]);
            return (bool) $stmt->fetch();
        }
        if (TenantContext::isSupplier()) {
            return $this->canAccessBatchAsCurrentSupplier($batchId);
        }
        return false;
    }

    private function canAccessBatchAsCurrentSupplier(int $batchId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT b.id FROM batches b
             JOIN purchase_orders po ON b.purchase_order_id = po.id
             WHERE b.id = ? AND po.supplier_id = ?'
        );
        $stmt->execute([$batchId, TenantContext::getSupplierId()]);
        return (bool) $stmt->fetch();
    }

    private function canReadBatchMaterial(int $bmId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT bm.batch_id FROM batch_materials bm WHERE bm.id = ?'
        );
        $stmt->execute([$bmId]);
        $bm = $stmt->fetch();
        if (!$bm) return false;

        return $this->canAccessBatch((int) $bm['batch_id']);
    }

    private function verifyBatchMaterialOwnershipAsSupplier(int $bmId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT bm.id FROM batch_materials bm
             JOIN batches b ON bm.batch_id = b.id
             JOIN purchase_orders po ON b.purchase_order_id = po.id
             WHERE bm.id = ? AND po.supplier_id = ?'
        );
        $stmt->execute([$bmId, TenantContext::getSupplierId()]);
        return (bool) $stmt->fetch();
    }
}