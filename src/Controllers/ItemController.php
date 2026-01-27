<?php
namespace App\Controllers;

use App\Config\TenantContext;
use App\Helpers\Response;
use App\Helpers\Validator;

/**
 * Item controller with multi-tenant access control.
 * - As Brand: Full CRUD on own items (filtered via batch.brand_id)
 * - As Supplier: No access to items
 */
class ItemController extends TenantAwareController
{
    public function index(array $params): void
    {
        $this->requireBrand();

        $brandId = TenantContext::getBrandId();
        $batchId = (int) $params['batchId'];

        // Verify batch belongs to brand
        if (!$this->verifyBatchOwnership($batchId)) {
            Response::error('Batch not found', 404);
            return;
        }

        $stmt = $this->db->prepare(
            'SELECT i.*, b.batch_number
             FROM items i
             JOIN batches b ON i.batch_id = b.id
             WHERE i.batch_id = ? AND b.brand_id = ?
             ORDER BY i.created_at DESC'
        );
        $stmt->execute([$batchId, $brandId]);
        Response::success($stmt->fetchAll());
    }

    public function show(array $params): void
    {
        $this->requireBrand();

        $itemId = (int) $params['id'];

        if (!$this->verifyItemOwnership($itemId)) {
            Response::error('Item not found', 404);
            return;
        }

        $stmt = $this->db->prepare(
            'SELECT i.*, b.batch_number, pv.gtin as variant_gtin, pv.size, pv.color_brand as color_name, p.product_name,
                    p.data_carrier_type, p.data_carrier_material, p.data_carrier_location
             FROM items i
             LEFT JOIN batches b ON i.batch_id = b.id
             LEFT JOIN products p ON b.product_id = p.id
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

    public function showBySgtin(array $params): void
    {
        $this->requireBrand();

        $brandId = TenantContext::getBrandId();

        $stmt = $this->db->prepare(
            'SELECT i.*, b.batch_number, pv.gtin as variant_gtin, pv.size, pv.color_brand as color_name, p.product_name,
                    p.data_carrier_type, p.data_carrier_material, p.data_carrier_location,
                    br.brand_name
             FROM items i
             JOIN batches b ON i.batch_id = b.id
             JOIN products p ON b.product_id = p.id
             LEFT JOIN product_variants pv ON i.product_variant_id = pv.id
             JOIN brands br ON p.brand_id = br.id
             WHERE i.sgtin = ? AND b.brand_id = ?'
        );
        $stmt->execute([$params['sgtin'], $brandId]);
        $item = $stmt->fetch();

        if (!$item) {
            Response::error('Item not found', 404);
            return;
        }

        Response::success($item);
    }

    public function create(array $params): void
    {
        $this->requireBrand();

        $brandId = TenantContext::getBrandId();
        $batchId = (int) $params['batchId'];
        $data = Validator::getJsonBody();

        // Verify batch exists and belongs to brand, get product info for SGTIN base
        $stmt = $this->db->prepare(
            'SELECT b.id, COALESCE(p.gtin, p.id) as product_code
             FROM batches b
             JOIN products p ON b.product_id = p.id
             WHERE b.id = ? AND b.brand_id = ?'
        );
        $stmt->execute([$batchId, $brandId]);
        $batch = $stmt->fetch();
        if (!$batch) {
            Response::error('Batch not found', 404);
            return;
        }

        // Generate sgtin if not provided
        $sgtin = $data['sgtin'] ?? $this->generateSgtin($batch['product_code']);

        // Check for duplicate sgtin
        $stmt = $this->db->prepare('SELECT id FROM items WHERE sgtin = ?');
        $stmt->execute([$sgtin]);
        if ($stmt->fetch()) {
            Response::error('SGTIN already exists', 400);
            return;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO items (batch_id, tid, sgtin) VALUES (?, ?, ?)'
        );
        $stmt->execute([
            $batchId,
            $data['tid'] ?? null,
            $sgtin
        ]);

        $id = $this->db->lastInsertId();
        $this->show(['id' => $id]);
    }

    public function createBulk(array $params): void
    {
        $this->requireBrand();

        $brandId = TenantContext::getBrandId();
        $batchId = (int) $params['batchId'];
        $data = Validator::getJsonBody();

        if ($error = Validator::required($data, ['quantity'])) {
            Response::error($error);
            return;
        }

        $quantity = (int)$data['quantity'];
        if ($quantity < 1 || $quantity > 1000) {
            Response::error('Quantity must be between 1 and 1000', 400);
            return;
        }

        // Verify batch exists and belongs to brand
        $stmt = $this->db->prepare(
            'SELECT b.id, COALESCE(p.gtin, p.id) as product_code
             FROM batches b
             JOIN products p ON b.product_id = p.id
             WHERE b.id = ? AND b.brand_id = ?'
        );
        $stmt->execute([$batchId, $brandId]);
        $batch = $stmt->fetch();
        if (!$batch) {
            Response::error('Batch not found', 404);
            return;
        }

        $createdIds = [];
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                'INSERT INTO items (batch_id, sgtin) VALUES (?, ?)'
            );

            for ($i = 0; $i < $quantity; $i++) {
                $sgtin = $this->generateSgtin($batch['product_code']);
                $stmt->execute([
                    $batchId,
                    $sgtin
                ]);
                $createdIds[] = [
                    'id' => (int)$this->db->lastInsertId(),
                    'sgtin' => $sgtin
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

    public function delete(array $params): void
    {
        $this->requireBrand();

        $itemId = (int) $params['id'];

        if (!$this->verifyItemOwnership($itemId)) {
            Response::error('Item not found', 404);
            return;
        }

        $stmt = $this->db->prepare('DELETE FROM items WHERE id = ?');
        $stmt->execute([$itemId]);

        Response::success(['deleted' => $itemId]);
    }

    private function generateSgtin(string $gtin): string
    {
        // Format: GTIN.NNNNNN (6-digit serial)
        $serial = str_pad((string)random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        return $gtin . '.' . $serial;
    }
}
