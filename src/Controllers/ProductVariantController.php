<?php
namespace App\Controllers;

use App\Config\Database;
use App\Helpers\Response;
use App\Helpers\Validator;

class ProductVariantController {
    private \PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function index(array $params): void {
        $stmt = $this->db->prepare(
            'SELECT pv.*, p.product_name
             FROM product_variants pv
             LEFT JOIN products p ON pv.product_id = p.id
             WHERE pv.product_id = ?
             ORDER BY pv.created_at DESC'
        );
        $stmt->execute([$params['productId']]);
        Response::success($stmt->fetchAll());
    }

    public function show(array $params): void {
        $stmt = $this->db->prepare(
            'SELECT pv.*, p.product_name
             FROM product_variants pv
             LEFT JOIN products p ON pv.product_id = p.id
             WHERE pv.id = ?'
        );
        $stmt->execute([$params['id']]);
        $variant = $stmt->fetch();

        if (!$variant) {
            Response::error('Variant not found', 404);
        }
        Response::success($variant);
    }

    public function create(array $params): void {
        $data = Validator::getJsonBody();

        if ($error = Validator::required($data, ['sku'])) {
            Response::error($error);
        }

        // Verify product exists
        $stmt = $this->db->prepare('SELECT id FROM products WHERE id = ?');
        $stmt->execute([$params['productId']]);
        if (!$stmt->fetch()) {
            Response::error('Product not found', 404);
        }

        // Check for duplicate sku
        $stmt = $this->db->prepare('SELECT id FROM product_variants WHERE sku = ?');
        $stmt->execute([$data['sku']]);
        if ($stmt->fetch()) {
            Response::error('SKU already exists', 400);
        }

        $stmt = $this->db->prepare(
            'INSERT INTO product_variants (
                product_id, sku, size, size_system,
                color_name, color_code, is_active
             ) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $params['productId'],
            $data['sku'],
            $data['size'] ?? null,
            $data['size_system'] ?? null,
            $data['color_name'] ?? null,
            $data['color_code'] ?? null,
            $data['is_active'] ?? true
        ]);

        $id = $this->db->lastInsertId();
        $this->show(['id' => $id]);
    }

    public function update(array $params): void {
        $data = Validator::getJsonBody();

        $stmt = $this->db->prepare('SELECT id FROM product_variants WHERE id = ?');
        $stmt->execute([$params['id']]);
        if (!$stmt->fetch()) {
            Response::error('Variant not found', 404);
        }

        // Check for duplicate sku if changing
        if (isset($data['sku'])) {
            $stmt = $this->db->prepare('SELECT id FROM product_variants WHERE sku = ? AND id != ?');
            $stmt->execute([$data['sku'], $params['id']]);
            if ($stmt->fetch()) {
                Response::error('SKU already exists', 400);
            }
        }

        $stmt = $this->db->prepare(
            'UPDATE product_variants SET
                sku = COALESCE(?, sku),
                size = COALESCE(?, size),
                size_system = COALESCE(?, size_system),
                color_name = COALESCE(?, color_name),
                color_code = COALESCE(?, color_code),
                is_active = COALESCE(?, is_active)
             WHERE id = ?'
        );
        $stmt->execute([
            $data['sku'] ?? null,
            $data['size'] ?? null,
            $data['size_system'] ?? null,
            $data['color_name'] ?? null,
            $data['color_code'] ?? null,
            $data['is_active'] ?? null,
            $params['id']
        ]);

        $this->show($params);
    }

    public function delete(array $params): void {
        $stmt = $this->db->prepare('SELECT id FROM product_variants WHERE id = ?');
        $stmt->execute([$params['id']]);
        if (!$stmt->fetch()) {
            Response::error('Variant not found', 404);
        }

        $stmt = $this->db->prepare('DELETE FROM product_variants WHERE id = ?');
        $stmt->execute([$params['id']]);

        Response::success(['deleted' => (int)$params['id']]);
    }
}
