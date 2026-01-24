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

        if ($error = Validator::required($data, ['item_number'])) {
            Response::error($error);
        }

        // Verify product exists
        $stmt = $this->db->prepare('SELECT id FROM products WHERE id = ?');
        $stmt->execute([$params['productId']]);
        if (!$stmt->fetch()) {
            Response::error('Product not found', 404);
        }

        // Check for duplicate item_number
        $stmt = $this->db->prepare('SELECT id FROM product_variants WHERE item_number = ?');
        $stmt->execute([$data['item_number']]);
        if ($stmt->fetch()) {
            Response::error('Item number already exists', 400);
        }

        $stmt = $this->db->prepare(
            'INSERT INTO product_variants (
                product_id, item_number, gtin, size, size_country_code,
                color_brand, color_general, weight_kg
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $params['productId'],
            $data['item_number'],
            $data['gtin'] ?? null,
            $data['size'] ?? null,
            $data['size_country_code'] ?? null,
            $data['color_brand'] ?? null,
            $data['color_general'] ?? null,
            $data['weight_kg'] ?? null
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

        // Check for duplicate item_number if changing
        if (isset($data['item_number'])) {
            $stmt = $this->db->prepare('SELECT id FROM product_variants WHERE item_number = ? AND id != ?');
            $stmt->execute([$data['item_number'], $params['id']]);
            if ($stmt->fetch()) {
                Response::error('Item number already exists', 400);
            }
        }

        $stmt = $this->db->prepare(
            'UPDATE product_variants SET
                item_number = COALESCE(?, item_number),
                gtin = COALESCE(?, gtin),
                size = COALESCE(?, size),
                size_country_code = COALESCE(?, size_country_code),
                color_brand = COALESCE(?, color_brand),
                color_general = COALESCE(?, color_general),
                weight_kg = COALESCE(?, weight_kg)
             WHERE id = ?'
        );
        $stmt->execute([
            $data['item_number'] ?? null,
            $data['gtin'] ?? null,
            $data['size'] ?? null,
            $data['size_country_code'] ?? null,
            $data['color_brand'] ?? null,
            $data['color_general'] ?? null,
            $data['weight_kg'] ?? null,
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
