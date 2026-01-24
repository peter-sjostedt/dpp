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

        // Check if sustainability info exists
        $stmt = $this->db->prepare('SELECT id FROM sustainability_information WHERE product_id = ?');
        $stmt->execute([$params['productId']]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Update
            $stmt = $this->db->prepare(
                'UPDATE sustainability_information SET
                    carbon_footprint_kg = COALESCE(?, carbon_footprint_kg),
                    water_usage_liters = COALESCE(?, water_usage_liters),
                    energy_consumption_kwh = COALESCE(?, energy_consumption_kwh),
                    environmental_impact_url = COALESCE(?, environmental_impact_url),
                    sustainability_claims = COALESCE(?, sustainability_claims),
                    lca_report_url = COALESCE(?, lca_report_url)
                 WHERE product_id = ?'
            );
            $stmt->execute([
                $data['carbon_footprint_kg'] ?? null,
                $data['water_usage_liters'] ?? null,
                $data['energy_consumption_kwh'] ?? null,
                $data['environmental_impact_url'] ?? null,
                $data['sustainability_claims'] ?? null,
                $data['lca_report_url'] ?? null,
                $params['productId']
            ]);
        } else {
            // Create
            $stmt = $this->db->prepare(
                'INSERT INTO sustainability_information (
                    product_id, carbon_footprint_kg, water_usage_liters,
                    energy_consumption_kwh, environmental_impact_url,
                    sustainability_claims, lca_report_url
                 ) VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $params['productId'],
                $data['carbon_footprint_kg'] ?? null,
                $data['water_usage_liters'] ?? null,
                $data['energy_consumption_kwh'] ?? null,
                $data['environmental_impact_url'] ?? null,
                $data['sustainability_claims'] ?? null,
                $data['lca_report_url'] ?? null
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
