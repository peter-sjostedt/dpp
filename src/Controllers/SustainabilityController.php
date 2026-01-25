<?php
namespace App\Controllers;

use App\Config\Database;
use App\Helpers\Response;
use App\Helpers\Validator;

class SustainabilityController {
    private \PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function show(array $params): void {
        $stmt = $this->db->prepare('SELECT * FROM sustainability_information WHERE product_id = ?');
        $stmt->execute([$params['productId']]);
        $sustainability = $stmt->fetch();

        if (!$sustainability) {
            Response::error('Sustainability information not found', 404);
        }

        // Decode JSON environmental_footprint if present
        if (!empty($sustainability['environmental_footprint'])) {
            $sustainability['environmental_footprint'] = json_decode($sustainability['environmental_footprint'], true);
        }

        Response::success($sustainability);
    }

    public function createOrUpdate(array $params): void {
        $data = Validator::getJsonBody();

        // Verify product exists
        $stmt = $this->db->prepare('SELECT id FROM products WHERE id = ?');
        $stmt->execute([$params['productId']]);
        if (!$stmt->fetch()) {
            Response::error('Product not found', 404);
        }

        // Encode environmental_footprint as JSON if it's an array
        $environmentalFootprint = null;
        if (isset($data['environmental_footprint'])) {
            $environmentalFootprint = is_array($data['environmental_footprint'])
                ? json_encode($data['environmental_footprint'])
                : $data['environmental_footprint'];
        }

        // Check if sustainability info exists
        $stmt = $this->db->prepare('SELECT id FROM sustainability_information WHERE product_id = ?');
        $stmt->execute([$params['productId']]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Update
            $stmt = $this->db->prepare(
                'UPDATE sustainability_information SET
                    brand_statement = COALESCE(?, brand_statement),
                    brand_statement_link = COALESCE(?, brand_statement_link),
                    environmental_footprint = COALESCE(?, environmental_footprint)
                 WHERE product_id = ?'
            );
            $stmt->execute([
                $data['brand_statement'] ?? null,
                $data['brand_statement_link'] ?? null,
                $environmentalFootprint,
                $params['productId']
            ]);
        } else {
            // Create
            $stmt = $this->db->prepare(
                'INSERT INTO sustainability_information (
                    product_id, brand_statement, brand_statement_link, environmental_footprint
                 ) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([
                $params['productId'],
                $data['brand_statement'] ?? null,
                $data['brand_statement_link'] ?? null,
                $environmentalFootprint
            ]);
        }

        $this->show($params);
    }

    public function delete(array $params): void {
        $stmt = $this->db->prepare('SELECT id FROM sustainability_information WHERE product_id = ?');
        $stmt->execute([$params['productId']]);
        if (!$stmt->fetch()) {
            Response::error('Sustainability information not found', 404);
        }

        $stmt = $this->db->prepare('DELETE FROM sustainability_information WHERE product_id = ?');
        $stmt->execute([$params['productId']]);

        Response::success(['deleted' => true]);
    }
}
