<?php
namespace App\Controllers;

use App\Config\Database;
use App\Helpers\Response;
use App\Helpers\Validator;

class CircularityController {
    private \PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function show(array $params): void {
        $stmt = $this->db->prepare('SELECT * FROM circularity_information WHERE product_id = ?');
        $stmt->execute([$params['productId']]);
        $circularity = $stmt->fetch();

        if (!$circularity) {
            Response::error('Circularity information not found', 404);
        }
        Response::success($circularity);
    }

    public function createOrUpdate(array $params): void {
        $data = Validator::getJsonBody();

        // Verify product exists
        $stmt = $this->db->prepare('SELECT id FROM products WHERE id = ?');
        $stmt->execute([$params['productId']]);
        if (!$stmt->fetch()) {
            Response::error('Product not found', 404);
        }

        // Check if circularity info exists
        $stmt = $this->db->prepare('SELECT id FROM circularity_information WHERE product_id = ?');
        $stmt->execute([$params['productId']]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Update
            $stmt = $this->db->prepare(
                'UPDATE circularity_information SET
                    recycled_content_percentage = COALESCE(?, recycled_content_percentage),
                    recyclability_percentage = COALESCE(?, recyclability_percentage),
                    recycling_instructions = COALESCE(?, recycling_instructions),
                    take_back_program = COALESCE(?, take_back_program),
                    take_back_program_url = COALESCE(?, take_back_program_url),
                    repair_services = COALESCE(?, repair_services),
                    repair_services_url = COALESCE(?, repair_services_url),
                    expected_lifespan = COALESCE(?, expected_lifespan),
                    durability_info = COALESCE(?, durability_info)
                 WHERE product_id = ?'
            );
            $stmt->execute([
                $data['recycled_content_percentage'] ?? null,
                $data['recyclability_percentage'] ?? null,
                $data['recycling_instructions'] ?? null,
                $data['take_back_program'] ?? null,
                $data['take_back_program_url'] ?? null,
                $data['repair_services'] ?? null,
                $data['repair_services_url'] ?? null,
                $data['expected_lifespan'] ?? null,
                $data['durability_info'] ?? null,
                $params['productId']
            ]);
        } else {
            // Create
            $stmt = $this->db->prepare(
                'INSERT INTO circularity_information (
                    product_id, recycled_content_percentage, recyclability_percentage,
                    recycling_instructions, take_back_program, take_back_program_url,
                    repair_services, repair_services_url, expected_lifespan, durability_info
                 ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $params['productId'],
                $data['recycled_content_percentage'] ?? null,
                $data['recyclability_percentage'] ?? null,
                $data['recycling_instructions'] ?? null,
                $data['take_back_program'] ?? 0,
                $data['take_back_program_url'] ?? null,
                $data['repair_services'] ?? 0,
                $data['repair_services_url'] ?? null,
                $data['expected_lifespan'] ?? null,
                $data['durability_info'] ?? null
            ]);
        }

        $this->show($params);
    }

    public function delete(array $params): void {
        $stmt = $this->db->prepare('SELECT id FROM circularity_information WHERE product_id = ?');
        $stmt->execute([$params['productId']]);
        if (!$stmt->fetch()) {
            Response::error('Circularity information not found', 404);
        }

        $stmt = $this->db->prepare('DELETE FROM circularity_information WHERE product_id = ?');
        $stmt->execute([$params['productId']]);

        Response::success(['deleted' => true]);
    }
}
