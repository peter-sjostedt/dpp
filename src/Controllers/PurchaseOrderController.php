<?php
namespace App\Controllers;

use App\Config\TenantContext;
use App\Helpers\Response;
use App\Helpers\Validator;

/**
 * Purchase Order controller with multi-tenant access control.
 *
 * A PO is a brand's order to a supplier for a specific product.
 * Suppliers create batches under POs during production.
 *
 * Access:
 * - Brand: CRUD on own POs
 * - Supplier: Read POs directed to them, accept POs
 */
class PurchaseOrderController extends TenantAwareController
{
    /**
     * List all POs for current tenant
     */
    public function indexAll(array $params): void
    {
        if (TenantContext::isBrand()) {
            $stmt = $this->db->prepare(
                'SELECT po.*, s.supplier_name, p.product_name,
                        (SELECT COUNT(*) FROM batches b WHERE b.purchase_order_id = po.id) as batch_count,
                        (SELECT COALESCE(SUM(b2.quantity), 0) FROM batches b2 WHERE b2.purchase_order_id = po.id) as produced_quantity
                 FROM purchase_orders po
                 LEFT JOIN suppliers s ON po.supplier_id = s.id
                 LEFT JOIN products p ON po.product_id = p.id
                 WHERE po.brand_id = ?
                 ORDER BY po.created_at DESC'
            );
            $stmt->execute([TenantContext::getBrandId()]);
        } else {
            $stmt = $this->db->prepare(
                'SELECT po.*, br.brand_name, p.product_name,
                        (SELECT COUNT(*) FROM batches b WHERE b.purchase_order_id = po.id) as batch_count,
                        (SELECT COALESCE(SUM(b2.quantity), 0) FROM batches b2 WHERE b2.purchase_order_id = po.id) as produced_quantity
                 FROM purchase_orders po
                 LEFT JOIN brands br ON po.brand_id = br.id
                 LEFT JOIN products p ON po.product_id = p.id
                 WHERE po.supplier_id = ?
                 ORDER BY po.created_at DESC'
            );
            $stmt->execute([TenantContext::getSupplierId()]);
        }

        Response::success($stmt->fetchAll());
    }

    /**
     * List POs for a specific supplier
     */
    public function indexBySupplier(array $params): void
    {
        $supplierId = (int) $params['supplierId'];

        if (TenantContext::isBrand()) {
            if (!$this->canAccessSupplier($supplierId)) {
                Response::error('Supplier not found', 404);
                return;
            }
            $stmt = $this->db->prepare(
                'SELECT po.*, s.supplier_name, p.product_name
                 FROM purchase_orders po
                 LEFT JOIN suppliers s ON po.supplier_id = s.id
                 LEFT JOIN products p ON po.product_id = p.id
                 WHERE po.brand_id = ? AND po.supplier_id = ?
                 ORDER BY po.created_at DESC'
            );
            $stmt->execute([TenantContext::getBrandId(), $supplierId]);
        } else {
            if ($supplierId !== TenantContext::getSupplierId()) {
                Response::error('Supplier not found', 404);
                return;
            }
            $stmt = $this->db->prepare(
                'SELECT po.*, br.brand_name, p.product_name
                 FROM purchase_orders po
                 LEFT JOIN brands br ON po.brand_id = br.id
                 LEFT JOIN products p ON po.product_id = p.id
                 WHERE po.supplier_id = ?
                 ORDER BY po.created_at DESC'
            );
            $stmt->execute([$supplierId]);
        }

        Response::success($stmt->fetchAll());
    }

    /**
     * List POs for a specific product
     */
    public function indexByProduct(array $params): void
    {
        $productId = (int) $params['productId'];

        if (TenantContext::isBrand()) {
            if (!$this->verifyProductOwnership($productId)) {
                Response::error('Product not found', 404);
                return;
            }
            $stmt = $this->db->prepare(
                'SELECT po.*, s.supplier_name, p.product_name
                 FROM purchase_orders po
                 LEFT JOIN suppliers s ON po.supplier_id = s.id
                 LEFT JOIN products p ON po.product_id = p.id
                 WHERE po.product_id = ? AND po.brand_id = ?
                 ORDER BY po.created_at DESC'
            );
            $stmt->execute([$productId, TenantContext::getBrandId()]);
        } else {
            // Supplier sees POs for products from related brands
            $stmt = $this->db->prepare(
                'SELECT po.*, br.brand_name, p.product_name
                 FROM purchase_orders po
                 LEFT JOIN brands br ON po.brand_id = br.id
                 LEFT JOIN products p ON po.product_id = p.id
                 WHERE po.product_id = ? AND po.supplier_id = ?
                 ORDER BY po.created_at DESC'
            );
            $stmt->execute([$productId, TenantContext::getSupplierId()]);
        }

        Response::success($stmt->fetchAll());
    }

