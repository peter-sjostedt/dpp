<?php
namespace App\Controllers;

use App\Config\TenantContext;
use App\Helpers\Response;
use App\Helpers\Validator;

/**
 * Item controller with multi-tenant access control.
 *
 * NEW FLOW: Items are created by SUPPLIERS during production.
 * They register RFID tags (TID/SGTIN) as they produce garments.
 *
 * Access:
 * - Brand: Read items for own POs
 * - Supplier: Create + Read + Delete items for own batches
 */
class ItemController extends TenantAwareController
{
    /**
     * List items in a batch
     */
    public function index(array $params): void
    {
        $batchId = (int) $params['batchId'];

        if (!$this->canAccessBatch($batchId)) {
            Response::error('Batch not found', 404);
            return;
        }

        $stmt = $this->db->prepare(
            'SELECT i.*, pv.gtin as variant_gtin, pv.size, pv.color_brand
             FROM items i
             LEFT JOIN product_variants pv ON i.product_variant_id = pv.id
             WHERE i.batch_id = ?
             ORDER BY i.created_at DESC'
        );
        $stmt->execute([$batchId]);
        Response::success($stmt->fetchAll());
    }

    /**
     * Show single item with full context
     */
    public function show(array $params): void
    {
        $itemId = (int) $params['id'];

        if (!$this->canAccessItem($itemId)) {
            Response::error('Item not found', 404);
            return;
        }

        $stmt = $this->db->prepare(
            'SELECT i.*, b.batch_number, po.po_number,
                    pv.gtin as variant_gtin, pv.size, pv.color_brand,
                    p.product_name, p.data_carrier_type, p.data_carrier_material, p.data_carrier_location,
                    s.supplier_name, br.brand_name
             FROM items i
             JOIN batches b ON i.batch_id = b.id
             JOIN purchase_orders po ON b.purchase_order_id = po.id
             JOIN products p ON po.product_id = p.id
             JOIN suppliers s ON po.supplier_id = s.id
             JOIN brands br ON po.brand_id = br.id
             LEFT JOIN product_variants pv ON i.product_variant_id = pv.id
             WHERE i.id = ?'
        );
        $stmt->execute([$itemId]);
        $item = $stmt->fetch();

        if (!$item) {
            Response::error('Item not found', 404);
            return;
        }

        Response::success($item);
    }

    /**
     * Find item by SGTIN
     */
    public function showBySgtin(array $params): void
    {
        $sgtin = $params['sgtin'];

        // Find the item first, then check access
        $stmt = $this->db->prepare(
            'SELECT i.*, b.batch_number, po.po_number, po.brand_id, po.supplier_id,
                    pv.gtin as variant_gtin, pv.size, pv.color_brand,
                    p.product_name, p.data_carrier_type, p.data_carrier_material, p.data_carrier_location,
                    s.supplier_name, br.brand_name
             FROM items i
             JOIN batches b ON i.batch_id = b.id
             JOIN purchase_orders po ON b.purchase_order_id = po.id
             JOIN products p ON po.product_id = p.id
             JOIN suppliers s ON po.supplier_id = s.id
             JOIN brands br ON po.brand_id = br.id
             LEFT JOIN product_variants pv ON i.product_variant_id = pv.id
             WHERE i.sgtin = ?'
        );
        $stmt->execute([$sgtin]);
        $item = $stmt->fetch();

        if (!$item) {
            Response::error('Item not found', 404);
            return;
        }

        // Check access
        if (TenantContext::isBrand() && (int) $item['brand_id'] !== TenantContext::getBrandId()) {
            Response::error('Item not found', 404);
            return;
        }
        if (TenantContext::isSupplier() && (int) $item['supplier_id'] !== TenantContext::getSupplierId()) {
            Response::error('Item not found', 404);
            return;
        }

        Response::success($item);
    }

