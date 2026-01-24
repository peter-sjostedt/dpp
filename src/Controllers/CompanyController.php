<?php
namespace App\Controllers;

use App\Config\Database;
use App\Helpers\Response;
use App\Helpers\Validator;

class CompanyController {
    private \PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function index(array $params): void {
        $stmt = $this->db->query('SELECT * FROM companies ORDER BY created_at DESC');
        Response::success($stmt->fetchAll());
    }

    public function show(array $params): void {
        $stmt = $this->db->prepare('SELECT * FROM companies WHERE id = ?');
        $stmt->execute([$params['id']]);
        $company = $stmt->fetch();

        if (!$company) {
            Response::error('Company not found', 404);
        }
        Response::success($company);
    }

    public function create(array $params): void {
        $data = Validator::getJsonBody();

        if ($error = Validator::required($data, ['name'])) {
            Response::error($error);
        }

        $stmt = $this->db->prepare(
            'INSERT INTO companies (name, org_number) VALUES (?, ?)'
        );
        $stmt->execute([$data['name'], $data['org_number'] ?? null]);

        $id = $this->db->lastInsertId();
        $this->show(['id' => $id]);
    }

    public function update(array $params): void {
        $data = Validator::getJsonBody();

        // Check if company exists
        $stmt = $this->db->prepare('SELECT id FROM companies WHERE id = ?');
        $stmt->execute([$params['id']]);
        if (!$stmt->fetch()) {
            Response::error('Company not found', 404);
        }

        $stmt = $this->db->prepare(
            'UPDATE companies SET name = COALESCE(?, name), org_number = COALESCE(?, org_number) WHERE id = ?'
        );
        $stmt->execute([
            $data['name'] ?? null,
            $data['org_number'] ?? null,
            $params['id']
        ]);

        $this->show($params);
    }

    public function delete(array $params): void {
        $stmt = $this->db->prepare('SELECT id FROM companies WHERE id = ?');
        $stmt->execute([$params['id']]);
        if (!$stmt->fetch()) {
            Response::error('Company not found', 404);
        }

        $stmt = $this->db->prepare('DELETE FROM companies WHERE id = ?');
        $stmt->execute([$params['id']]);

        Response::success(['deleted' => (int)$params['id']]);
    }
}
