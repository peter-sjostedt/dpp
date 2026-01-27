<?php
namespace App\Controllers;

use App\Config\TenantContext;
use App\Helpers\Response;
use App\Helpers\Validator;

/**
 * Product Component Controller
 * Handles product material composition (body fabric, trim, lining, etc.)
 * Components have their material info stored inline (not linked to separate table)
 */
class ProductComponentController extends TenantAwareController {

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
     * Check if current user can read a component
     */
    private function canReadComponent(int|string $componentId): bool {
        $stmt = $this->db->prepare(
            'SELECT product_id FROM product_components WHERE id = ?'
        );
        $stmt->execute([$componentId]);
        $comp = $stmt->fetch();

        if (!$comp) {
            return false;
        }

        return $this->canReadProduct($comp['product_id']);
    }

    /**
     * Verify component belongs to a product owned by current brand (for writes)
     */
    private function verifyComponentOwnership(int|string $componentId): bool {
        if (!TenantContext::isBrand()) {
            return false;
        }

        $brandId = TenantContext::getBrandId();
        $stmt = $this->db->prepare(
            'SELECT pc.id FROM product_components pc
             JOIN products p ON pc.product_id = p.id
             WHERE pc.id = ? AND p.brand_id = ?'
        );
        $stmt->execute([$componentId, $brandId]);
        return (bool) $stmt->fetch();
    }

    // ========== Product Components ==========

    public function index(array $params): void {
        if (!$this->canReadProduct($params['productId'])) {
            Response::error('Product not found', 404);
        }

        $stmt = $this->db->prepare(
            'SELECT * FROM product_components WHERE product_id = ? ORDER BY component'
        );
        $stmt->execute([$params['productId']]);
        Response::success($stmt->fetchAll());
    }

    public function show(array $params): void {
        if (!$this->canReadComponent($params['id'])) {
            Response::error('Component not found', 404);
        }

        $stmt = $this->db->prepare('SELECT * FROM product_components WHERE id = ?');
        $stmt->execute([$params['id']]);
        Response::success($stmt->fetch());
    }

    public function create(array $params): void {
        // Write operations require brand authentication
        $this->requireBrand();

        $data = Validator::getJsonBody();

        if ($error = Validator::required($data, ['component'])) {
            Response::error($error);
        }

        // Verify product exists and belongs to this brand
        if (!$this->verifyProductOwnership($params['productId'])) {
            Response::error('Product not found', 404);
        }

        $stmt = $this->db->prepare(
            'INSERT INTO product_components (
                product_id, component, material, content_name, content_value, content_source,
                material_trademarks, content_name_other, trim_type, component_weight,
                recycled, recycled_percentage, recycled_input_source,
                leather_species, leather_grade, leather_species_other, leather_pattern,
                leather_thickness, leather_max, leather_min,
                sewing_thread_content, print_ink_type, dye_class, dye_class_standard,
                finishes, pattern
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $params['productId'],
            $data['component'],
            $data['material'] ?? null,
            $data['content_name'] ?? null,
            $data['content_value'] ?? null,
            $data['content_source'] ?? null,
            $data['material_trademarks'] ?? null,
            $data['content_name_other'] ?? null,
            $data['trim_type'] ?? null,
            $data['component_weight'] ?? null,
            $data['recycled'] ?? null,
            $data['recycled_percentage'] ?? null,
            $data['recycled_input_source'] ?? null,
            $data['leather_species'] ?? null,
            $data['leather_grade'] ?? null,
            $data['leather_species_other'] ?? null,
            $data['leather_pattern'] ?? null,
            $data['leather_thickness'] ?? null,
            $data['leather_max'] ?? null,
            $data['leather_min'] ?? null,
            $data['sewing_thread_content'] ?? null,
            $data['print_ink_type'] ?? null,
            $data['dye_class'] ?? null,
            $data['dye_class_standard'] ?? null,
            $data['finishes'] ?? null,
            $data['pattern'] ?? null
        ]);

        $id = $this->db->lastInsertId();
        $this->show(['id' => $id]);
    }

    public function update(array $params): void {
        // Write operations require brand authentication
        $this->requireBrand();

        $data = Validator::getJsonBody();

        if (!$this->verifyComponentOwnership($params['id'])) {
            Response::error('Component not found', 404);
        }

        $stmt = $this->db->prepare(
            'UPDATE product_components SET
                component = COALESCE(?, component),
                material = COALESCE(?, material),
                content_name = COALESCE(?, content_name),
                content_value = COALESCE(?, content_value),
                content_source = COALESCE(?, content_source),
                material_trademarks = COALESCE(?, material_trademarks),
                content_name_other = COALESCE(?, content_name_other),
                trim_type = COALESCE(?, trim_type),
                component_weight = COALESCE(?, component_weight),
                recycled = COALESCE(?, recycled),
                recycled_percentage = COALESCE(?, recycled_percentage),
                recycled_input_source = COALESCE(?, recycled_input_source),
                leather_species = COALESCE(?, leather_species),
                leather_grade = COALESCE(?, leather_grade),
                leather_species_other = COALESCE(?, leather_species_other),
                leather_pattern = COALESCE(?, leather_pattern),
                leather_thickness = COALESCE(?, leather_thickness),
                leather_max = COALESCE(?, leather_max),
                leather_min = COALESCE(?, leather_min),
                sewing_thread_content = COALESCE(?, sewing_thread_content),
                print_ink_type = COALESCE(?, print_ink_type),
                dye_class = COALESCE(?, dye_class),
                dye_class_standard = COALESCE(?, dye_class_standard),
                finishes = COALESCE(?, finishes),
                pattern = COALESCE(?, pattern)
             WHERE id = ?'
        );
        $stmt->execute([
            $data['component'] ?? null,
            $data['material'] ?? null,
            $data['content_name'] ?? null,
            $data['content_value'] ?? null,
            $data['content_source'] ?? null,
            $data['material_trademarks'] ?? null,
            $data['content_name_other'] ?? null,
            $data['trim_type'] ?? null,
            $data['component_weight'] ?? null,
            $data['recycled'] ?? null,
            $data['recycled_percentage'] ?? null,
            $data['recycled_input_source'] ?? null,
            $data['leather_species'] ?? null,
            $data['leather_grade'] ?? null,
            $data['leather_species_other'] ?? null,
            $data['leather_pattern'] ?? null,
            $data['leather_thickness'] ?? null,
            $data['leather_max'] ?? null,
            $data['leather_min'] ?? null,
            $data['sewing_thread_content'] ?? null,
            $data['print_ink_type'] ?? null,
            $data['dye_class'] ?? null,
            $data['dye_class_standard'] ?? null,
            $data['finishes'] ?? null,
            $data['pattern'] ?? null,
            $params['id']
        ]);

        $this->show($params);
    }

    public function delete(array $params): void {
        // Write operations require brand authentication
        $this->requireBrand();

        if (!$this->verifyComponentOwnership($params['id'])) {
            Response::error('Component not found', 404);
        }

        $stmt = $this->db->prepare('DELETE FROM product_components WHERE id = ?');
        $stmt->execute([$params['id']]);

        Response::success(['deleted' => (int)$params['id']]);
    }
}
