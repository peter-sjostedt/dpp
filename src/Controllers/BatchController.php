<?php
namespace App\Controllers;

use App\Config\TenantContext;
use App\Helpers\Response;
use App\Helpers\Validator;

/**
 * Batch controller with multi-tenant access control.
 * - As Brand: Full CRUD on own batches (filtered by brand_id)
 * - As Supplier: Read-only access to batches using their materials
 */
class BatchController extends TenantAwareController
{
    // ========== Batches CRUD ==========

    public function index(array $params): void
    {
        if (TenantContext::isBrand()) {
            $brandId = TenantContext::getBrandId();

            // Batches belonging to brand that have items
            $stmt = $this->db->prepare(
                'SELECT DISTINCT b.*, pv.sku, pv.size, pv.color_name
                 FROM batches b
                 LEFT JOIN product_variants pv ON b.product_variant_id = pv.id
                 JOIN items i ON i.batch_id = b.id
                 WHERE b.product_variant_id = ? AND b.brand_id = ?
                 ORDER BY b.production_date DESC'
            );
            $stmt->execute([$params['variantId'], $brandId]);
        } else {
            // Supplier sees batches that use their materials
            $stmt = $this->db->prepare(
                'SELECT DISTINCT b.*, pv.sku, pv.size, pv.color_name
                 FROM batches b
                 LEFT JOIN product_variants pv ON b.product_variant_id = pv.id
                 JOIN batch_materials bm ON bm.batch_id = b.id
                 JOIN factory_materials fm ON bm.factory_material_id = fm.id
                 WHERE b.product_variant_id = ? AND fm.supplier_id = ?
                 ORDER BY b.production_date DESC'
            );
            $stmt->execute([$params['variantId'], TenantContext::getSupplierId()]);
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
            // Supplier can view batches that use their materials
            if (!$this->canAccessBatchAsSupplier($batchId)) {
                Response::error('Batch not found', 404);
                return;
            }
        }

        $stmt = $this->db->prepare(
            'SELECT b.*, pv.sku, pv.size, pv.color_name, p.product_name
             FROM batches b
             LEFT JOIN product_variants pv ON b.product_variant_id = pv.id
             LEFT JOIN products p ON pv.product_id = p.id
             WHERE b.id = ?'
        );
        $stmt->execute([$batchId]);
        $batch = $stmt->fetch();

        if (!$batch) {
            Response::error('Batch not found', 404);
            return;
        }

        // Include linked suppliers
        $batch['suppliers'] = $this->getBatchSuppliers($batchId);

        Response::success($batch);
    }

    public function create(array $params): void
    {
        $this->requireBrand();

        $brandId = TenantContext::getBrandId();
        $data = Validator::getJsonBody();

        if ($error = Validator::required($data, ['batch_number'])) {
            Response::error($error);
            return;
        }

        // Verify variant exists and belongs to brand (via product)
        $stmt = $this->db->prepare(
            'SELECT pv.id FROM product_variants pv
             JOIN products p ON pv.product_id = p.id
             WHERE pv.id = ? AND p.brand_id = ?'
        );
        $stmt->execute([$params['variantId'], $brandId]);
        if (!$stmt->fetch()) {
            Response::error('Variant not found', 404);
            return;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO batches (brand_id, product_variant_id, batch_number, po_number, quantity, production_date, _status)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $brandId,
            $params['variantId'],
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

        $stmt = $this->db->prepare(
            'UPDATE batches SET
                batch_number = COALESCE(?, batch_number),
                po_number = COALESCE(?, po_number),
                quantity = COALESCE(?, quantity),
                production_date = COALESCE(?, production_date),
                _status = COALESCE(?, _status)
             WHERE id = ?'
        );
        $stmt->execute([
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

    // ========== Batch Suppliers ==========

    private function getBatchSuppliers(int|string $batchId): array
    {
        $stmt = $this->db->prepare(
            'SELECT bs.*, s.supplier_name, s.supplier_location
             FROM batch_suppliers bs
             LEFT JOIN suppliers s ON bs.supplier_id = s.id
             WHERE bs.batch_id = ?
             ORDER BY bs.production_stage'
        );
        $stmt->execute([$batchId]);
        return $stmt->fetchAll();
    }

    public function listSuppliers(array $params): void
    {
        $batchId = (int) $params['batchId'];

        if (TenantContext::isBrand()) {
            if (!$this->verifyBatchOwnership($batchId)) {
                Response::error('Batch not found', 404);
                return;
            }
        } else {
            if (!$this->canAccessBatchAsSupplier($batchId)) {
                Response::error('Batch not found', 404);
                return;
            }
        }

        Response::success($this->getBatchSuppliers($batchId));
    }

    public function addSupplier(array $params): void
    {
        $this->requireBrand();

        $batchId = (int) $params['batchId'];
        $data = Validator::getJsonBody();

        if ($error = Validator::required($data, ['supplier_id', 'production_stage'])) {
            Response::error($error);
            return;
        }

        // Verify batch belongs to brand
        if (!$this->verifyBatchOwnership($batchId)) {
            Response::error('Batch not found', 404);
            return;
        }

        // Verify supplier is accessible (brand has relationship with this supplier)
        if (!$this->canAccessSupplier($data['supplier_id'])) {
            Response::error('Supplier not found', 404);
            return;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO batch_suppliers (batch_id, supplier_id, production_stage, country_of_origin) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $batchId,
            $data['supplier_id'],
            $data['production_stage'],
            $data['country_of_origin'] ?? null
        ]);

        Response::success(['id' => (int)$this->db->lastInsertId()], 201);
    }

    public function removeSupplier(array $params): void
    {
        $this->requireBrand();

        $linkId = (int) $params['id'];

        // Verify the batch_supplier link belongs to a batch owned by this brand
        $stmt = $this->db->prepare(
            'SELECT bs.id FROM batch_suppliers bs
             JOIN batches b ON bs.batch_id = b.id
             WHERE bs.id = ? AND b.brand_id = ?'
        );
        $stmt->execute([$linkId, TenantContext::getBrandId()]);
        if (!$stmt->fetch()) {
            Response::error('Batch supplier link not found', 404);
            return;
        }

        $stmt = $this->db->prepare('DELETE FROM batch_suppliers WHERE id = ?');
        $stmt->execute([$linkId]);

        Response::success(['deleted' => $linkId]);
    }
}
