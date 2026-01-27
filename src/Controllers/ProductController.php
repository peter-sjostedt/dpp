<?php
namespace App\Controllers;

use App\Config\TenantContext;
use App\Helpers\Response;
use App\Helpers\Validator;

/**
 * Product controller with multi-tenant access control.
 * - As Brand: Full CRUD on own products
 * - As Supplier: Read-only access to products from related brands
 */
class ProductController extends TenantAwareController
{
    public function index(array $params): void
    {
        if (TenantContext::isBrand()) {
            $brandId = TenantContext::getBrandId();

            // Verify brandId in URL matches authenticated brand
            if (isset($params['brandId']) && (int)$params['brandId'] !== $brandId) {
                Response::error('Brand not found', 404);
                return;
            }

            // Products belonging to brand that have items
            $stmt = $this->db->prepare(
                'SELECT DISTINCT p.*, b.brand_name
                 FROM products p
                 LEFT JOIN brands b ON p.brand_id = b.id
                 JOIN batches bat ON bat.product_id = p.id
                 JOIN items i ON i.batch_id = bat.id
                 WHERE p.brand_id = ? AND p._is_active = TRUE
                 ORDER BY p.product_name'
            );
            $stmt->execute([$brandId]);
        } else {
            // Supplier sees products from related brands
            $brandId = isset($params['brandId']) ? (int)$params['brandId'] : null;

            if ($brandId && !$this->canAccessBrand($brandId)) {
                Response::error('Brand not found', 404);
                return;
            }

            if ($brandId) {
                $stmt = $this->db->prepare(
                    'SELECT DISTINCT p.*, b.brand_name
                     FROM products p
                     LEFT JOIN brands b ON p.brand_id = b.id
                     WHERE p.brand_id = ? AND p._is_active = TRUE
                     ORDER BY p.product_name'
                );
                $stmt->execute([$brandId]);
            } else {
                // All products from related brands
                $stmt = $this->db->prepare(
                    'SELECT DISTINCT p.*, b.brand_name
                     FROM products p
                     LEFT JOIN brands b ON p.brand_id = b.id
                     JOIN brand_suppliers bs ON p.brand_id = bs.brand_id
                     WHERE bs.supplier_id = ? AND bs._is_active = TRUE AND p._is_active = TRUE
                     ORDER BY b.brand_name, p.product_name'
                );
                $stmt->execute([TenantContext::getSupplierId()]);
            }
        }

        Response::success($stmt->fetchAll());
    }

    public function indexAll(array $params): void
    {
        if (TenantContext::isBrand()) {
            $stmt = $this->db->prepare(
                'SELECT p.*, b.brand_name
                 FROM products p
                 LEFT JOIN brands b ON p.brand_id = b.id
                 WHERE p.brand_id = ?
                 ORDER BY p.product_name'
            );
            $stmt->execute([TenantContext::getBrandId()]);
        } else {
            // Supplier sees all products from related brands
            $stmt = $this->db->prepare(
                'SELECT p.*, b.brand_name
                 FROM products p
                 LEFT JOIN brands b ON p.brand_id = b.id
                 JOIN brand_suppliers bs ON p.brand_id = bs.brand_id
                 WHERE bs.supplier_id = ? AND bs._is_active = TRUE
                 ORDER BY b.brand_name, p.product_name'
            );
            $stmt->execute([TenantContext::getSupplierId()]);
        }

        Response::success($stmt->fetchAll());
    }

    public function show(array $params): void
    {
        $productId = (int) $params['id'];

        if (TenantContext::isBrand()) {
            if (!$this->verifyProductOwnership($productId)) {
                Response::error('Product not found', 404);
                return;
            }
        } else {
            // Supplier can view products from related brands
            $brandId = $this->getProductBrandId($productId);
            if (!$brandId || !$this->canAccessBrand($brandId)) {
                Response::error('Product not found', 404);
                return;
            }
        }

        $stmt = $this->db->prepare(
            'SELECT p.*, b.brand_name, b.logo_url as brand_logo, b.parent_company,
                    b.trader as trader_name, b.trader_location as trader_address
             FROM products p
             LEFT JOIN brands b ON p.brand_id = b.id
             WHERE p.id = ?'
        );
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if (!$product) {
            Response::error('Product not found', 404);
            return;
        }

        // Include related information (using correct table names)
        $product['care_information'] = $this->getCareInfo($productId);
        $product['compliance_information'] = $this->getComplianceInfo($productId);
        $product['circularity_information'] = $this->getCircularityInfo($productId);
        $product['sustainability_information'] = $this->getSustainabilityInfo($productId);
        $product['components'] = $this->getProductComponents($productId);

        Response::success($product);
    }

