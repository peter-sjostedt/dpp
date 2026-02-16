<?php
namespace App\Controllers;

use App\Config\TenantContext;
use App\Helpers\Response;
use App\Helpers\Validator;

/**
 * Batch controller with multi-tenant access control.
 *
 * NEW FLOW: Batches are created by SUPPLIERS under purchase orders.
 * A batch = a production run with specific material inputs.
 * Multiple batches per PO if factory switches fabric roll.
 *
 * Schema: batches has purchase_order_id (FK to purchase_orders)
 * PO has brand_id, supplier_id, product_id
 *
 * Access:
 * - Brand: Read batches for own POs
 * - Supplier: CRUD on batches for POs directed to them
 */
class BatchController extends TenantAwareController
{
    /**
     * List batches for a purchase order
     */
    public function indexByPo(array $params): void
    {
        $poId = (int) $params['poId'];

        if (!$this->canAccessPo($poId)) {
            Response::error('Purchase order not found', 404);
            return;
        }

        $stmt = $this->db->prepare(
            'SELECT b.*,
                    (SELECT COUNT(*) FROM items i WHERE i.batch_id = b.id) as item_count
             FROM batches b
             WHERE b.purchase_order_id = ?
             ORDER BY b.production_date DESC'
        );
        $stmt->execute([$poId]);
        Response::success($stmt->fetchAll());
    }

    /**
     * List all batches (filtered by tenant, optional ?status= filter)
     */
    public function indexAll(array $params): void
    {
        $statusFilter = $_GET['status'] ?? null;

        if (TenantContext::isBrand()) {
            $sql = 'SELECT b.*, po.po_number, s.supplier_name, p.product_name,
                        (SELECT COUNT(*) FROM items i WHERE i.batch_id = b.id) as item_count
                 FROM batches b
                 JOIN purchase_orders po ON b.purchase_order_id = po.id
                 LEFT JOIN suppliers s ON po.supplier_id = s.id
                 LEFT JOIN products p ON po.product_id = p.id
                 WHERE po.brand_id = ?';
            $bindings = [TenantContext::getBrandId()];
        } else {
            $sql = 'SELECT b.*, po.po_number, br.brand_name, p.product_name,
                        (SELECT COUNT(*) FROM items i WHERE i.batch_id = b.id) as item_count
                 FROM batches b
                 JOIN purchase_orders po ON b.purchase_order_id = po.id
                 LEFT JOIN brands br ON po.brand_id = br.id
                 LEFT JOIN products p ON po.product_id = p.id
                 WHERE po.supplier_id = ?';
            $bindings = [TenantContext::getSupplierId()];
        }

        if ($statusFilter && in_array($statusFilter, ['in_production', 'completed'])) {
            $sql .= ' AND b._status = ?';
            $bindings[] = $statusFilter;
        }

        $sql .= ' ORDER BY b.production_date DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($bindings);
        Response::success($stmt->fetchAll());
    }

    /**
     * Show single batch with materials
     */
    public function show(array $params): void
    {
        $batchId = (int) $params['id'];

        if (!$this->canAccessBatch($batchId)) {
            Response::error('Batch not found', 404);
            return;
        }

        $stmt = $this->db->prepare(
            'SELECT b.*, po.po_number, po.brand_id, po.supplier_id, po.product_id,
                    s.supplier_name, s.supplier_location,
                    p.product_name, br.brand_name
             FROM batches b
             JOIN purchase_orders po ON b.purchase_order_id = po.id
             LEFT JOIN suppliers s ON po.supplier_id = s.id
             LEFT JOIN products p ON po.product_id = p.id
             LEFT JOIN brands br ON po.brand_id = br.id
             WHERE b.id = ?'
        );
        $stmt->execute([$batchId]);
        $batch = $stmt->fetch();

        if (!$batch) {
            Response::error('Batch not found', 404);
            return;
        }

        // Include linked materials
        $stmt = $this->db->prepare(
            'SELECT bm.*, fm.material_name, fm.material_type
             FROM batch_materials bm
             JOIN factory_materials fm ON bm.factory_material_id = fm.id
             WHERE bm.batch_id = ?'
        );
        $stmt->execute([$batchId]);
        $batch['materials'] = $stmt->fetchAll();

        Response::success($batch);
    }

    /**
     * Create batch (supplier only, under a PO)
     */
    public function create(array $params): void
    {
        $this->requireSupplier();

        $poId = (int) $params['poId'];
        $data = Validator::getJsonBody();

        if ($error = Validator::required($data, ['batch_number'])) {
            Response::error($error);
            return;
        }

        // Verify PO exists and is directed to this supplier
        $po = $this->fetchPoAsSupplier($poId);
        if (!$po) {
            Response::error('Purchase order not found', 404);
            return;
        }

        // PO must be accepted to create batches
        if ($po['_status'] !== 'accepted') {
            Response::error('Purchase order must be accepted before creating batches', 400);
            return;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO batches (purchase_order_id, batch_number, production_date, quantity, _status,
                facility_name, facility_location, facility_registry, facility_identifier,
                country_of_origin_confection, country_of_origin_dyeing, country_of_origin_weaving)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $poId,
            $data['batch_number'],
            $data['production_date'] ?? null,
            $data['quantity'] ?? null,
            'in_production',
            $data['facility_name'] ?? null,
            $data['facility_location'] ?? null,
            $data['facility_registry'] ?? null,
            $data['facility_identifier'] ?? null,
            $data['country_of_origin_confection'] ?? null,
            $data['country_of_origin_dyeing'] ?? null,
            $data['country_of_origin_weaving'] ?? null,
        ]);

