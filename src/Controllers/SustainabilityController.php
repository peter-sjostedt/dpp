<?php
namespace App\Controllers;

use App\Config\TenantContext;
use App\Helpers\Response;
use App\Helpers\Validator;

/**
 * Sustainability Controller - Uses sustainability_info table
 * Handles brand sustainability statements and environmental footprint
 */
class SustainabilityController extends TenantAwareController {

    /**
     * Check if current user can read this product
     * Brands: own products
     * Suppliers: products from brands they have relationship with
     */
    private function canReadProduct(int|string $productId): bool {
        if (TenantContext::isBrand()) {
            return $this->verifyProductOwnership($productId);
        }

        if (TenantContext::isSupplier()) {
            $brandId = $this->getProductBrandId($productId);
            return $brandId && $this->canAccessBrand($brandId);
        }

        return false;
    }

    public function show(array $params): void {
        if (!$this->canReadProduct($params['productId'])) {
            Response::error('Product not found', 404);
        }

        $stmt = $this->db->prepare('SELECT * FROM sustainability_info WHERE product_id = ?');
        $stmt->execute([$params['productId']]);
        $sustainability = $stmt->fetch();

        if (!$sustainability) {
            Response::error('Sustainability information not found', 404);
        }

        Response::success($sustainability);
    }

    public function createOrUpdate(array $params): void {
        // Write operations require brand authentication
        $this->requireBrand();

        $data = Validator::getJsonBody();

        // Verify product exists and belongs to this brand
        if (!$this->verifyProductOwnership($params['productId'])) {
            Response::error('Product not found', 404);
        }

        // Check if sustainability info exists
        $stmt = $this->db->prepare('SELECT id FROM sustainability_info WHERE product_id = ?');
        $stmt->execute([$params['productId']]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Update
            $stmt = $this->db->prepare(
                'UPDATE sustainability_info SET
                    brand_statement = COALESCE(?, brand_statement),
                    statement_link = COALESCE(?, statement_link),
                    environmental_footprint = COALESCE(?, environmental_footprint)
                 WHERE product_id = ?'
            );
            $stmt->execute([
                $data['brand_statement'] ?? null,
                $data['statement_link'] ?? null,
                $data['environmental_footprint'] ?? null,
                $params['productId']
            ]);
        } else {
            // Create
            $stmt = $this->db->prepare(
                'INSERT INTO sustainability_info (
                    product_id, brand_statement, statement_link, environmental_footprint
                 ) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([
                $params['productId'],
                $data['brand_statement'] ?? null,
                $data['statement_link'] ?? null,
                $data['environmental_footprint'] ?? null
            ]);
        }

        $this->show($params);
    }

    public function delete(array $params): void {
        // Write operations require brand authentication
        $this->requireBrand();

        if (!$this->verifyProductOwnership($params['productId'])) {
            Response::error('Product not found', 404);
        }

        $stmt = $this->db->prepare('SELECT id FROM sustainability_info WHERE product_id = ?');
        $stmt->execute([$params['productId']]);
        if (!$stmt->fetch()) {
            Response::error('Sustainability information not found', 404);
        }

        $stmt = $this->db->prepare('DELETE FROM sustainability_info WHERE product_id = ?');
        $stmt->execute([$params['productId']]);

        Response::success(['deleted' => true]);
    }
}
