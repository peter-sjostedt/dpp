<?php
namespace App\Controllers;

use App\Config\TenantContext;
use App\Helpers\Response;
use App\Helpers\Validator;

class CareInformationController extends TenantAwareController {

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

        $stmt = $this->db->prepare('SELECT * FROM care_information WHERE product_id = ?');
        $stmt->execute([$params['productId']]);
        $care = $stmt->fetch();

        if (!$care) {
            Response::error('Care information not found', 404);
        }
        Response::success($care);
    }

    public function createOrUpdate(array $params): void {
        // Write operations require brand authentication
        $this->requireBrand();

        $data = Validator::getJsonBody();

        // Verify product exists and belongs to this brand
        if (!$this->verifyProductOwnership($params['productId'])) {
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
                    care_image_url = COALESCE(?, care_image_url),
                    care_text = COALESCE(?, care_text),
                    safety_information = COALESCE(?, safety_information)
                 WHERE product_id = ?'
            );
            $stmt->execute([
                $data['care_image_url'] ?? null,
                $data['care_text'] ?? null,
                $data['safety_information'] ?? null,
                $params['productId']
            ]);
        } else {
            // Create
            $stmt = $this->db->prepare(
                'INSERT INTO care_information (
                    product_id, care_image_url, care_text, safety_information
                 ) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([
                $params['productId'],
                $data['care_image_url'] ?? null,
                $data['care_text'] ?? null,
                $data['safety_information'] ?? null
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
