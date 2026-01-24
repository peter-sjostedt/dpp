<?php
namespace App\Controllers;

use App\Config\Database;
use App\Helpers\Response;
use App\Helpers\Validator;

class BrandController {
    private \PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function index(array $params): void {
        $sql = 'SELECT b.*, c.name as company_name
                FROM brands b
                LEFT JOIN companies c ON b.company_id = c.id
                ORDER BY b.created_at DESC';
        $stmt = $this->db->query($sql);
        Response::success($stmt->fetchAll());
    }

    public function show(array $params): void {
        $stmt = $this->db->prepare(
            'SELECT b.*, c.name as company_name
             FROM brands b
             LEFT JOIN companies c ON b.company_id = c.id
             WHERE b.id = ?'
        );
        $stmt->execute([$params['id']]);
        $brand = $stmt->fetch();

        if (!$brand) {
            Response::error('Brand not found', 404);
        }
        Response::success($brand);
    }

    public function create(array $params): void {
        $data = Validator::getJsonBody();

        if ($error = Validator::required($data, ['company_id', 'brand_name'])) {
            Response::error($error);
        }

        // Verify company exists
        $stmt = $this->db->prepare('SELECT id FROM companies WHERE id = ?');
        $stmt->execute([$data['company_id']]);
        if (!$stmt->fetch()) {
            Response::error('Company not found', 404);
        }

        $stmt = $this->db->prepare(
            'INSERT INTO brands (company_id, brand_name, logo_url, brand_website, brand_description, sub_brand, parent_company, trader_name, trader_address)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['company_id'],
            $data['brand_name'],
            $data['logo_url'] ?? null,
            $data['brand_website'] ?? null,
            $data['brand_description'] ?? null,
            $data['sub_brand'] ?? null,
            $data['parent_company'] ?? null,
            $data['trader_name'] ?? null,
            $data['trader_address'] ?? null
        ]);

        $id = $this->db->lastInsertId();
        $this->show(['id' => $id]);
    }

    public function update(array $params): void {
        $data = Validator::getJsonBody();

        $stmt = $this->db->prepare('SELECT id FROM brands WHERE id = ?');
        $stmt->execute([$params['id']]);
        if (!$stmt->fetch()) {
            Response::error('Brand not found', 404);
        }

        $stmt = $this->db->prepare(
            'UPDATE brands SET
                brand_name = COALESCE(?, brand_name),
                logo_url = COALESCE(?, logo_url),
                brand_website = COALESCE(?, brand_website),
                brand_description = COALESCE(?, brand_description),
                sub_brand = COALESCE(?, sub_brand),
                parent_company = COALESCE(?, parent_company),
                trader_name = COALESCE(?, trader_name),
                trader_address = COALESCE(?, trader_address)
             WHERE id = ?'
        );
        $stmt->execute([
            $data['brand_name'] ?? null,
            $data['logo_url'] ?? null,
            $data['brand_website'] ?? null,
            $data['brand_description'] ?? null,
            $data['sub_brand'] ?? null,
            $data['parent_company'] ?? null,
            $data['trader_name'] ?? null,
            $data['trader_address'] ?? null,
            $params['id']
        ]);

        $this->show($params);
    }

    public function delete(array $params): void {
        $stmt = $this->db->prepare('SELECT id FROM brands WHERE id = ?');
        $stmt->execute([$params['id']]);
        if (!$stmt->fetch()) {
            Response::error('Brand not found', 404);
        }

        $stmt = $this->db->prepare('DELETE FROM brands WHERE id = ?');
        $stmt->execute([$params['id']]);

        Response::success(['deleted' => (int)$params['id']]);
    }
}
