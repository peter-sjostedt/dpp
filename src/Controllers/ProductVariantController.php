<?php
namespace App\Controllers;

use App\Config\TenantContext;
use App\Helpers\Response;
use App\Helpers\Validator;

class ProductVariantController extends TenantAwareController {

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
     * Check if current user can read a variant
     */
    private function canReadVariant(int|string $variantId): bool {
        $stmt = $this->db->prepare('SELECT product_id FROM product_variants WHERE id = ?');
        $stmt->execute([$variantId]);
        $variant = $stmt->fetch();

        if (!$variant) {
            return false;
        }

        return $this->canReadProduct($variant['product_id']);
    }

    /**
     * Verify variant belongs to a product owned by current brand (for writes)
     */
    private function verifyVariantOwnership(int|string $variantId): bool {
        if (!TenantContext::isBrand()) {
            return false;
        }

        $brandId = TenantContext::getBrandId();
        $stmt = $this->db->prepare(
            'SELECT pv.id FROM product_variants pv
             JOIN products p ON pv.product_id = p.id
             WHERE pv.id = ? AND p.brand_id = ?'
        );
        $stmt->execute([$variantId, $brandId]);
        return (bool) $stmt->fetch();
    }

    public function index(array $params): void {
        if (!$this->canReadProduct($params['productId'])) {
            Response::error('Product not found', 404);
        }

        if (TenantContext::isBrand()) {
            $brandId = TenantContext::getBrandId();
            // Brands see variants that have batches with items, filtered on brand_id
            $stmt = $this->db->prepare(
                'SELECT DISTINCT pv.*, p.product_name
                 FROM product_variants pv
                 LEFT JOIN products p ON pv.product_id = p.id
                 JOIN batches b ON b.product_variant_id = pv.id
                 JOIN items i ON i.batch_id = b.id
                 WHERE pv.product_id = ? AND p.brand_id = ? AND pv._is_active = TRUE
                 ORDER BY pv.size, pv.color_name'
            );
            $stmt->execute([$params['productId'], $brandId]);
        } else {
            // Suppliers see variants from brands they have relationship with
            $stmt = $this->db->prepare(
                'SELECT DISTINCT pv.*, p.product_name
                 FROM product_variants pv
                 LEFT JOIN products p ON pv.product_id = p.id
                 JOIN batches b ON b.product_variant_id = pv.id
                 JOIN items i ON i.batch_id = b.id
                 WHERE pv.product_id = ? AND pv._is_active = TRUE
                 ORDER BY pv.size, pv.color_name'
            );
            $stmt->execute([$params['productId']]);
        }

        Response::success($stmt->fetchAll());
    }

    public function show(array $params): void {
        if (!$this->canReadVariant($params['id'])) {
            Response::error('Variant not found', 404);
        }

        $stmt = $this->db->prepare(
            'SELECT pv.*, p.product_name
             FROM product_variants pv
             LEFT JOIN products p ON pv.product_id = p.id
             WHERE pv.id = ?'
        );
        $stmt->execute([$params['id']]);
        Response::success($stmt->fetch());
    }

    public function create(array $params): void {
        // Write operations require brand authentication
        $this->requireBrand();

        $data = Validator::getJsonBody();

        if ($error = Validator::required($data, ['sku'])) {
            Response::error($error);
        }

        // Verify product exists and belongs to this brand
        if (!$this->verifyProductOwnership($params['productId'])) {
            Response::error('Product not found', 404);
        }

        // Check for duplicate sku
        $stmt = $this->db->prepare('SELECT id FROM product_variants WHERE sku = ?');
        $stmt->execute([$data['sku']]);
        if ($stmt->fetch()) {
            Response::error('SKU already exists', 400);
        }

        $stmt = $this->db->prepare(
            'INSERT INTO product_variants (
                product_id, sku, size, size_system,
                color_name, color_code, _is_active
             ) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $params['productId'],
            $data['sku'],
            $data['size'] ?? null,
            $data['size_system'] ?? null,
            $data['color_name'] ?? null,
            $data['color_code'] ?? null,
            $data['_is_active'] ?? true
        ]);

        $id = $this->db->lastInsertId();
        $this->show(['id' => $id]);
    }

    public function update(array $params): void {
        // Write operations require brand authentication
        $this->requireBrand();

        $data = Validator::getJsonBody();

        // Verify variant exists and belongs to this brand's product
        if (!$this->verifyVariantOwnership($params['id'])) {
            Response::error('Variant not found', 404);
        }

        // Check for duplicate sku if changing
        if (isset($data['sku'])) {
            $stmt = $this->db->prepare('SELECT id FROM product_variants WHERE sku = ? AND id != ?');
            $stmt->execute([$data['sku'], $params['id']]);
            if ($stmt->fetch()) {
                Response::error('SKU already exists', 400);
            }
        }

        $stmt = $this->db->prepare(
            'UPDATE product_variants SET
                sku = COALESCE(?, sku),
                size = COALESCE(?, size),
                size_system = COALESCE(?, size_system),
                color_name = COALESCE(?, color_name),
                color_code = COALESCE(?, color_code),
                _is_active = COALESCE(?, _is_active)
             WHERE id = ?'
        );
        $stmt->execute([
            $data['sku'] ?? null,
            $data['size'] ?? null,
            $data['size_system'] ?? null,
            $data['color_name'] ?? null,
            $data['color_code'] ?? null,
            $data['_is_active'] ?? null,
            $params['id']
        ]);

        $this->show($params);
    }

    public function delete(array $params): void {
        // Write operations require brand authentication
        $this->requireBrand();

        // Verify variant exists and belongs to this brand's product
        if (!$this->verifyVariantOwnership($params['id'])) {
            Response::error('Variant not found', 404);
        }

        $stmt = $this->db->prepare('DELETE FROM product_variants WHERE id = ?');
        $stmt->execute([$params['id']]);

        Response::success(['deleted' => (int)$params['id']]);
    }
}
