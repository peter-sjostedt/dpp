<?php
namespace App\Controllers;

use App\Config\Database;
use App\Helpers\Response;
use App\Helpers\Validator;

class ProductController {
    private \PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function index(array $params): void {
        $stmt = $this->db->prepare(
            'SELECT p.*, b.brand_name
             FROM products p
             LEFT JOIN brands b ON p.brand_id = b.id
             WHERE p.brand_id = ?
             ORDER BY p.created_at DESC'
        );
        $stmt->execute([$params['brandId']]);
        Response::success($stmt->fetchAll());
    }

    public function show(array $params): void {
        $stmt = $this->db->prepare(
            'SELECT p.*, b.brand_name, c.name as company_name
             FROM products p
             LEFT JOIN brands b ON p.brand_id = b.id
             LEFT JOIN companies c ON b.company_id = c.id
             WHERE p.id = ?'
        );
        $stmt->execute([$params['id']]);
        $product = $stmt->fetch();

        if (!$product) {
            Response::error('Product not found', 404);
        }

        // Include related information
        $product['care_information'] = $this->getCareInfo($params['id']);
        $product['compliance_information'] = $this->getComplianceInfo($params['id']);
        $product['circularity_information'] = $this->getCircularityInfo($params['id']);
        $product['sustainability_information'] = $this->getSustainabilityInfo($params['id']);
        $product['certifications'] = $this->getCertifications($params['id']);
        $product['chemical_compliance'] = $this->getChemicalCompliance($params['id']);
        $product['components'] = $this->getComponents($params['id']);

        Response::success($product);
    }

    public function create(array $params): void {
        $data = Validator::getJsonBody();

        if ($error = Validator::required($data, ['product_name'])) {
            Response::error($error);
        }

        // Verify brand exists
        $stmt = $this->db->prepare('SELECT id FROM brands WHERE id = ?');
        $stmt->execute([$params['brandId']]);
        if (!$stmt->fetch()) {
            Response::error('Brand not found', 404);
        }

        $stmt = $this->db->prepare(
            'INSERT INTO products (
                brand_id, product_name, product_description, product_category,
                product_type, product_gender, style_number, season,
                product_image_url, country_of_origin, gtin,
                description, consumer_website_url, line, garment_type,
                weight_kg, is_active
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $params['brandId'],
            $data['product_name'],
            $data['product_description'] ?? null,
            $data['product_category'] ?? null,
            $data['product_type'] ?? null,
            $data['product_gender'] ?? null,
            $data['style_number'] ?? null,
            $data['season'] ?? null,
            $data['product_image_url'] ?? null,
            $data['country_of_origin'] ?? null,
            $data['gtin'] ?? null,
            $data['description'] ?? null,
            $data['consumer_website_url'] ?? null,
            $data['line'] ?? null,
            $data['garment_type'] ?? null,
            $data['weight_kg'] ?? null,
            $data['is_active'] ?? true
        ]);

