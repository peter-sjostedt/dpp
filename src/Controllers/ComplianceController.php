<?php
namespace App\Controllers;

use App\Config\TenantContext;
use App\Helpers\Response;
use App\Helpers\Validator;

/**
 * Compliance Controller - Uses compliance_info table
 * Handles harmful substances, certifications, chemical compliance, microfibers
 */
class ComplianceController extends TenantAwareController {

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

    // ========== Compliance Information ==========

    public function show(array $params): void {
        if (!$this->canReadProduct($params['productId'])) {
            Response::error('Product not found', 404);
        }

        $stmt = $this->db->prepare('SELECT * FROM compliance_info WHERE product_id = ?');
        $stmt->execute([$params['productId']]);
        $compliance = $stmt->fetch();

        if (!$compliance) {
            Response::error('Compliance information not found', 404);
        }

        Response::success($compliance);
    }

    public function createOrUpdate(array $params): void {
        // Write operations require brand authentication
        $this->requireBrand();

        $data = Validator::getJsonBody();

        // Verify product exists and belongs to this brand
        if (!$this->verifyProductOwnership($params['productId'])) {
            Response::error('Product not found', 404);
        }

        // Check if compliance info exists
        $stmt = $this->db->prepare('SELECT id FROM compliance_info WHERE product_id = ?');
        $stmt->execute([$params['productId']]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Update
            $stmt = $this->db->prepare(
                'UPDATE compliance_info SET
                    harmful_substances = COALESCE(?, harmful_substances),
                    harmful_substances_info = COALESCE(?, harmful_substances_info),
                    certifications = COALESCE(?, certifications),
                    certifications_validation = COALESCE(?, certifications_validation),
                    chemical_compliance_standard = COALESCE(?, chemical_compliance_standard),
                    chemical_compliance_validation = COALESCE(?, chemical_compliance_validation),
                    chemical_compliance_link = COALESCE(?, chemical_compliance_link),
                    microfibers = COALESCE(?, microfibers),
                    traceability_provider = COALESCE(?, traceability_provider)
                 WHERE product_id = ?'
            );
            $stmt->execute([
                $data['harmful_substances'] ?? null,
                $data['harmful_substances_info'] ?? null,
                $data['certifications'] ?? null,
                $data['certifications_validation'] ?? null,
                $data['chemical_compliance_standard'] ?? null,
                $data['chemical_compliance_validation'] ?? null,
                $data['chemical_compliance_link'] ?? null,
                $data['microfibers'] ?? null,
                $data['traceability_provider'] ?? null,
                $params['productId']
            ]);
        } else {
            // Create
            $stmt = $this->db->prepare(
                'INSERT INTO compliance_info (
                    product_id, harmful_substances, harmful_substances_info,
                    certifications, certifications_validation,
                    chemical_compliance_standard, chemical_compliance_validation,
                    chemical_compliance_link, microfibers, traceability_provider
                 ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $params['productId'],
                $data['harmful_substances'] ?? null,
                $data['harmful_substances_info'] ?? null,
                $data['certifications'] ?? null,
                $data['certifications_validation'] ?? null,
                $data['chemical_compliance_standard'] ?? null,
                $data['chemical_compliance_validation'] ?? null,
                $data['chemical_compliance_link'] ?? null,
                $data['microfibers'] ?? null,
                $data['traceability_provider'] ?? null
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

        $stmt = $this->db->prepare('SELECT id FROM compliance_info WHERE product_id = ?');
        $stmt->execute([$params['productId']]);
        if (!$stmt->fetch()) {
            Response::error('Compliance information not found', 404);
        }

        $stmt = $this->db->prepare('DELETE FROM compliance_info WHERE product_id = ?');
        $stmt->execute([$params['productId']]);

        Response::success(['deleted' => true]);
    }
}