    /**
     * Create single item (supplier only)
     */
    public function create(array $params): void
    {
        $this->requireSupplier();

        $batchId = (int) $params['batchId'];
        $data = Validator::getJsonBody();

        if (!$this->canAccessBatchAsCurrentSupplier($batchId)) {
            Response::error('Batch not found', 404);
            return;
        }

        // Get product GTIN for SGTIN generation
        $productCode = $this->getProductCodeForBatch($batchId);

        $sgtin = $data['sgtin'] ?? $this->generateSgtin($productCode);

        // Check for duplicate sgtin
        $stmt = $this->db->prepare('SELECT id FROM items WHERE sgtin = ?');
        $stmt->execute([$sgtin]);
        if ($stmt->fetch()) {
            Response::error('SGTIN already exists', 400);
            return;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO items (batch_id, product_variant_id, unique_product_id, tid, sgtin, serial_number, _status)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $batchId,
            $data['product_variant_id'] ?? null,
            $data['unique_product_id'] ?? null,
            $data['tid'] ?? null,
            $sgtin,
            $data['serial_number'] ?? null,
            $data['status'] ?? 'produced'
        ]);

        $id = (int) $this->db->lastInsertId();
        $this->show(['id' => $id]);
    }

    /**
     * Bulk create items (supplier only)
     */
    public function createBulk(array $params): void
    {
        $this->requireSupplier();

        $batchId = (int) $params['batchId'];
        $data = Validator::getJsonBody();

        if ($error = Validator::required($data, ['quantity'])) {
            Response::error($error);
            return;
        }

        $quantity = (int) $data['quantity'];
        if ($quantity < 1 || $quantity > 1000) {
            Response::error('Quantity must be between 1 and 1000', 400);
            return;
        }

        if (!$this->canAccessBatchAsCurrentSupplier($batchId)) {
            Response::error('Batch not found', 404);
            return;
        }

        $productCode = $this->getProductCodeForBatch($batchId);
        $variantId = $data['product_variant_id'] ?? null;
        $serialPrefix = $data['serial_prefix'] ?? 'SN-';

        $createdIds = [];
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                'INSERT INTO items (batch_id, product_variant_id, sgtin, serial_number, _status)
                 VALUES (?, ?, ?, ?, ?)'
            );

            for ($i = 0; $i < $quantity; $i++) {
                $sgtin = $this->generateSgtin($productCode);
                $serial = $serialPrefix . str_pad((string)($i + 1), 5, '0', STR_PAD_LEFT);

                $stmt->execute([
                    $batchId,
                    $variantId,
                    $sgtin,
                    $serial,
                    'produced'
                ]);
                $createdIds[] = [
                    'id' => (int) $this->db->lastInsertId(),
                    'sgtin' => $sgtin,
                    'serial_number' => $serial
                ];
            }

            $this->db->commit();
            Response::success([
                'created' => count($createdIds),
                'items' => $createdIds
            ], 201);
        } catch (\Exception $e) {
            $this->db->rollBack();
            Response::error('Failed to create items: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete item (supplier only)
     */
    public function delete(array $params): void
    {
        $this->requireSupplier();

        $itemId = (int) $params['id'];

        if (!$this->canAccessItemAsCurrentSupplier($itemId)) {
            Response::error('Item not found', 404);
            return;
        }

        $stmt = $this->db->prepare('DELETE FROM items WHERE id = ?');
        $stmt->execute([$itemId]);

        Response::success(['deleted' => $itemId]);
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
        return $this->canAccessBatchAsCurrentSupplier($batchId);
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

    private function canAccessItem(int $itemId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT i.batch_id FROM items i WHERE i.id = ?'
        );
        $stmt->execute([$itemId]);
        $item = $stmt->fetch();
        if (!$item) return false;

        return $this->canAccessBatch((int) $item['batch_id']);
    }

    private function canAccessItemAsCurrentSupplier(int $itemId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT i.id FROM items i
             JOIN batches b ON i.batch_id = b.id
             JOIN purchase_orders po ON b.purchase_order_id = po.id
             WHERE i.id = ? AND po.supplier_id = ?'
        );
        $stmt->execute([$itemId, TenantContext::getSupplierId()]);
        return (bool) $stmt->fetch();
    }

    private function getProductCodeForBatch(int $batchId): string
    {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(p.gtin, p.id) as product_code
             FROM batches b
             JOIN purchase_orders po ON b.purchase_order_id = po.id
             JOIN products p ON po.product_id = p.id
             WHERE b.id = ?'
        );
        $stmt->execute([$batchId]);
        $result = $stmt->fetch();
        return $result ? $result['product_code'] : '0';
    }

    private function generateSgtin(string $gtin): string
    {
        $serial = str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        return $gtin . '.' . $serial;
    }
}