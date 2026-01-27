<?php
namespace App\Controllers;

use App\Config\TenantContext;
use App\Helpers\Response;
use App\Helpers\Validator;

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

        $stmt = $this->db->prepare('SELECT * FROM compliance_information WHERE product_id = ?');
        $stmt->execute([$params['productId']]);
        $compliance = $stmt->fetch();

        if (!$compliance) {
            Response::error('Compliance information not found', 404);
        }

        // Include chemical compliance
        $compliance['chemical_compliance'] = $this->getChemicalCompliance($params['productId']);

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
        $stmt = $this->db->prepare('SELECT id FROM compliance_information WHERE product_id = ?');
        $stmt->execute([$params['productId']]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Update
            $stmt = $this->db->prepare(
                'UPDATE compliance_information SET
                    contains_svhc = COALESCE(?, contains_svhc),
                    svhc_details = COALESCE(?, svhc_details),
                    scan4chem_link = COALESCE(?, scan4chem_link),
                    sheds_microfibers = COALESCE(?, sheds_microfibers),
                    traceability_provider = COALESCE(?, traceability_provider)
                 WHERE product_id = ?'
            );
            $stmt->execute([
                $data['contains_svhc'] ?? null,
                $data['svhc_details'] ?? null,
                $data['scan4chem_link'] ?? null,
                $data['sheds_microfibers'] ?? null,
                $data['traceability_provider'] ?? null,
                $params['productId']
            ]);
        } else {
            // Create
            $stmt = $this->db->prepare(
                'INSERT INTO compliance_information (
                    product_id, contains_svhc, svhc_details,
                    scan4chem_link, sheds_microfibers, traceability_provider
                 ) VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $params['productId'],
                $data['contains_svhc'] ?? false,
                $data['svhc_details'] ?? null,
                $data['scan4chem_link'] ?? null,
                $data['sheds_microfibers'] ?? null,
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

        $stmt = $this->db->prepare('SELECT id FROM compliance_information WHERE product_id = ?');
        $stmt->execute([$params['productId']]);
        if (!$stmt->fetch()) {
            Response::error('Compliance information not found', 404);
        }

        $stmt = $this->db->prepare('DELETE FROM compliance_information WHERE product_id = ?');
        $stmt->execute([$params['productId']]);

        Response::success(['deleted' => true]);
    }

    // ========== Chemical Compliance ==========

    private function getChemicalCompliance(int|string $productId): array {
        $stmt = $this->db->prepare(
            'SELECT * FROM chemical_compliance WHERE product_id = ?'
        );
        $stmt->execute([$productId]);
        return $stmt->fetchAll();
    }

    public function listChemicals(array $params): void {
        if (!$this->canReadProduct($params['productId'])) {
            Response::error('Product not found', 404);
        }

        Response::success($this->getChemicalCompliance($params['productId']));
    }

    public function addChemical(array $params): void {
        // Write operations require brand authentication
        $this->requireBrand();

        $data = Validator::getJsonBody();

        if ($error = Validator::required($data, ['compliance_standard'])) {
            Response::error($error);
        }

        // Verify product exists and belongs to this brand
        if (!$this->verifyProductOwnership($params['productId'])) {
            Response::error('Product not found', 404);
        }

        $stmt = $this->db->prepare(
            'INSERT INTO chemical_compliance (
                product_id, compliance_standard, validation_document_url
             ) VALUES (?, ?, ?)'
        );
        $stmt->execute([
            $params['productId'],
            $data['compliance_standard'],
            $data['validation_document_url'] ?? null
        ]);

        $id = $this->db->lastInsertId();
        $stmt = $this->db->prepare('SELECT * FROM chemical_compliance WHERE id = ?');
        $stmt->execute([$id]);
        Response::success($stmt->fetch(), 201);
    }

    public function removeChemical(array $params): void {
        // Write operations require brand authentication
        $this->requireBrand();

        $brandId = TenantContext::getBrandId();

        // Verify chemical compliance belongs to a product owned by this brand
        $stmt = $this->db->prepare(
            'SELECT cc.id FROM chemical_compliance cc
             JOIN products p ON cc.product_id = p.id
             WHERE cc.id = ? AND p.brand_id = ?'
        );
        $stmt->execute([$params['id'], $brandId]);
        if (!$stmt->fetch()) {
            Response::error('Chemical compliance record not found', 404);
        }

        $stmt = $this->db->prepare('DELETE FROM chemical_compliance WHERE id = ?');
        $stmt->execute([$params['id']]);

        Response::success(['deleted' => (int)$params['id']]);
    }
}
