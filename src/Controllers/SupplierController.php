<?php
namespace App\Controllers;

use App\Config\Database;
use App\Helpers\Response;
use App\Helpers\Validator;

class SupplierController {
    private \PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function index(array $params): void {
        $sql = 'SELECT s.*, c.name as company_name
                FROM suppliers s
                LEFT JOIN companies c ON s.company_id = c.id
                ORDER BY s.created_at DESC';
        $stmt = $this->db->query($sql);
        Response::success($stmt->fetchAll());
    }

    public function show(array $params): void {
        $stmt = $this->db->prepare(
            'SELECT s.*, c.name as company_name
             FROM suppliers s
             LEFT JOIN companies c ON s.company_id = c.id
             WHERE s.id = ?'
        );
        $stmt->execute([$params['id']]);
        $supplier = $stmt->fetch();

        if (!$supplier) {
            Response::error('Supplier not found', 404);
        }
        Response::success($supplier);
    }

    public function create(array $params): void {
        $data = Validator::getJsonBody();

        if ($error = Validator::required($data, ['company_id', 'supplier_name'])) {
            Response::error($error);
        }

        // Verify company exists
        $stmt = $this->db->prepare('SELECT id FROM companies WHERE id = ?');
        $stmt->execute([$data['company_id']]);
        if (!$stmt->fetch()) {
            Response::error('Company not found', 404);
        }

        $stmt = $this->db->prepare(
            'INSERT INTO suppliers (
                company_id, supplier_name, supplier_location, facility_registry,
                facility_identifier, operator_registry, operator_identifier
             ) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['company_id'],
            $data['supplier_name'],
            $data['supplier_location'] ?? null,
            $data['facility_registry'] ?? null,
            $data['facility_identifier'] ?? null,
            $data['operator_registry'] ?? null,
            $data['operator_identifier'] ?? null
        ]);

        $id = $this->db->lastInsertId();
        $this->show(['id' => $id]);
    }

    public function update(array $params): void {
        $data = Validator::getJsonBody();

        $stmt = $this->db->prepare('SELECT id FROM suppliers WHERE id = ?');
        $stmt->execute([$params['id']]);
        if (!$stmt->fetch()) {
            Response::error('Supplier not found', 404);
        }

        $stmt = $this->db->prepare(
            'UPDATE suppliers SET
                supplier_name = COALESCE(?, supplier_name),
                supplier_location = COALESCE(?, supplier_location),
                facility_registry = COALESCE(?, facility_registry),
                facility_identifier = COALESCE(?, facility_identifier),
                operator_registry = COALESCE(?, operator_registry),
                operator_identifier = COALESCE(?, operator_identifier)
             WHERE id = ?'
        );
        $stmt->execute([
            $data['supplier_name'] ?? null,
            $data['supplier_location'] ?? null,
            $data['facility_registry'] ?? null,
            $data['facility_identifier'] ?? null,
            $data['operator_registry'] ?? null,
            $data['operator_identifier'] ?? null,
            $params['id']
        ]);

        $this->show($params);
    }

    public function delete(array $params): void {
        $stmt = $this->db->prepare('SELECT id FROM suppliers WHERE id = ?');
        $stmt->execute([$params['id']]);
        if (!$stmt->fetch()) {
            Response::error('Supplier not found', 404);
        }

        $stmt = $this->db->prepare('DELETE FROM suppliers WHERE id = ?');
        $stmt->execute([$params['id']]);

        Response::success(['deleted' => (int)$params['id']]);
    }
}
