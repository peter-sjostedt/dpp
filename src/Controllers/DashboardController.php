<?php
namespace App\Controllers;

use App\Config\TenantContext;
use App\Helpers\Response;

/**
 * Dashboard summary controller with role-based metrics.
 *
 * Brand sees: pending orders, completed batches, incomplete products, export ready, expiring certs
 * Supplier sees: pending orders, batches without materials, batches without items, incomplete materials, expiring certs
 */
class DashboardController extends TenantAwareController
{
    public function summary(array $params): void
    {
        if (TenantContext::isBrand()) {
            $this->brandSummary();
        } elseif (TenantContext::isSupplier()) {
            $this->supplierSummary();
        } else {
            Response::error('Unauthorized', 403);
        }
    }

    private function brandSummary(): void
    {
        $brandId = TenantContext::getBrandId();

        // pending_orders: POs with status rejected or sent that haven't been handled
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as cnt FROM purchase_orders
             WHERE brand_id = ? AND _status IN ('sent', 'rejected')"
        );
        $stmt->execute([$brandId]);
        $pendingOrders = (int) $stmt->fetch()['cnt'];

        // completed_batches: batches with status completed (brand's POs)
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as cnt FROM batches b
             JOIN purchase_orders po ON b.purchase_order_id = po.id
             WHERE po.brand_id = ? AND b._status = 'completed'"
        );
        $stmt->execute([$brandId]);
        $completedBatches = (int) $stmt->fetch()['cnt'];

        // incomplete_products: products missing care_information, compliance_info,
        // product_components or product_variants
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) as cnt FROM products p
             WHERE p.brand_id = ? AND p._is_active = TRUE AND (
                 NOT EXISTS (SELECT 1 FROM care_information ci WHERE ci.product_id = p.id)
                 OR NOT EXISTS (SELECT 1 FROM compliance_info co WHERE co.product_id = p.id)
                 OR NOT EXISTS (SELECT 1 FROM product_components pc WHERE pc.product_id = p.id)
                 OR NOT EXISTS (SELECT 1 FROM product_variants pv WHERE pv.product_id = p.id)
             )'
        );
        $stmt->execute([$brandId]);
        $incompleteProducts = (int) $stmt->fetch()['cnt'];

        // export_ready: products with complete data that don't have dpp_exports
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) as cnt FROM products p
             WHERE p.brand_id = ? AND p._is_active = TRUE
             AND EXISTS (SELECT 1 FROM care_information ci WHERE ci.product_id = p.id)
             AND EXISTS (SELECT 1 FROM compliance_info co WHERE co.product_id = p.id)
             AND EXISTS (SELECT 1 FROM product_components pc WHERE pc.product_id = p.id)
             AND EXISTS (SELECT 1 FROM product_variants pv WHERE pv.product_id = p.id)
             AND NOT EXISTS (SELECT 1 FROM dpp_exports de WHERE de.product_id = p.id)'
        );
        $stmt->execute([$brandId]);
        $exportReady = (int) $stmt->fetch()['cnt'];

        // expiring_certifications: factory_material_certifications with valid_until
        // within 30 days (via supplier → brand_suppliers)
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) as cnt FROM factory_material_certifications fmc
             JOIN factory_materials fm ON fmc.factory_material_id = fm.id
             JOIN brand_suppliers bs ON fm.supplier_id = bs.supplier_id
             WHERE bs.brand_id = ? AND bs._is_active = TRUE
             AND fmc.valid_until IS NOT NULL
             AND fmc.valid_until <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)'
        );
        $stmt->execute([$brandId]);
        $expiringCerts = (int) $stmt->fetch()['cnt'];

        Response::success([
            'pending_orders' => $pendingOrders,
            'completed_batches' => $completedBatches,
            'incomplete_products' => $incompleteProducts,
            'export_ready' => $exportReady,
            'expiring_certifications' => $expiringCerts,
        ]);
    }

    private function supplierSummary(): void
    {
        $supplierId = TenantContext::getSupplierId();

        // pending_orders: POs with status sent (supplier's)
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as cnt FROM purchase_orders
             WHERE supplier_id = ? AND _status = 'sent'"
        );
        $stmt->execute([$supplierId]);
        $pendingOrders = (int) $stmt->fetch()['cnt'];

        // batches_without_materials: batches in_production without batch_materials
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as cnt FROM batches b
             JOIN purchase_orders po ON b.purchase_order_id = po.id
             WHERE po.supplier_id = ? AND b._status = 'in_production'
             AND NOT EXISTS (SELECT 1 FROM batch_materials bm WHERE bm.batch_id = b.id)"
        );
        $stmt->execute([$supplierId]);
        $batchesWithoutMaterials = (int) $stmt->fetch()['cnt'];

        // batches_without_items: batches in_production with 0 items
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as cnt FROM batches b
             JOIN purchase_orders po ON b.purchase_order_id = po.id
             WHERE po.supplier_id = ? AND b._status = 'in_production'
             AND NOT EXISTS (SELECT 1 FROM items i WHERE i.batch_id = b.id)"
        );
        $stmt->execute([$supplierId]);
        $batchesWithoutItems = (int) $stmt->fetch()['cnt'];

        // incomplete_materials: factory_materials without compositions
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) as cnt FROM factory_materials fm
             WHERE fm.supplier_id = ? AND fm._is_active = TRUE
             AND NOT EXISTS (SELECT 1 FROM factory_material_compositions fmc WHERE fmc.factory_material_id = fm.id)'
        );
        $stmt->execute([$supplierId]);
        $incompleteMaterials = (int) $stmt->fetch()['cnt'];

        // expiring_certifications: factory_material_certifications with valid_until
        // within 30 days or already expired
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) as cnt FROM factory_material_certifications fmc
             JOIN factory_materials fm ON fmc.factory_material_id = fm.id
             WHERE fm.supplier_id = ?
             AND fmc.valid_until IS NOT NULL
             AND fmc.valid_until <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)'
        );
        $stmt->execute([$supplierId]);
        $expiringCerts = (int) $stmt->fetch()['cnt'];

        Response::success([
            'pending_orders' => $pendingOrders,
            'batches_without_materials' => $batchesWithoutMaterials,
            'batches_without_items' => $batchesWithoutItems,
            'incomplete_materials' => $incompleteMaterials,
            'expiring_certifications' => $expiringCerts,
        ]);
    }
}
