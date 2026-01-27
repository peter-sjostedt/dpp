<?php
namespace App\Controllers;

use App\Config\TenantContext;
use App\Helpers\Response;
use App\Helpers\Validator;

/**
 * Batch controller with multi-tenant access control.
 *
 * Schema: batches has brand_id, supplier_id, product_id
 * - supplier_id = the confection/production supplier
 * - product_id = which product this batch produces
 *
 * Access:
 * - As Brand: Full CRUD on own batches (filtered by brand_id)
 * - As Supplier: Read-only access to batches where they are the supplier
 */
class BatchController extends TenantAwareController
{
    // ========== Batches CRUD ==========

    /**
     * List batches for a product
     */
    public function index(array $params): void
    {
        $productId = (int) $params['productId'];

        if (TenantContext::isBrand()) {
            $brandId = TenantContext::getBrandId();

            // Verify product belongs to brand
            if (!$this->verifyProductOwnership($productId)) {
                Response::error('Product not found', 404);
                return;
            }

            $stmt = $this->db->prepare(
                'SELECT b.*, s.supplier_name, p.product_name
                 FROM batches b
                 LEFT JOIN suppliers s ON b.supplier_id = s.id
                 LEFT JOIN products p ON b.product_id = p.id
                 WHERE b.product_id = ? AND b.brand_id = ?
                 ORDER BY b.production_date DESC'
            );
            $stmt->execute([$productId, $brandId]);
        } else {
            // Supplier sees batches where they are the confection supplier
            $supplierId = TenantContext::getSupplierId();

            // Check supplier has relationship with product's brand
            $stmt = $this->db->prepare(
                'SELECT p.id FROM products p
                 JOIN brand_suppliers bs ON p.brand_id = bs.brand_id
                 WHERE p.id = ? AND bs.supplier_id = ? AND bs._is_active = TRUE'
            );
            $stmt->execute([$productId, $supplierId]);
            if (!$stmt->fetch()) {
                Response::error('Product not found', 404);
                return;
            }

            $stmt = $this->db->prepare(
                'SELECT b.*, s.supplier_name, p.product_name
                 FROM batches b
                 LEFT JOIN suppliers s ON b.supplier_id = s.id
                 LEFT JOIN products p ON b.product_id = p.id
                 WHERE b.product_id = ? AND b.supplier_id = ?
                 ORDER BY b.production_date DESC'
            );
            $stmt->execute([$productId, $supplierId]);
        }

        Response::success($stmt->fetchAll());
    }

    /**
     * List all batches (without product filter)
     */
    public function indexAll(array $params): void
    {
        if (TenantContext::isBrand()) {
            $brandId = TenantContext::getBrandId();

            $stmt = $this->db->prepare(
                'SELECT b.*, s.supplier_name, p.product_name
                 FROM batches b
                 LEFT JOIN suppliers s ON b.supplier_id = s.id
                 LEFT JOIN products p ON b.product_id = p.id
                 WHERE b.brand_id = ?
                 ORDER BY b.production_date DESC'
            );
            $stmt->execute([$brandId]);
        } else {
            // Supplier sees batches where they are the confection supplier
            $supplierId = TenantContext::getSupplierId();

            $stmt = $this->db->prepare(
                'SELECT b.*, s.supplier_name, p.product_name
                 FROM batches b
                 LEFT JOIN suppliers s ON b.supplier_id = s.id
                 LEFT JOIN products p ON b.product_id = p.id
                 WHERE b.supplier_id = ?
                 ORDER BY b.production_date DESC'
            );
            $stmt->execute([$supplierId]);
        }

        Response::success($stmt->fetchAll());
    }

    public function show(array $params): void
    {
        $batchId = (int) $params['id'];

        if (TenantContext::isBrand()) {
            if (!$this->verifyBatchOwnership($batchId)) {
                Response::error('Batch not found', 404);
                return;
            }
        } else {
            // Supplier can view batches where they are the supplier
            $stmt = $this->db->prepare(
                'SELECT id FROM batches WHERE id = ? AND supplier_id = ?'
            );
            $stmt->execute([$batchId, TenantContext::getSupplierId()]);
            if (!$stmt->fetch()) {
                Response::error('Batch not found', 404);
                return;
            }
        }

        $stmt = $this->db->prepare(
            'SELECT b.*, s.supplier_name, s.supplier_location, p.product_name
             FROM batches b
             LEFT JOIN suppliers s ON b.supplier_id = s.id
             LEFT JOIN products p ON b.product_id = p.id
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

    public function create(array $params): void
    {
        $this->requireBrand();

        $brandId = TenantContext::getBrandId();
        $productId = (int) $params['productId'];
        $data = Validator::getJsonBody();

        if ($error = Validator::required($data, ['batch_number', 'supplier_id'])) {
            Response::error($error);
            return;
        }

        // Verify product belongs to brand
        if (!$this->verifyProductOwnership($productId)) {
            Response::error('Product not found', 404);
            return;
        }

        // Verify supplier is accessible (brand has relationship with this supplier)
        if (!$this->canAccessSupplier($data['supplier_id'])) {
            Response::error('Supplier not found', 404);
            return;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO batches (brand_id, supplier_id, product_id, batch_number, po_number, quantity, production_date, _status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $brandId,
            $data['supplier_id'],
            $productId,
            $data['batch_number'],
            $data['po_number'] ?? null,
            $data['quantity'] ?? null,
            $data['production_date'] ?? null,
            $data['status'] ?? 'planned'
        ]);

        $id = $this->db->lastInsertId();
        $this->show(['id' => $id]);
    }

    public function update(array $params): void
    {
        $this->requireBrand();

        $batchId = (int) $params['id'];

        if (!$this->verifyBatchOwnership($batchId)) {
            Response::error('Batch not found', 404);
            return;
        }

        $data = Validator::getJsonBody();

        // If changing supplier, verify new supplier is accessible
        if (isset($data['supplier_id']) && !$this->canAccessSupplier($data['supplier_id'])) {
            Response::error('Supplier not found', 404);
            return;
        }

        $stmt = $this->db->prepare(
            'UPDATE batches SET
                supplier_id = COALESCE(?, supplier_id),
                batch_number = COALESCE(?, batch_number),
                po_number = COALESCE(?, po_number),
                quantity = COALESCE(?, quantity),
                production_date = COALESCE(?, production_date),
                _status = COALESCE(?, _status)
             WHERE id = ?'
        );
        $stmt->execute([
            $data['supplier_id'] ?? null,
            $data['batch_number'] ?? null,
            $data['po_number'] ?? null,
            $data['quantity'] ?? null,
            $data['production_date'] ?? null,
            $data['status'] ?? null,
            $batchId
        ]);

        $this->show(['id' => $batchId]);
    }

    public function delete(array $params): void
    {
        $this->requireBrand();

        $batchId = (int) $params['id'];

        if (!$this->verifyBatchOwnership($batchId)) {
            Response::error('Batch not found', 404);
            return;
        }

        $stmt = $this->db->prepare('DELETE FROM batches WHERE id = ?');
        $stmt->execute([$batchId]);

        Response::success(['deleted' => $batchId]);
    }
}
