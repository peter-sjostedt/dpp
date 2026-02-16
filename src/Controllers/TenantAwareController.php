<?php

namespace App\Controllers;

use App\Config\Database;
use App\Config\TenantContext;
use App\Helpers\Response;

/**
 * Base controller with multi-tenant access control methods.
 * All controllers should extend this class.
 */
abstract class TenantAwareController
{
    protected \PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Require authentication as a brand, return 403 if supplier
     */
    protected function requireBrand(): void
    {
        if (!TenantContext::isBrand()) {
            Response::error('This endpoint requires brand authentication', 403);
            exit;
        }
    }

    /**
     * Require authentication as a supplier, return 403 if brand
     */
    protected function requireSupplier(): void
    {
        if (!TenantContext::isSupplier()) {
            Response::error('This endpoint requires supplier authentication', 403);
            exit;
        }
    }

    /**
     * Check if current brand has access to a supplier via brand_suppliers
     */
    protected function canAccessSupplier(int|string $supplierId): bool
    {
        if (!TenantContext::isBrand()) {
            return false;
        }

        $stmt = $this->db->prepare(
            'SELECT id FROM brand_suppliers
             WHERE brand_id = ? AND supplier_id = ? AND _is_active = TRUE'
        );
        $stmt->execute([TenantContext::getBrandId(), $supplierId]);
        return (bool) $stmt->fetch();
    }

    /**
     * Check if current supplier has relationship with a brand via brand_suppliers
     */
    protected function canAccessBrand(int|string $brandId): bool
    {
        if (!TenantContext::isSupplier()) {
            return false;
        }

        $stmt = $this->db->prepare(
            'SELECT id FROM brand_suppliers
             WHERE brand_id = ? AND supplier_id = ? AND _is_active = TRUE'
        );
        $stmt->execute([$brandId, TenantContext::getSupplierId()]);
        return (bool) $stmt->fetch();
    }

    /**
     * Get all brand IDs that the current supplier has relationships with
     */
    protected function getAccessibleBrandIds(): array
    {
        if (!TenantContext::isSupplier()) {
            return [];
        }

        $stmt = $this->db->prepare(
            'SELECT brand_id FROM brand_suppliers
             WHERE supplier_id = ? AND _is_active = TRUE'
        );
        $stmt->execute([TenantContext::getSupplierId()]);
        return array_column($stmt->fetchAll(), 'brand_id');
    }

    /**
     * Get all supplier IDs that the current brand has relationships with
     */
    protected function getAccessibleSupplierIds(): array
    {
        if (!TenantContext::isBrand()) {
            return [];
        }

        $stmt = $this->db->prepare(
            'SELECT supplier_id FROM brand_suppliers
             WHERE brand_id = ? AND _is_active = TRUE'
        );
        $stmt->execute([TenantContext::getBrandId()]);
        return array_column($stmt->fetchAll(), 'supplier_id');
    }

    /**
     * Verify product belongs to current brand
     */
    protected function verifyProductOwnership(int|string $productId): bool
    {
        if (!TenantContext::isBrand()) {
            return false;
        }

        $stmt = $this->db->prepare('SELECT id FROM products WHERE id = ? AND brand_id = ?');
        $stmt->execute([$productId, TenantContext::getBrandId()]);
        return (bool) $stmt->fetch();
    }

    /**
     * Verify batch belongs to current brand (via purchase_orders)
     */
    protected function verifyBatchOwnership(int|string $batchId): bool
    {
        if (!TenantContext::isBrand()) {
            return false;
        }

        $stmt = $this->db->prepare(
            'SELECT b.id FROM batches b
             JOIN purchase_orders po ON b.purchase_order_id = po.id
             WHERE b.id = ? AND po.brand_id = ?'
        );
        $stmt->execute([$batchId, TenantContext::getBrandId()]);
        return (bool) $stmt->fetch();
    }