    public function create(array $params): void
    {
        $this->requireBrand();

        $brandId = TenantContext::getBrandId();

        // Verify brandId in URL matches authenticated brand
        if (isset($params['brandId']) && (int)$params['brandId'] !== $brandId) {
            Response::error('Cannot create products for other brands', 403);
            return;
        }

        $data = Validator::getJsonBody();

        if ($error = Validator::required($data, ['product_name'])) {
            Response::error($error);
            return;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO products (
                brand_id, gtin_type, gtin, product_name, description, photo_url,
                article_number, commodity_code_system, commodity_code_number,
                year_of_sale, season_of_sale, price_currency, msrp, resale_price,
                category, product_group, type_line_concept, type_item, age_group, gender,
                market_segment, water_properties, net_weight, weight_unit, _is_active,
                data_carrier_type, data_carrier_material, data_carrier_location
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $brandId,
            $data['gtin_type'] ?? null,
            $data['gtin'] ?? null,
            $data['product_name'],
            $data['description'] ?? null,
            $data['photo_url'] ?? null,
            $data['article_number'] ?? null,
            $data['commodity_code_system'] ?? null,
            $data['commodity_code_number'] ?? null,
            $data['year_of_sale'] ?? null,
            $data['season_of_sale'] ?? null,
            $data['price_currency'] ?? 'EUR',
            $data['msrp'] ?? null,
            $data['resale_price'] ?? null,
            $data['category'] ?? null,
            $data['product_group'] ?? null,
            $data['type_line_concept'] ?? null,
            $data['type_item'] ?? null,
            $data['age_group'] ?? null,
            $data['gender'] ?? null,
            $data['market_segment'] ?? null,
            $data['water_properties'] ?? null,
            $data['net_weight'] ?? null,
            $data['weight_unit'] ?? 'kg',
            $data['_is_active'] ?? true,
            $data['data_carrier_type'] ?? null,
            $data['data_carrier_material'] ?? null,
            $data['data_carrier_location'] ?? null
        ]);

        $id = $this->db->lastInsertId();
        $this->show(['id' => $id]);
    }

    public function update(array $params): void
    {
        $this->requireBrand();

        $productId = (int) $params['id'];

        if (!$this->verifyProductOwnership($productId)) {
            Response::error('Product not found', 404);
            return;
        }

        $data = Validator::getJsonBody();

        $stmt = $this->db->prepare(
            'UPDATE products SET
                gtin_type = COALESCE(?, gtin_type),
                gtin = COALESCE(?, gtin),
                product_name = COALESCE(?, product_name),
                description = COALESCE(?, description),
                photo_url = COALESCE(?, photo_url),
                article_number = COALESCE(?, article_number),
                commodity_code_system = COALESCE(?, commodity_code_system),
                commodity_code_number = COALESCE(?, commodity_code_number),
                year_of_sale = COALESCE(?, year_of_sale),
                season_of_sale = COALESCE(?, season_of_sale),
                price_currency = COALESCE(?, price_currency),
                msrp = COALESCE(?, msrp),
                resale_price = COALESCE(?, resale_price),
                category = COALESCE(?, category),
                product_group = COALESCE(?, product_group),
                type_line_concept = COALESCE(?, type_line_concept),
                type_item = COALESCE(?, type_item),
                age_group = COALESCE(?, age_group),
                gender = COALESCE(?, gender),
                market_segment = COALESCE(?, market_segment),
                water_properties = COALESCE(?, water_properties),
                net_weight = COALESCE(?, net_weight),
                weight_unit = COALESCE(?, weight_unit),
                _is_active = COALESCE(?, _is_active),
                data_carrier_type = COALESCE(?, data_carrier_type),
                data_carrier_material = COALESCE(?, data_carrier_material),
                data_carrier_location = COALESCE(?, data_carrier_location)
             WHERE id = ?'
        );
        $stmt->execute([
            $data['gtin_type'] ?? null,
            $data['gtin'] ?? null,
            $data['product_name'] ?? null,
            $data['description'] ?? null,
            $data['photo_url'] ?? null,
            $data['article_number'] ?? null,
            $data['commodity_code_system'] ?? null,
            $data['commodity_code_number'] ?? null,
            $data['year_of_sale'] ?? null,
            $data['season_of_sale'] ?? null,
            $data['price_currency'] ?? null,
            $data['msrp'] ?? null,
            $data['resale_price'] ?? null,
            $data['category'] ?? null,
            $data['product_group'] ?? null,
            $data['type_line_concept'] ?? null,
            $data['type_item'] ?? null,
            $data['age_group'] ?? null,
            $data['gender'] ?? null,
            $data['market_segment'] ?? null,
            $data['water_properties'] ?? null,
            $data['net_weight'] ?? null,
            $data['weight_unit'] ?? null,
            $data['_is_active'] ?? null,
            $data['data_carrier_type'] ?? null,
            $data['data_carrier_material'] ?? null,
            $data['data_carrier_location'] ?? null,
            $productId
        ]);

        $this->show(['id' => $productId]);
    }

