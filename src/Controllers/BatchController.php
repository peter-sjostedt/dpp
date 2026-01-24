<?php
namespace App\Controllers;

use App\Config\Database;
use App\Helpers\Response;
use App\Helpers\Validator;

class BatchController {
    private \PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // ========== Batches CRUD ==========

    public function index(array $params): void {
        $stmt = $this->db->prepare(
            'SELECT b.*, pv.sku, pv.size, pv.color_name
             FROM batches b
             LEFT JOIN product_variants pv ON b.product_variant_id = pv.id
             WHERE b.product_variant_id = ?
             ORDER BY b.created_at DESC'
        );
        $stmt->execute([$params['variantId']]);
        Response::success($stmt->fetchAll());
    }

    public function show(array $params): void {
        $stmt = $this->db->prepare(
            'SELECT b.*, pv.sku, pv.size, pv.color_name, p.product_name
             FROM batches b
             LEFT JOIN product_variants pv ON b.product_variant_id = pv.id
             LEFT JOIN products p ON pv.product_id = p.id
             WHERE b.id = ?'
        );
        $stmt->execute([$params['id']]);
        $batch = $stmt->fetch();

        if (!$batch) {
            Response::error('Batch not found', 404);
        }

        // Include linked suppliers
        $batch['suppliers'] = $this->getBatchSuppliers($params['id']);

        Response::success($batch);
    }

    public function create(array $params): void {
        $data = Validator::getJsonBody();

        if ($error = Validator::required($data, ['batch_number'])) {
            Response::error($error);
        }

        // Verify variant exists
        $stmt = $this->db->prepare('SELECT id FROM product_variants WHERE id = ?');
        $stmt->execute([$params['variantId']]);
        if (!$stmt->fetch()) {
            Response::error('Variant not found', 404);
        }

        $stmt = $this->db->prepare(
            'INSERT INTO batches (product_variant_id, batch_number, po_number, quantity, production_date)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $params['variantId'],
            $data['batch_number'],
            $data['po_number'] ?? null,
            $data['quantity'] ?? null,
            $data['production_date'] ?? null
        ]);

        $id = $this->db->lastInsertId();
        $this->show(['id' => $id]);
    }

    public function update(array $params): void {
        $data = Validator::getJsonBody();

        $stmt = $this->db->prepare('SELECT id FROM batches WHERE id = ?');
        $stmt->execute([$params['id']]);
        if (!$stmt->fetch()) {
            Response::error('Batch not found', 404);
        }

        $stmt = $this->db->prepare(
            'UPDATE batches SET
                batch_number = COALESCE(?, batch_number),
                po_number = COALESCE(?, po_number),
                quantity = COALESCE(?, quantity),
                production_date = COALESCE(?, production_date)
             WHERE id = ?'
        );
        $stmt->execute([
            $data['batch_number'] ?? null,
            $data['po_number'] ?? null,
            $data['quantity'] ?? null,
            $data['production_date'] ?? null,
            $params['id']
        ]);

        $this->show($params);
    }

    public function delete(array $params): void {
        $stmt = $this->db->prepare('SELECT id FROM batches WHERE id = ?');
        $stmt->execute([$params['id']]);
        if (!$stmt->fetch()) {
            Response::error('Batch not found', 404);
        }

        $stmt = $this->db->prepare('DELETE FROM batches WHERE id = ?');
        $stmt->execute([$params['id']]);

        Response::success(['deleted' => (int)$params['id']]);
    }

    // ========== Batch Suppliers ==========

    private function getBatchSuppliers(int|string $batchId): array {
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

    public function listSuppliers(array $params): void {
        Response::success($this->getBatchSuppliers($params['batchId']));
    }

    public function addSupplier(array $params): void {
        $data = Validator::getJsonBody();

        if ($error = Validator::required($data, ['supplier_id', 'production_stage'])) {
            Response::error($error);
        }

        // Verify batch exists
        $stmt = $this->db->prepare('SELECT id FROM batches WHERE id = ?');
        $stmt->execute([$params['batchId']]);
        if (!$stmt->fetch()) {
            Response::error('Batch not found', 404);
        }

        // Verify supplier exists
        $stmt = $this->db->prepare('SELECT id FROM suppliers WHERE id = ?');
        $stmt->execute([$data['supplier_id']]);
        if (!$stmt->fetch()) {
            Response::error('Supplier not found', 404);
        }

        $stmt = $this->db->prepare(
            'INSERT INTO batch_suppliers (batch_id, supplier_id, production_stage) VALUES (?, ?, ?)'
        );
        $stmt->execute([
            $params['batchId'],
            $data['supplier_id'],
            $data['production_stage']
        ]);

        Response::success(['id' => (int)$this->db->lastInsertId()], 201);
    }

    public function removeSupplier(array $params): void {
        $stmt = $this->db->prepare('SELECT id FROM batch_suppliers WHERE id = ?');
        $stmt->execute([$params['id']]);
        if (!$stmt->fetch()) {
            Response::error('Batch supplier link not found', 404);
        }

        $stmt = $this->db->prepare('DELETE FROM batch_suppliers WHERE id = ?');
        $stmt->execute([$params['id']]);

        Response::success(['deleted' => (int)$params['id']]);
    }
}