    /**
     * Verify item belongs to current brand (via batch → purchase_orders)
     */
    protected function verifyItemOwnership(int|string $itemId): bool
    {
        if (!TenantContext::isBrand()) {
            return false;
        }

        $stmt = $this->db->prepare(
            'SELECT i.id FROM items i
             JOIN batches b ON i.batch_id = b.id
             JOIN purchase_orders po ON b.purchase_order_id = po.id
             WHERE i.id = ? AND po.brand_id = ?'
        );
        $stmt->execute([$itemId, TenantContext::getBrandId()]);
        return (bool) $stmt->fetch();
    }

    /**
     * Verify factory material belongs to current supplier
     */
    protected function verifyMaterialOwnership(int|string $materialId): bool
    {
        if (!TenantContext::isSupplier()) {
            return false;
        }

        $stmt = $this->db->prepare(
            'SELECT id FROM factory_materials WHERE id = ? AND supplier_id = ?'
        );
        $stmt->execute([$materialId, TenantContext::getSupplierId()]);
        return (bool) $stmt->fetch();
    }

    /**
     * Check if brand can access a material (via supplier relationship)
     */
    protected function canAccessMaterial(int|string $materialId): bool
    {
        if (TenantContext::isSupplier()) {
            return $this->verifyMaterialOwnership($materialId);
        }

        if (!TenantContext::isBrand()) {
            return false;
        }

        $stmt = $this->db->prepare(
            'SELECT fm.id FROM factory_materials fm
             JOIN brand_suppliers bs ON fm.supplier_id = bs.supplier_id
             WHERE fm.id = ? AND bs.brand_id = ? AND bs._is_active = TRUE'
        );
        $stmt->execute([$materialId, TenantContext::getBrandId()]);
        return (bool) $stmt->fetch();
    }

    /**
     * Get brand_id for the current product (for supplier access check)
     */
    protected function getProductBrandId(int|string $productId): ?int
    {
        $stmt = $this->db->prepare('SELECT brand_id FROM products WHERE id = ?');
        $stmt->execute([$productId]);
        $result = $stmt->fetch();
        return $result ? (int) $result['brand_id'] : null;
    }

    /**
     * Get brand_id for the current batch (via purchase_orders)
     */
    protected function getBatchBrandId(int|string $batchId): ?int
    {
        $stmt = $this->db->prepare(
            'SELECT po.brand_id FROM batches b
             JOIN purchase_orders po ON b.purchase_order_id = po.id
             WHERE b.id = ?'
        );
        $stmt->execute([$batchId]);
        $result = $stmt->fetch();
        return $result ? (int) $result['brand_id'] : null;
    }

    /**
     * Check if supplier can access a batch (via purchase_orders)
     */
    protected function canAccessBatchAsSupplier(int|string $batchId): bool
    {
        if (!TenantContext::isSupplier()) {
            return false;
        }

        $stmt = $this->db->prepare(
            'SELECT b.id FROM batches b
             JOIN purchase_orders po ON b.purchase_order_id = po.id
             WHERE b.id = ? AND po.supplier_id = ?'
        );
        $stmt->execute([$batchId, TenantContext::getSupplierId()]);
        return (bool) $stmt->fetch();
    }

    /**
     * Verify purchase order belongs to current brand
     */
    protected function verifyPurchaseOrderOwnership(int|string $poId): bool
    {
        if (!TenantContext::isBrand()) {
            return false;
        }

        $stmt = $this->db->prepare(
            'SELECT id FROM purchase_orders WHERE id = ? AND brand_id = ?'
        );
        $stmt->execute([$poId, TenantContext::getBrandId()]);
        return (bool) $stmt->fetch();
    }

    /**
     * Check if supplier can access a purchase order (is recipient)
     */
    protected function canAccessPurchaseOrderAsSupplier(int|string $poId): bool
    {
        if (!TenantContext::isSupplier()) {
            return false;
        }

        $stmt = $this->db->prepare(
            'SELECT id FROM purchase_orders WHERE id = ? AND supplier_id = ?'
        );
        $stmt->execute([$poId, TenantContext::getSupplierId()]);
        return (bool) $stmt->fetch();
    }
}
