<?php
namespace App\Controllers;

use App\Config\Database;
use App\Helpers\Response;
use App\Helpers\Validator;

class ItemController {
    private \PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function index(array $params): void {
        $stmt = $this->db->prepare(
            'SELECT i.*, b.batch_number
             FROM items i
             LEFT JOIN batches b ON i.batch_id = b.id
             WHERE i.batch_id = ?
             ORDER BY i.created_at DESC'
        );
        $stmt->execute([$params['batchId']]);
        Response::success($stmt->fetchAll());
    }

    public function show(array $params): void {
        $stmt = $this->db->prepare(
            'SELECT i.*, b.batch_number, pv.sku, pv.size, pv.color_name, p.product_name,
                    p.data_carrier_type, p.data_carrier_material, p.data_carrier_location
             FROM items i
             LEFT JOIN batches b ON i.batch_id = b.id
             LEFT JOIN product_variants pv ON b.product_variant_id = pv.id
             LEFT JOIN products p ON pv.product_id = p.id
             WHERE i.id = ?'
        );
        $stmt->execute([$params['id']]);
        $item = $stmt->fetch();

        if (!$item) {
            Response::error('Item not found', 404);
        }
        Response::success($item);
    }

    public function showBySgtin(array $params): void {
        $stmt = $this->db->prepare(
            'SELECT i.*, b.batch_number, pv.sku, pv.size, pv.color_name, p.product_name,
                    p.data_carrier_type, p.data_carrier_material, p.data_carrier_location,
                    br.brand_name, c.name as company_name
             FROM items i
             LEFT JOIN batches b ON i.batch_id = b.id
             LEFT JOIN product_variants pv ON b.product_variant_id = pv.id
             LEFT JOIN products p ON pv.product_id = p.id
             LEFT JOIN brands br ON p.brand_id = br.id
             LEFT JOIN companies c ON br.company_id = c.id
             WHERE i.sgtin = ?'
        );
        $stmt->execute([$params['sgtin']]);
        $item = $stmt->fetch();

        if (!$item) {
            Response::error('Item not found', 404);
        }
        Response::success($item);
    }

    public function create(array $params): void {
        $data = Validator::getJsonBody();

        // Verify batch exists and get product GTIN
        $stmt = $this->db->prepare(
            'SELECT b.id, p.gtin
             FROM batches b
             JOIN product_variants pv ON b.product_variant_id = pv.id
             JOIN products p ON pv.product_id = p.id
             WHERE b.id = ?'
        );
        $stmt->execute([$params['batchId']]);
        $batch = $stmt->fetch();
        if (!$batch) {
            Response::error('Batch not found', 404);
        }

        // Generate sgtin if not provided
        $sgtin = $data['sgtin'] ?? $this->generateSgtin($batch['gtin']);

        // Check for duplicate sgtin
        $stmt = $this->db->prepare('SELECT id FROM items WHERE sgtin = ?');
        $stmt->execute([$sgtin]);
        if ($stmt->fetch()) {
            Response::error('SGTIN already exists', 400);
        }

        $stmt = $this->db->prepare(
            'INSERT INTO items (batch_id, product_variant_id, tid, sgtin)
             SELECT ?, product_variant_id, ?, ? FROM batches WHERE id = ?'
        );
        $stmt->execute([
            $params['batchId'],
            $data['tid'] ?? null,
            $sgtin,
            $params['batchId']
        ]);

        $id = $this->db->lastInsertId();
        $this->show(['id' => $id]);
    }

    public function createBulk(array $params): void {
        $data = Validator::getJsonBody();

        if ($error = Validator::required($data, ['quantity'])) {
            Response::error($error);
        }

        $quantity = (int)$data['quantity'];
        if ($quantity < 1 || $quantity > 1000) {
            Response::error('Quantity must be between 1 and 1000', 400);
        }

        // Verify batch exists and get product GTIN + variant id
        $stmt = $this->db->prepare(
            'SELECT b.id, b.product_variant_id, p.gtin
             FROM batches b
             JOIN product_variants pv ON b.product_variant_id = pv.id
             JOIN products p ON pv.product_id = p.id
             WHERE b.id = ?'
        );
        $stmt->execute([$params['batchId']]);
        $batch = $stmt->fetch();
        if (!$batch) {
            Response::error('Batch not found', 404);
        }

        $prefix = $data['prefix'] ?? 'DPP';

        $createdIds = [];
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                'INSERT INTO items (batch_id, product_variant_id, sgtin) VALUES (?, ?, ?)'
            );

            for ($i = 0; $i < $quantity; $i++) {
                $sgtin = $this->generateSgtin($batch['gtin']);
                $stmt->execute([
                    $params['batchId'],
                    $batch['product_variant_id'],
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

    public function delete(array $params): void {
        $stmt = $this->db->prepare('SELECT id FROM items WHERE id = ?');
        $stmt->execute([$params['id']]);
        if (!$stmt->fetch()) {
            Response::error('Item not found', 404);
        }

        $stmt = $this->db->prepare('DELETE FROM items WHERE id = ?');
        $stmt->execute([$params['id']]);

        Response::success(['deleted' => (int)$params['id']]);
    }

    private function generateSgtin(string $gtin): string {
        // Format: GTIN.NNNNNN (6-digit serial)
        $serial = str_pad((string)random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        return $gtin . '.' . $serial;
    }
}