    /**
     * Show single PO with batches
     */
    public function show(array $params): void
    {
        $poId = (int) $params['id'];
        $po = $this->fetchPo($poId);

        if (!$po) {
            Response::error('Purchase order not found', 404);
            return;
        }

        // Include batches
        $stmt = $this->db->prepare(
            'SELECT b.*,
                    (SELECT COUNT(*) FROM items i WHERE i.batch_id = b.id) as item_count
             FROM batches b
             WHERE b.purchase_order_id = ?
             ORDER BY b.production_date DESC'
        );
        $stmt->execute([$poId]);
        $po['batches'] = $stmt->fetchAll();

        Response::success($po);
    }

    /**
     * Create PO (brand only)
     */
    public function create(array $params): void
    {
        $this->requireBrand();

        $brandId = TenantContext::getBrandId();
        $data = Validator::getJsonBody();

        if ($error = Validator::required($data, ['supplier_id', 'product_id', 'po_number'])) {
            Response::error($error);
            return;
        }

        // Verify supplier relationship
        if (!$this->canAccessSupplier($data['supplier_id'])) {
            Response::error('Supplier not found', 404);
            return;
        }

        // Verify product belongs to brand
        if (!$this->verifyProductOwnership($data['product_id'])) {
            Response::error('Product not found', 404);
            return;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO purchase_orders (brand_id, supplier_id, product_id, po_number, quantity, requested_delivery_date, _status)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $brandId,
            $data['supplier_id'],
            $data['product_id'],
            $data['po_number'],
            $data['quantity'] ?? null,
            $data['requested_delivery_date'] ?? null,
            $data['status'] ?? 'draft'
        ]);

        $id = (int) $this->db->lastInsertId();
        $this->show(['id' => $id]);
    }

    /**
     * Update PO (brand only, not fulfilled)
     */
    public function update(array $params): void
    {
        $this->requireBrand();

        $poId = (int) $params['id'];
        $po = $this->fetchPoAsBrand($poId);

        if (!$po) {
            Response::error('Purchase order not found', 404);
            return;
        }

        if ($po['_status'] === 'fulfilled') {
            Response::error('Cannot modify a fulfilled purchase order', 400);
            return;
        }

        $data = Validator::getJsonBody();

        $stmt = $this->db->prepare(
            'UPDATE purchase_orders SET
                po_number = COALESCE(?, po_number),
                quantity = COALESCE(?, quantity),
                requested_delivery_date = COALESCE(?, requested_delivery_date),
                _status = COALESCE(?, _status)
             WHERE id = ?'
        );
        $stmt->execute([
            $data['po_number'] ?? null,
            $data['quantity'] ?? null,
            $data['requested_delivery_date'] ?? null,
            $data['status'] ?? null,
            $poId
        ]);

        $this->show(['id' => $poId]);
    }

    /**
     * Accept PO (supplier only)
     */
    public function accept(array $params): void
    {
        $this->requireSupplier();

        $poId = (int) $params['id'];
        $po = $this->fetchPoAsSupplier($poId);

        if (!$po) {
            Response::error('Purchase order not found', 404);
            return;
        }

        if ($po['_status'] !== 'sent') {
            Response::error('Only sent purchase orders can be accepted', 400);
            return;
        }

        $stmt = $this->db->prepare(
            'UPDATE purchase_orders SET _status = ? WHERE id = ?'
        );
        $stmt->execute(['accepted', $poId]);

        $this->show(['id' => $poId]);
    }

    /**
     * Delete PO (brand only, draft only)
     */
    public function delete(array $params): void
    {
        $this->requireBrand();

        $poId = (int) $params['id'];
        $po = $this->fetchPoAsBrand($poId);

        if (!$po) {
            Response::error('Purchase order not found', 404);
            return;
        }

        if ($po['_status'] !== 'draft') {
            Response::error('Only draft purchase orders can be deleted', 400);
            return;
        }

        $stmt = $this->db->prepare('DELETE FROM purchase_orders WHERE id = ?');
        $stmt->execute([$poId]);

        Response::success(['deleted' => $poId]);
    }

    // ========== Helpers ==========

    private function fetchPo(int $poId): ?array
    {
        if (TenantContext::isBrand()) {
            return $this->fetchPoAsBrand($poId);
        }
        return $this->fetchPoAsSupplier($poId);
    }

    private function fetchPoAsBrand(int $poId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT po.*, s.supplier_name, p.product_name
             FROM purchase_orders po
             LEFT JOIN suppliers s ON po.supplier_id = s.id
             LEFT JOIN products p ON po.product_id = p.id
             WHERE po.id = ? AND po.brand_id = ?'
        );
        $stmt->execute([$poId, TenantContext::getBrandId()]);
        return $stmt->fetch() ?: null;
    }

    private function fetchPoAsSupplier(int $poId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT po.*, br.brand_name, p.product_name
             FROM purchase_orders po
             LEFT JOIN brands br ON po.brand_id = br.id
             LEFT JOIN products p ON po.product_id = p.id
             WHERE po.id = ? AND po.supplier_id = ?'
        );
        $stmt->execute([$poId, TenantContext::getSupplierId()]);
        return $stmt->fetch() ?: null;
    }
}