        $id = $this->db->lastInsertId();
        $this->show(['id' => $id]);
    }

    public function update(array $params): void {
        $data = Validator::getJsonBody();

        $stmt = $this->db->prepare('SELECT id FROM products WHERE id = ?');
        $stmt->execute([$params['id']]);
        if (!$stmt->fetch()) {
            Response::error('Product not found', 404);
        }

        $stmt = $this->db->prepare(
            'UPDATE products SET
                product_name = COALESCE(?, product_name),
                product_description = COALESCE(?, product_description),
                product_category = COALESCE(?, product_category),
                product_type = COALESCE(?, product_type),
                product_gender = COALESCE(?, product_gender),
                style_number = COALESCE(?, style_number),
                season = COALESCE(?, season),
                product_image_url = COALESCE(?, product_image_url),
                country_of_origin = COALESCE(?, country_of_origin),
                gtin = COALESCE(?, gtin),
                description = COALESCE(?, description),
                consumer_website_url = COALESCE(?, consumer_website_url),
                line = COALESCE(?, line),
                garment_type = COALESCE(?, garment_type),
                weight_kg = COALESCE(?, weight_kg),
                is_active = COALESCE(?, is_active)
             WHERE id = ?'
        );
        $stmt->execute([
            $data['product_name'] ?? null,
            $data['product_description'] ?? null,
            $data['product_category'] ?? null,
            $data['product_type'] ?? null,
            $data['product_gender'] ?? null,
            $data['style_number'] ?? null,
            $data['season'] ?? null,
            $data['product_image_url'] ?? null,
            $data['country_of_origin'] ?? null,
            $data['gtin'] ?? null,
            $data['description'] ?? null,
            $data['consumer_website_url'] ?? null,
            $data['line'] ?? null,
            $data['garment_type'] ?? null,
            $data['weight_kg'] ?? null,
            $data['is_active'] ?? null,
            $params['id']
        ]);

        $this->show($params);
    }

    public function delete(array $params): void {
        $stmt = $this->db->prepare('SELECT id FROM products WHERE id = ?');
        $stmt->execute([$params['id']]);
        if (!$stmt->fetch()) {
            Response::error('Product not found', 404);
        }

        $stmt = $this->db->prepare('DELETE FROM products WHERE id = ?');
        $stmt->execute([$params['id']]);

        Response::success(['deleted' => (int)$params['id']]);
    }

    // DPP Export - complete digital product passport
    public function getDpp(array $params): void {
        $stmt = $this->db->prepare(
            'SELECT p.*, b.brand_name, b.logo_url, b.sub_brand, b.parent_company, b.trader_name, b.trader_address,
                    c.name as company_name, c.org_number
             FROM products p
             LEFT JOIN brands b ON p.brand_id = b.id
             LEFT JOIN companies c ON b.company_id = c.id
             WHERE p.id = ?'
        );
        $stmt->execute([$params['id']]);
        $product = $stmt->fetch();

        if (!$product) {
            Response::error('Product not found', 404);
        }

        $dpp = [
            'product' => $product,
            'care_information' => $this->getCareInfo($params['id']),
            'compliance_information' => $this->getComplianceInfo($params['id']),
            'circularity_information' => $this->getCircularityInfo($params['id']),
            'sustainability_information' => $this->getSustainabilityInfo($params['id']),
            'certifications' => $this->getCertifications($params['id']),
            'chemical_compliance' => $this->getChemicalCompliance($params['id']),
            'components' => $this->getComponentsWithMaterials($params['id']),
            'variants' => $this->getVariantsWithDetails($params['id'])
        ];

        Response::success($dpp);
    }

    // Helper methods to get related information
    private function getCareInfo(int|string $productId): ?array {
        $stmt = $this->db->prepare('SELECT * FROM care_information WHERE product_id = ?');
        $stmt->execute([$productId]);
        return $stmt->fetch() ?: null;
    }

    private function getComplianceInfo(int|string $productId): ?array {
        $stmt = $this->db->prepare('SELECT * FROM compliance_information WHERE product_id = ?');
        $stmt->execute([$productId]);
        return $stmt->fetch() ?: null;
    }

    private function getCircularityInfo(int|string $productId): ?array {
        $stmt = $this->db->prepare('SELECT * FROM circularity_information WHERE product_id = ?');
        $stmt->execute([$productId]);
        return $stmt->fetch() ?: null;
    }

    private function getSustainabilityInfo(int|string $productId): ?array {
        $stmt = $this->db->prepare('SELECT * FROM sustainability_information WHERE product_id = ?');
        $stmt->execute([$productId]);
        return $stmt->fetch() ?: null;
    }

    private function getCertifications(int|string $productId): array {
        $stmt = $this->db->prepare('SELECT * FROM certifications WHERE product_id = ? ORDER BY certification_name');
        $stmt->execute([$productId]);
        return $stmt->fetchAll();
    }

    private function getChemicalCompliance(int|string $productId): array {
        $stmt = $this->db->prepare('SELECT * FROM chemical_compliance WHERE product_id = ?');
        $stmt->execute([$productId]);
        return $stmt->fetchAll();
    }

    private function getComponents(int|string $productId): array {
        $stmt = $this->db->prepare('SELECT * FROM product_components WHERE product_id = ? ORDER BY component_type');
        $stmt->execute([$productId]);
        return $stmt->fetchAll();
    }

    private function getComponentsWithMaterials(int|string $productId): array {
        $stmt = $this->db->prepare('SELECT * FROM product_components WHERE product_id = ? ORDER BY component_type');
        $stmt->execute([$productId]);
        $components = $stmt->fetchAll();

        foreach ($components as &$component) {
            $stmt = $this->db->prepare(
                'SELECT cm.*, fm.material_name, fm.material_type
                 FROM component_materials cm
                 LEFT JOIN factory_materials fm ON cm.factory_material_id = fm.id
                 WHERE cm.component_id = ?'
            );
            $stmt->execute([$component['id']]);
            $component['materials'] = $stmt->fetchAll();
        }

        return $components;
    }

    private function getVariantsWithDetails(int|string $productId): array {
        $stmt = $this->db->prepare('SELECT * FROM product_variants WHERE product_id = ? ORDER BY sku');
        $stmt->execute([$productId]);
        $variants = $stmt->fetchAll();

        foreach ($variants as &$variant) {
            $stmt = $this->db->prepare(
                'SELECT b.*,
                        (SELECT COUNT(*) FROM items i WHERE i.batch_id = b.id) as item_count
                 FROM batches b
                 WHERE b.product_variant_id = ?'
            );
            $stmt->execute([$variant['id']]);
            $variant['batches'] = $stmt->fetchAll();
        }

        return $variants;
    }
}
