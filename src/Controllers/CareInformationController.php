<?php
namespace App\Controllers;

use App\Config\Database;
use App\Helpers\Response;
use App\Helpers\Validator;

class CareInformationController {
    private \PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function show(array $params): void {
        $stmt = $this->db->prepare('SELECT * FROM care_information WHERE product_id = ?');
        $stmt->execute([$params['productId']]);
        $care = $stmt->fetch();

        if (!$care) {
            Response::error('Care information not found', 404);
        }
        Response::success($care);
    }

    public function createOrUpdate(array $params): void {
        $data = Validator::getJsonBody();

        // Verify product exists
        $stmt = $this->db->prepare('SELECT id FROM products WHERE id = ?');
        $stmt->execute([$params['productId']]);
        if (!$stmt->fetch()) {
            Response::error('Product not found', 404);
        }

        // Check if care info exists
        $stmt = $this->db->prepare('SELECT id FROM care_information WHERE product_id = ?');
        $stmt->execute([$params['productId']]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Update
            $stmt = $this->db->prepare(
                'UPDATE care_information SET
                    washing_instructions = COALESCE(?, washing_instructions),
                    drying_instructions = COALESCE(?, drying_instructions),
                    ironing_instructions = COALESCE(?, ironing_instructions),
                    bleaching_instructions = COALESCE(?, bleaching_instructions),
                    professional_care = COALESCE(?, professional_care),
                    additional_care_info = COALESCE(?, additional_care_info)
                 WHERE product_id = ?'
            );
            $stmt->execute([
                $data['washing_instructions'] ?? null,
                $data['drying_instructions'] ?? null,
                $data['ironing_instructions'] ?? null,
                $data['bleaching_instructions'] ?? null,
                $data['professional_care'] ?? null,
                $data['additional_care_info'] ?? null,
                $params['productId']
            ]);
        } else {
            // Create
            $stmt = $this->db->prepare(
                'INSERT INTO care_information (
                    product_id, washing_instructions, drying_instructions,
                    ironing_instructions, bleaching_instructions,
                    professional_care, additional_care_info
                 ) VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $params['productId'],
                $data['washing_instructions'] ?? null,
                $data['drying_instructions'] ?? null,
                $data['ironing_instructions'] ?? null,
                $data['bleaching_instructions'] ?? null,
                $data['professional_care'] ?? null,
                $data['additional_care_info'] ?? null
            ]);
        }

        $this->show($params);
    }

    public function delete(array $params): void {
        $stmt = $this->db->prepare('SELECT id FROM care_information WHERE product_id = ?');
        $stmt->execute([$params['productId']]);
        if (!$stmt->fetch()) {
            Response::error('Care information not found', 404);
        }

        $stmt = $this->db->prepare('DELETE FROM care_information WHERE product_id = ?');
        $stmt->execute([$params['productId']]);

        Response::success(['deleted' => true]);
    }
}