    public function delete(array $params): void
    {
        $this->requireBrand();

        $productId = (int) $params['id'];

        if (!$this->verifyProductOwnership($productId)) {
            Response::error('Product not found', 404);
            return;
        }

        $stmt = $this->db->prepare('DELETE FROM products WHERE id = ?');
        $stmt->execute([$productId]);

        Response::success(['deleted' => $productId]);
    }

    // DPP Export - complete digital product passport (legacy endpoint)
    public function getDpp(array $params): void
    {
        $productId = (int) $params['id'];

        if (TenantContext::isBrand()) {
            if (!$this->verifyProductOwnership($productId)) {
                Response::error('Product not found', 404);
                return;
            }
        } else {
            // Supplier can view DPP for products from related brands
            $brandId = $this->getProductBrandId($productId);
            if (!$brandId || !$this->canAccessBrand($brandId)) {
                Response::error('Product not found', 404);
                return;
            }
        }

        $stmt = $this->db->prepare(
            'SELECT p.*, b.brand_name, b.logo_url, b.sub_brand, b.parent_company,
                    b.trader as trader_name, b.trader_location as trader_address
             FROM products p
             LEFT JOIN brands b ON p.brand_id = b.id
             WHERE p.id = ?'
        );
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if (!$product) {
            Response::error('Product not found', 404);
            return;
        }

        $dpp = [
            'product' => $product,
            'care_information' => $this->getCareInfo($productId),
            'compliance_information' => $this->getComplianceInfo($productId),
            'circularity_information' => $this->getCircularityInfo($productId),
            'sustainability_information' => $this->getSustainabilityInfo($productId),
            'components' => $this->getProductComponents($productId),
            'variants' => $this->getVariantsWithDetails($productId)
        ];

        Response::success($dpp);
    }

    // Helper methods to get related information
    private function getCareInfo(int|string $productId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM care_information WHERE product_id = ?');
        $stmt->execute([$productId]);
        return $stmt->fetch() ?: null;
    }

    private function getComplianceInfo(int|string $productId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM compliance_info WHERE product_id = ?');
        $stmt->execute([$productId]);
        return $stmt->fetch() ?: null;
    }

    private function getCircularityInfo(int|string $productId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM circularity_info WHERE product_id = ?');
        $stmt->execute([$productId]);
        return $stmt->fetch() ?: null;
    }

    private function getSustainabilityInfo(int|string $productId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM sustainability_info WHERE product_id = ?');
        $stmt->execute([$productId]);
        return $stmt->fetch() ?: null;
    }

    private function getProductComponents(int|string $productId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM product_components WHERE product_id = ? ORDER BY component');
        $stmt->execute([$productId]);
        return $stmt->fetchAll();
    }

    private function getVariantsWithDetails(int|string $productId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM product_variants WHERE product_id = ? ORDER BY item_number');
        $stmt->execute([$productId]);
        $variants = $stmt->fetchAll();

        foreach ($variants as &$variant) {
            $stmt = $this->db->prepare(
                'SELECT b.*,
                        (SELECT COUNT(*) FROM items i WHERE i.batch_id = b.id) as item_count
                 FROM batches b
                 WHERE b.product_id = ?'
            );
            $stmt->execute([$productId]);
            $variant['batches'] = $stmt->fetchAll();
        }

        return $variants;
    }
}
