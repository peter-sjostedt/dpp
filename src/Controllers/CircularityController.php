<?php
namespace App\Controllers;

use App\Config\TenantContext;
use App\Helpers\Response;
use App\Helpers\Validator;

/**
 * Circularity Controller - Uses circularity_info table
 * Handles recyclability, take-back, repair instructions, etc.
 */
class CircularityController extends TenantAwareController {

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

        $stmt = $this->db->prepare('SELECT * FROM circularity_info WHERE product_id = ?');
        $stmt->execute([$params['productId']]);
        $circularity = $stmt->fetch();

        if (!$circularity) {
            Response::error('Circularity information not found', 404);
        }
        Response::success($circularity);
    }

    public function createOrUpdate(array $params): void {
        // Write operations require brand authentication
        $this->requireBrand();

        $data = Validator::getJsonBody();

        // Verify product exists and belongs to this brand
        if (!$this->verifyProductOwnership($params['productId'])) {
            Response::error('Product not found', 404);
        }

        // Check if circularity info exists
        $stmt = $this->db->prepare('SELECT id FROM circularity_info WHERE product_id = ?');
        $stmt->execute([$params['productId']]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Update
            $stmt = $this->db->prepare(
                'UPDATE circularity_info SET
                    performance = COALESCE(?, performance),
                    recyclability = COALESCE(?, recyclability),
                    take_back_instructions = COALESCE(?, take_back_instructions),
                    recycling_instructions = COALESCE(?, recycling_instructions),
                    disassembly_instructions_sorters = COALESCE(?, disassembly_instructions_sorters),
                    disassembly_instructions_user = COALESCE(?, disassembly_instructions_user),
                    circular_design_strategy = COALESCE(?, circular_design_strategy),
                    circular_design_description = COALESCE(?, circular_design_description),
                    repair_instructions = COALESCE(?, repair_instructions)
                 WHERE product_id = ?'
            );
            $stmt->execute([
                $data['performance'] ?? null,
                $data['recyclability'] ?? null,
                $data['take_back_instructions'] ?? null,
                $data['recycling_instructions'] ?? null,
                $data['disassembly_instructions_sorters'] ?? null,
                $data['disassembly_instructions_user'] ?? null,
                $data['circular_design_strategy'] ?? null,
                $data['circular_design_description'] ?? null,
                $data['repair_instructions'] ?? null,
                $params['productId']
            ]);
        } else {
            // Create
            $stmt = $this->db->prepare(
                'INSERT INTO circularity_info (
                    product_id, performance, recyclability, take_back_instructions,
                    recycling_instructions, disassembly_instructions_sorters,
                    disassembly_instructions_user, circular_design_strategy,
                    circular_design_description, repair_instructions
                 ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $params['productId'],
                $data['performance'] ?? null,
                $data['recyclability'] ?? null,
                $data['take_back_instructions'] ?? null,
                $data['recycling_instructions'] ?? null,
                $data['disassembly_instructions_sorters'] ?? null,
                $data['disassembly_instructions_user'] ?? null,
                $data['circular_design_strategy'] ?? null,
                $data['circular_design_description'] ?? null,
                $data['repair_instructions'] ?? null
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

        $stmt = $this->db->prepare('SELECT id FROM circularity_info WHERE product_id = ?');
        $stmt->execute([$params['productId']]);
        if (!$stmt->fetch()) {
            Response::error('Circularity information not found', 404);
        }

        $stmt = $this->db->prepare('DELETE FROM circularity_info WHERE product_id = ?');
        $stmt->execute([$params['productId']]);

        Response::success(['deleted' => true]);
    }
}