        $id = (int) $this->db->lastInsertId();
        $this->show(['id' => $id]);
    }

    /**
     * Update batch (supplier only, in_production only)
     */
    public function update(array $params): void
    {
        $this->requireSupplier();

        $batchId = (int) $params['id'];

        if (!$this->canAccessBatchAsCurrentSupplier($batchId)) {
            Response::error('Batch not found', 404);
            return;
        }

        // Verify batch is still in_production
        $stmt = $this->db->prepare('SELECT _status FROM batches WHERE id = ?');
        $stmt->execute([$batchId]);
        $batch = $stmt->fetch();
        if ($batch['_status'] !== 'in_production') {
            Response::error('Can only update batches with status in_production', 400);
            return;
        }

        $data = Validator::getJsonBody();

        $stmt = $this->db->prepare(
            'UPDATE batches SET
                batch_number = COALESCE(?, batch_number),
                production_date = COALESCE(?, production_date),
                quantity = COALESCE(?, quantity),
                facility_name = COALESCE(?, facility_name),
                facility_location = COALESCE(?, facility_location),
                facility_registry = COALESCE(?, facility_registry),
                facility_identifier = COALESCE(?, facility_identifier),
                country_of_origin_confection = COALESCE(?, country_of_origin_confection),
                country_of_origin_dyeing = COALESCE(?, country_of_origin_dyeing),
                country_of_origin_weaving = COALESCE(?, country_of_origin_weaving)
             WHERE id = ?'
        );
        $stmt->execute([
            $data['batch_number'] ?? null,
            $data['production_date'] ?? null,
            $data['quantity'] ?? null,
            $data['facility_name'] ?? null,
            $data['facility_location'] ?? null,
            $data['facility_registry'] ?? null,
            $data['facility_identifier'] ?? null,
            $data['country_of_origin_confection'] ?? null,
            $data['country_of_origin_dyeing'] ?? null,
            $data['country_of_origin_weaving'] ?? null,
            $batchId
        ]);

        $this->show(['id' => $batchId]);
    }

    /**
     * Delete batch (supplier only, in_production, no items)
     */
    public function delete(array $params): void
    {
        $this->requireSupplier();

        $batchId = (int) $params['id'];

        if (!$this->canAccessBatchAsCurrentSupplier($batchId)) {
            Response::error('Batch not found', 404);
            return;
        }

        // Verify batch is in_production
        $stmt = $this->db->prepare('SELECT _status FROM batches WHERE id = ?');
        $stmt->execute([$batchId]);
        $batch = $stmt->fetch();
        if ($batch['_status'] !== 'in_production') {
            Response::error('Can only delete batches with status in_production', 400);
            return;
        }

        // Check for items
        $stmt = $this->db->prepare('SELECT COUNT(*) as cnt FROM items WHERE batch_id = ?');
        $stmt->execute([$batchId]);
        if ((int) $stmt->fetch()['cnt'] > 0) {
            Response::error('Cannot delete batch with existing items', 400);
            return;
        }

        $stmt = $this->db->prepare('DELETE FROM batches WHERE id = ?');
        $stmt->execute([$batchId]);

        Response::success(['deleted' => $batchId]);
    }

    /**
     * Complete batch (supplier only, in_production → completed)
     */
    public function complete(array $params): void
    {
        $this->requireSupplier();

        $batchId = (int) $params['id'];

        if (!$this->canAccessBatchAsCurrentSupplier($batchId)) {
            Response::error('Batch not found', 404);
            return;
        }

        $stmt = $this->db->prepare('SELECT _status FROM batches WHERE id = ?');
        $stmt->execute([$batchId]);
        $batch = $stmt->fetch();

        if ($batch['_status'] !== 'in_production') {
            Response::error('Only in_production batches can be completed', 400);
            return;
        }

        $stmt = $this->db->prepare(
            'UPDATE batches SET _status = ? WHERE id = ?'
        );
        $stmt->execute(['completed', $batchId]);

        $this->show(['id' => $batchId]);
    }

    // ========== Access helpers ==========

    /**
     * Can current tenant access this PO?
     */
    private function canAccessPo(int $poId): bool
    {
        if (TenantContext::isBrand()) {
            $stmt = $this->db->prepare('SELECT id FROM purchase_orders WHERE id = ? AND brand_id = ?');
            $stmt->execute([$poId, TenantContext::getBrandId()]);
            return (bool) $stmt->fetch();
        }
        if (TenantContext::isSupplier()) {
            $stmt = $this->db->prepare('SELECT id FROM purchase_orders WHERE id = ? AND supplier_id = ?');
            $stmt->execute([$poId, TenantContext::getSupplierId()]);
            return (bool) $stmt->fetch();
        }
        return false;
    }

    /**
     * Can current tenant access this batch?
     */
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

    /**
     * Is this batch for the current supplier (via PO)?
     */
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

    /**
     * Fetch PO for current supplier
     */
    private function fetchPoAsSupplier(int $poId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM purchase_orders WHERE id = ? AND supplier_id = ?'
        );
        $stmt->execute([$poId, TenantContext::getSupplierId()]);
        return $stmt->fetch() ?: null;
    }
}