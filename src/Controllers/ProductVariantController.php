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
            // Brands see all variants for their product
            $stmt = $this->db->prepare(
                'SELECT pv.*, p.product_name
                 FROM product_variants pv
                 LEFT JOIN products p ON pv.product_id = p.id
                 WHERE pv.product_id = ? AND p.brand_id = ? AND pv._is_active = TRUE
                 ORDER BY pv.size, pv.color_brand'
            );
            $stmt->execute([$params['productId'], $brandId]);
        } else {
            // Suppliers see variants from brands they have relationship with
            $stmt = $this->db->prepare(
                'SELECT pv.*, p.product_name
                 FROM product_variants pv
                 LEFT JOIN products p ON pv.product_id = p.id
                 WHERE pv.product_id = ? AND pv._is_active = TRUE
                 ORDER BY pv.size, pv.color_brand'
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

        // Verify product exists and belongs to this brand
        if (!$this->verifyProductOwnership($params['productId'])) {
            Response::error('Product not found', 404);
        }

        // Check for duplicate gtin if provided
        if (!empty($data['gtin'])) {
            $stmt = $this->db->prepare('SELECT id FROM product_variants WHERE gtin = ?');
            $stmt->execute([$data['gtin']]);
            if ($stmt->fetch()) {
                Response::error('GTIN already exists', 400);
            }
        }

        $stmt = $this->db->prepare(
            'INSERT INTO product_variants (
                product_id, item_number, size, size_country_code,
                color_brand, color_general, gtin, _is_active
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $params['productId'],
            $data['item_number'] ?? null,
            $data['size'] ?? null,
            $data['size_country_code'] ?? null,
            $data['color_brand'] ?? null,
            $data['color_general'] ?? null,
            $data['gtin'] ?? null,
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

        // Check for duplicate gtin if changing
        if (!empty($data['gtin'])) {
            $stmt = $this->db->prepare('SELECT id FROM product_variants WHERE gtin = ? AND id != ?');
            $stmt->execute([$data['gtin'], $params['id']]);
            if ($stmt->fetch()) {
                Response::error('GTIN already exists', 400);
            }
        }

        $stmt = $this->db->prepare(
            'UPDATE product_variants SET
                item_number = COALESCE(?, item_number),
                size = COALESCE(?, size),
                size_country_code = COALESCE(?, size_country_code),
                color_brand = COALESCE(?, color_brand),
                color_general = COALESCE(?, color_general),
                gtin = COALESCE(?, gtin),
                _is_active = COALESCE(?, _is_active)
             WHERE id = ?'
        );
        $stmt->execute([
            $data['item_number'] ?? null,
            $data['size'] ?? null,
            $data['size_country_code'] ?? null,
            $data['color_brand'] ?? null,
            $data['color_general'] ?? null,
            $data['gtin'] ?? null,
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
