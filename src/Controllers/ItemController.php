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
            'SELECT i.*, b.batch_number, pv.sku, pv.size, pv.color_name, p.product_name
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

    public function showBySerial(array $params): void {
        $stmt = $this->db->prepare(
            'SELECT i.*, b.batch_number, pv.sku, pv.size, pv.color_name, p.product_name,
                    br.brand_name, c.name as company_name
             FROM items i
             LEFT JOIN batches b ON i.batch_id = b.id
             LEFT JOIN product_variants pv ON b.product_variant_id = pv.id
             LEFT JOIN products p ON pv.product_id = p.id
             LEFT JOIN brands br ON p.brand_id = br.id
             LEFT JOIN companies c ON br.company_id = c.id
             WHERE i.serial_number = ?'
        );
        $stmt->execute([$params['serial']]);
        $item = $stmt->fetch();

        if (!$item) {
            Response::error('Item not found', 404);
        }
        Response::success($item);
    }

    public function create(array $params): void {
        $data = Validator::getJsonBody();

        // Verify batch exists
        $stmt = $this->db->prepare('SELECT id FROM batches WHERE id = ?');
        $stmt->execute([$params['batchId']]);
        if (!$stmt->fetch()) {
            Response::error('Batch not found', 404);
        }

        // Generate serial number if not provided
        $serialNumber = $data['serial_number'] ?? $this->generateSerialNumber();

        // Check for duplicate serial_number
        $stmt = $this->db->prepare('SELECT id FROM items WHERE serial_number = ?');
        $stmt->execute([$serialNumber]);
        if ($stmt->fetch()) {
            Response::error('Serial number already exists', 400);
        }

        $stmt = $this->db->prepare(
            'INSERT INTO items (
                batch_id, serial_number, data_carrier_type,
                data_carrier_material, data_carrier_location
             ) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $params['batchId'],
            $serialNumber,
            $data['data_carrier_type'] ?? null,
            $data['data_carrier_material'] ?? null,
            $data['data_carrier_location'] ?? null
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

        // Verify batch exists
        $stmt = $this->db->prepare('SELECT id FROM batches WHERE id = ?');
        $stmt->execute([$params['batchId']]);
        if (!$stmt->fetch()) {
            Response::error('Batch not found', 404);
        }

        $prefix = $data['prefix'] ?? 'DPP';
        $dataCarrierType = $data['data_carrier_type'] ?? null;
        $dataCarrierMaterial = $data['data_carrier_material'] ?? null;
        $dataCarrierLocation = $data['data_carrier_location'] ?? null;

        $createdIds = [];
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                'INSERT INTO items (batch_id, serial_number, data_carrier_type, data_carrier_material, data_carrier_location)
                 VALUES (?, ?, ?, ?, ?)'
            );

            for ($i = 0; $i < $quantity; $i++) {
                $serialNumber = $this->generateSerialNumber($prefix);
                $stmt->execute([
                    $params['batchId'],
                    $serialNumber,
                    $dataCarrierType,
                    $dataCarrierMaterial,
                    $dataCarrierLocation
                ]);
                $createdIds[] = [
                    'id' => (int)$this->db->lastInsertId(),
                    'serial_number' => $serialNumber
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

    private function generateSerialNumber(string $prefix = 'DPP'): string {
        return $prefix . '-' . strtoupper(bin2hex(random_bytes(4))) . '-' . time();
    }
}
