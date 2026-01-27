<?php
namespace App\Controllers;

use App\Config\TenantContext;
use App\Helpers\Response;
use App\Helpers\Validator;

class CertificationController extends TenantAwareController {

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

    /**
     * Check if current user can read a certification by ID
     */
    private function canReadCertification(int|string $certificationId): bool {
        $stmt = $this->db->prepare(
            'SELECT product_id FROM product_certifications WHERE id = ?'
        );
        $stmt->execute([$certificationId]);
        $cert = $stmt->fetch();

        if (!$cert) {
            return false;
        }

        return $this->canReadProduct($cert['product_id']);
    }

    public function index(array $params): void {
        if (!$this->canReadProduct($params['productId'])) {
            Response::error('Product not found', 404);
        }

        $stmt = $this->db->prepare(
            'SELECT * FROM product_certifications WHERE product_id = ? ORDER BY certification_name'
        );
        $stmt->execute([$params['productId']]);
        Response::success($stmt->fetchAll());
    }

    public function show(array $params): void {
        if (!$this->canReadCertification($params['id'])) {
            Response::error('Certification not found', 404);
        }

        $stmt = $this->db->prepare('SELECT * FROM product_certifications WHERE id = ?');
        $stmt->execute([$params['id']]);
        Response::success($stmt->fetch());
    }

    public function create(array $params): void {
        // Write operations require brand authentication
        $this->requireBrand();

        $data = Validator::getJsonBody();

        if ($error = Validator::required($data, ['certification_name'])) {
            Response::error($error);
        }

        // Verify product exists and belongs to this brand
        if (!$this->verifyProductOwnership($params['productId'])) {
            Response::error('Product not found', 404);
        }

        $stmt = $this->db->prepare(
            'INSERT INTO product_certifications (
                product_id, certification_name, certification_other,
                validation_document_url, valid_until
             ) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $params['productId'],
            $data['certification_name'],
            $data['certification_other'] ?? null,
            $data['validation_document_url'] ?? null,
            $data['valid_until'] ?? null
        ]);

        $id = $this->db->lastInsertId();
        $this->show(['id' => $id]);
    }

    public function update(array $params): void {
        // Write operations require brand authentication
        $this->requireBrand();

        $data = Validator::getJsonBody();
        $brandId = TenantContext::getBrandId();

        // Verify certification belongs to a product owned by this brand
        $stmt = $this->db->prepare(
            'SELECT c.id FROM product_certifications c
             JOIN products p ON c.product_id = p.id
             WHERE c.id = ? AND p.brand_id = ?'
        );
        $stmt->execute([$params['id'], $brandId]);
        if (!$stmt->fetch()) {
            Response::error('Certification not found', 404);
        }

        $stmt = $this->db->prepare(
            'UPDATE product_certifications SET
                certification_name = COALESCE(?, certification_name),
                certification_other = COALESCE(?, certification_other),
                validation_document_url = COALESCE(?, validation_document_url),
                valid_until = COALESCE(?, valid_until)
             WHERE id = ?'
        );
        $stmt->execute([
            $data['certification_name'] ?? null,
            $data['certification_other'] ?? null,
            $data['validation_document_url'] ?? null,
            $data['valid_until'] ?? null,
            $params['id']
        ]);

        $this->show($params);
    }

    public function delete(array $params): void {
        // Write operations require brand authentication
        $this->requireBrand();

        $brandId = TenantContext::getBrandId();

        // Verify certification belongs to a product owned by this brand
        $stmt = $this->db->prepare(
            'SELECT c.id FROM product_certifications c
             JOIN products p ON c.product_id = p.id
             WHERE c.id = ? AND p.brand_id = ?'
        );
        $stmt->execute([$params['id'], $brandId]);
        if (!$stmt->fetch()) {
            Response::error('Certification not found', 404);
        }

        $stmt = $this->db->prepare('DELETE FROM product_certifications WHERE id = ?');
        $stmt->execute([$params['id']]);

        Response::success(['deleted' => (int)$params['id']]);
    }
}
