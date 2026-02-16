<?php
namespace App\Controllers;

use App\Config\TenantContext;
use App\Helpers\Response;
use App\Helpers\Validator;

/**
 * Purchase Order Line controller with multi-tenant access control.
 *
 * A PO line specifies quantity per product variant (size/color).
 *
 * Access:
 * - Brand: CRUD on lines for own POs
 * - Supplier: Read-only on lines for POs directed to them
 */
class PurchaseOrderLineController extends TenantAwareController
{
    /**
     * List lines for a PO
     */
    public function index(array $params): void
    {
        $poId = (int) $params['poId'];

        if (!$this->canAccessPo($poId)) {
            Response::error('Purchase order not found', 404);
            return;
        }

        $stmt = $this->db->prepare(
            'SELECT pol.*, pv.item_number, pv.size, pv.size_country_code,
                    pv.color_brand, pv.color_general, pv.gtin AS variant_gtin
             FROM purchase_order_lines pol
             JOIN product_variants pv ON pol.product_variant_id = pv.id
             WHERE pol.purchase_order_id = ?
             ORDER BY pv.color_brand, pv.size'
        );
        $stmt->execute([$poId]);
        Response::success($stmt->fetchAll());
    }

    /**
     * Create line (brand only)
     */
    public function create(array $params): void
    {
        $this->requireBrand();

        $poId = (int) $params['poId'];
        $data = Validator::getJsonBody();

        if ($error = Validator::required($data, ['product_variant_id', 'quantity'])) {
            Response::error($error);
            return;
        }

        // Verify PO belongs to brand
        $po = $this->fetchPoAsBrand($poId);
        if (!$po) {
            Response::error('Purchase order not found', 404);
            return;
        }

        // Verify PO status is draft
        if ($po['_status'] !== 'draft') {
            Response::error('Can only add lines to draft purchase orders', 400);
            return;
        }

        // Verify product_variant belongs to same product as PO
        $stmt = $this->db->prepare(
            'SELECT id FROM product_variants WHERE id = ? AND product_id = ?'
        );
        $stmt->execute([$data['product_variant_id'], $po['product_id']]);
        if (!$stmt->fetch()) {
            Response::error('Product variant not found or does not belong to this PO\'s product', 400);
            return;
        }

        // Quantity must be > 0
        if ((int) $data['quantity'] <= 0) {
            Response::error('Quantity must be greater than 0', 400);
            return;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO purchase_order_lines (purchase_order_id, product_variant_id, quantity)
             VALUES (?, ?, ?)'
        );
        $stmt->execute([
            $poId,
            $data['product_variant_id'],
            (int) $data['quantity']
        ]);

        $id = (int) $this->db->lastInsertId();
        $this->show(['id' => $id]);
    }

    /**
     * Show single line
     */
    public function show(array $params): void
    {
        $lineId = (int) $params['id'];

        if (!$this->canAccessLine($lineId)) {
            Response::error('Purchase order line not found', 404);
            return;
        }

        $stmt = $this->db->prepare(
            'SELECT pol.*, pv.item_number, pv.size, pv.size_country_code,
                    pv.color_brand, pv.color_general, pv.gtin AS variant_gtin
             FROM purchase_order_lines pol
             JOIN product_variants pv ON pol.product_variant_id = pv.id
             WHERE pol.id = ?'
        );
        $stmt->execute([$lineId]);
        $result = $stmt->fetch();

        if (!$result) {
            Response::error('Purchase order line not found', 404);
            return;
        }

        Response::success($result);
    }

    /**
     * Update line (brand only)
     */
    public function update(array $params): void
    {
        $this->requireBrand();

        $lineId = (int) $params['id'];

        if (!$this->verifyLineOwnershipAsBrand($lineId)) {
            Response::error('Purchase order line not found', 404);
            return;
        }

        // Fetch the line's PO to check status
        $stmt = $this->db->prepare(
            'SELECT po.* FROM purchase_orders po
             JOIN purchase_order_lines pol ON pol.purchase_order_id = po.id
             WHERE pol.id = ?'
        );
        $stmt->execute([$lineId]);
        $po = $stmt->fetch();

        if ($po['_status'] !== 'draft') {
            Response::error('Can only modify lines on draft purchase orders', 400);
            return;
        }

        $data = Validator::getJsonBody();

        // If changing variant, verify it belongs to same product as PO
        if (isset($data['product_variant_id'])) {
            $stmt = $this->db->prepare(
                'SELECT id FROM product_variants WHERE id = ? AND product_id = ?'
            );
            $stmt->execute([$data['product_variant_id'], $po['product_id']]);
            if (!$stmt->fetch()) {
                Response::error('Product variant not found or does not belong to this PO\'s product', 400);
                return;
            }
        }

        // If changing quantity, verify > 0
        if (isset($data['quantity']) && (int) $data['quantity'] <= 0) {
            Response::error('Quantity must be greater than 0', 400);
            return;
        }

        $stmt = $this->db->prepare(
            'UPDATE purchase_order_lines SET
                product_variant_id = COALESCE(?, product_variant_id),
                quantity = COALESCE(?, quantity)
             WHERE id = ?'
        );
        $stmt->execute([
            $data['product_variant_id'] ?? null,
            isset($data['quantity']) ? (int) $data['quantity'] : null,
            $lineId
        ]);

        $this->show(['id' => $lineId]);
    }

    /**
     * Delete line (brand only)
     */
    public function delete(array $params): void
    {
        $this->requireBrand();

        $lineId = (int) $params['id'];

        if (!$this->verifyLineOwnershipAsBrand($lineId)) {
            Response::error('Purchase order line not found', 404);
            return;
        }

        // Verify PO status is draft
        $stmt = $this->db->prepare(
            'SELECT po._status FROM purchase_orders po
             JOIN purchase_order_lines pol ON pol.purchase_order_id = po.id
             WHERE pol.id = ?'
        );
        $stmt->execute([$lineId]);
        $po = $stmt->fetch();

        if ($po['_status'] !== 'draft') {
            Response::error('Can only delete lines from draft purchase orders', 400);
            return;
        }

        $stmt = $this->db->prepare('DELETE FROM purchase_order_lines WHERE id = ?');
        $stmt->execute([$lineId]);

        Response::success(['deleted' => $lineId]);
    }

    // ========== Access helpers ==========

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

    private function canAccessLine(int $lineId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT pol.purchase_order_id FROM purchase_order_lines pol WHERE pol.id = ?'
        );
        $stmt->execute([$lineId]);
        $line = $stmt->fetch();
        if (!$line) return false;
        return $this->canAccessPo((int) $line['purchase_order_id']);
    }

    private function verifyLineOwnershipAsBrand(int $lineId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT pol.id FROM purchase_order_lines pol
             JOIN purchase_orders po ON pol.purchase_order_id = po.id
             WHERE pol.id = ? AND po.brand_id = ?'
        );
        $stmt->execute([$lineId, TenantContext::getBrandId()]);
        return (bool) $stmt->fetch();
    }

    private function fetchPoAsBrand(int $poId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM purchase_orders WHERE id = ? AND brand_id = ?'
        );
        $stmt->execute([$poId, TenantContext::getBrandId()]);
        return $stmt->fetch() ?: null;
    }
}
