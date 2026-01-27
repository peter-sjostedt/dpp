<?php
namespace App\Controllers;

use App\Config\TenantContext;
use App\Helpers\Response;

class DppExportController extends TenantAwareController {

    /**
     * Preview complete DPP data for a product
     */
    public function preview(array $params): void {
        // DPP export is brand-only
        $this->requireBrand();

        $productId = $params['id'];

        // Verify product exists and belongs to this brand
        if (!$this->verifyProductOwnership($productId)) {
            Response::error('Product not found', 404);
        }

        $dpp = [
            'brandInformation' => $this->getBrandInfo($productId),
            'productInformation' => $this->getProductInfo($productId),
            'materialInformation' => $this->getMaterialInfo($productId),
            'supplyChainInformation' => $this->getSupplyChainInfo($productId),
            'careInformation' => $this->getCareInfo($productId),
            'complianceInformation' => $this->getComplianceInfo($productId),
            'circularityInformation' => $this->getCircularityInfo($productId),
            'sustainabilityInformation' => $this->getSustainabilityInfo($productId),
        ];

        Response::success($dpp);
    }

    /**
     * Validate DPP data completeness
     */
    public function validate(array $params): void {
        // DPP export is brand-only
        $this->requireBrand();

        $productId = $params['id'];

        // Verify product exists and belongs to this brand
        if (!$this->verifyProductOwnership($productId)) {
            Response::error('Product not found', 404);
        }

        $errors = [];
        $warnings = [];

        // Check product has required fields
        $stmt = $this->db->prepare('SELECT * FROM products WHERE id = ?');
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        // Required product fields
        if (empty($product['product_name'])) {
            $errors[] = 'Product name is required';
        }
        if (empty($product['country_of_origin'])) {
            $warnings[] = 'Country of origin is recommended';
        }

        // Check brand info
        $brandInfo = $this->getBrandInfo($productId);
        if (empty($brandInfo['brand_name'])) {
            $errors[] = 'Brand name is required';
        }

        // Check materials
        $materials = $this->getMaterialInfo($productId);
        if (empty($materials)) {
            $warnings[] = 'No material information linked to product batches';
        } else {
            foreach ($materials as $material) {
                if (empty($material['compositions'])) {
                    $warnings[] = "Material '{$material['material_name']}' has no composition data";
                }
            }
        }

        // Check care info
        $careInfo = $this->getCareInfo($productId);
        if (empty($careInfo)) {
            $warnings[] = 'Care information is recommended';
        }

        // Check compliance
        $compliance = $this->getComplianceInfo($productId);
        if (empty($compliance)) {
            $warnings[] = 'Compliance information is recommended for DPP';
        }

        $isValid = empty($errors);

        Response::success([
            'valid' => $isValid,
            'errors' => $errors,
            'warnings' => $warnings,
            'completeness' => $this->calculateCompleteness($productId)
        ]);
    }

    /**
     * Export DPP in Trace4Value format
     */
    public function export(array $params): void {
        // DPP export is brand-only
        $this->requireBrand();

        $productId = $params['id'];

        // Verify product exists and belongs to this brand
        if (!$this->verifyProductOwnership($productId)) {
            Response::error('Product not found', 404);
        }

        $dpp = $this->buildTrace4ValueDpp($productId);

        Response::success([
            'format' => 'Trace4Value',
            'version' => '1.0',
            'exported_at' => date('c'),
            'dpp' => $dpp
        ]);
    }

    // ========== Helper Methods ==========

    private function getBrandInfo(int|string $productId): array {
        $stmt = $this->db->prepare(
            'SELECT b.brand_name, b.brand_logo_url, b.brand_website, b.brand_description,
                    c.name as company_name, c.org_number
             FROM products p
             LEFT JOIN brands b ON p.brand_id = b.id
             LEFT JOIN companies c ON b.company_id = c.id
             WHERE p.id = ?'
        );
        $stmt->execute([$productId]);
        return $stmt->fetch() ?: [];
    }

    private function getProductInfo(int|string $productId): array {
        $stmt = $this->db->prepare(
            'SELECT p.*, GROUP_CONCAT(DISTINCT pv.sku) as skus
             FROM products p
             LEFT JOIN product_variants pv ON pv.product_id = p.id
             WHERE p.id = ?
             GROUP BY p.id'
        );
        $stmt->execute([$productId]);
        $product = $stmt->fetch() ?: [];

        // Get variants
        $stmt = $this->db->prepare(
            'SELECT sku, size, size_system, color_name, color_code
             FROM product_variants WHERE product_id = ?'
        );
        $stmt->execute([$productId]);
        $product['variants'] = $stmt->fetchAll();

        return $product;
    }

    private function getMaterialInfo(int|string $productId): array {
        // Get materials linked via batches
        $stmt = $this->db->prepare(
            'SELECT DISTINCT fm.*, s.supplier_name, s.supplier_location
             FROM factory_materials fm
             LEFT JOIN suppliers s ON fm.supplier_id = s.id
             INNER JOIN batch_materials bm ON bm.factory_material_id = fm.id
             INNER JOIN batches b ON b.id = bm.batch_id
             INNER JOIN product_variants pv ON pv.id = b.product_variant_id
             WHERE pv.product_id = ?'
        );
        $stmt->execute([$productId]);
        $materials = $stmt->fetchAll();

        // Enrich each material with compositions and certifications
        foreach ($materials as &$material) {
            $stmt = $this->db->prepare(
                'SELECT fiber_type, percentage, fiber_source, is_recycled
                 FROM factory_material_compositions WHERE factory_material_id = ?'
            );
            $stmt->execute([$material['id']]);
            $material['compositions'] = $stmt->fetchAll();

            $stmt = $this->db->prepare(
                'SELECT certification_type, certification_other, certificate_number, valid_until
                 FROM factory_material_certifications WHERE factory_material_id = ?'
            );
            $stmt->execute([$material['id']]);
            $material['certifications'] = $stmt->fetchAll();
        }

        return $materials;
    }

    private function getSupplyChainInfo(int|string $productId): array {
        // Get supply chain from materials
        $stmt = $this->db->prepare(
            'SELECT DISTINCT fmsc.*
             FROM factory_material_supply_chain fmsc
             INNER JOIN factory_materials fm ON fm.id = fmsc.factory_material_id
             INNER JOIN batch_materials bm ON bm.factory_material_id = fm.id
             INNER JOIN batches b ON b.id = bm.batch_id
             INNER JOIN product_variants pv ON pv.id = b.product_variant_id
             WHERE pv.product_id = ?
             ORDER BY fmsc.process_stage'
        );
        $stmt->execute([$productId]);
        $materialSupplyChain = $stmt->fetchAll();

        // Get suppliers from batches
        $stmt = $this->db->prepare(
            'SELECT DISTINCT s.*, bs.production_stage
             FROM suppliers s
             INNER JOIN batch_suppliers bs ON bs.supplier_id = s.id
             INNER JOIN batches b ON b.id = bs.batch_id
             INNER JOIN product_variants pv ON pv.id = b.product_variant_id
             WHERE pv.product_id = ?
             ORDER BY bs.production_stage'
        );
        $stmt->execute([$productId]);
        $batchSuppliers = $stmt->fetchAll();

        return [
            'material_supply_chain' => $materialSupplyChain,
            'production_suppliers' => $batchSuppliers
        ];
    }

    private function getCareInfo(int|string $productId): ?array {
        $stmt = $this->db->prepare('SELECT * FROM care_information WHERE product_id = ?');
        $stmt->execute([$productId]);
        return $stmt->fetch() ?: null;
    }

    private function getComplianceInfo(int|string $productId): ?array {
        $stmt = $this->db->prepare('SELECT * FROM compliance_information WHERE product_id = ?');
        $stmt->execute([$productId]);
        return $stmt->fetch() ?: null;
    }

    private function getCircularityInfo(int|string $productId): ?array {
        $stmt = $this->db->prepare('SELECT * FROM circularity_information WHERE product_id = ?');
        $stmt->execute([$productId]);
        return $stmt->fetch() ?: null;
    }

    private function getSustainabilityInfo(int|string $productId): ?array {
        $stmt = $this->db->prepare('SELECT * FROM sustainability_information WHERE product_id = ?');
        $stmt->execute([$productId]);
        return $stmt->fetch() ?: null;
    }

    private function calculateCompleteness(int|string $productId): array {
        $sections = [
            'brand' => !empty($this->getBrandInfo($productId)['brand_name']),
            'product' => !empty($this->getProductInfo($productId)['product_name']),
            'materials' => !empty($this->getMaterialInfo($productId)),
            'supply_chain' => !empty($this->getSupplyChainInfo($productId)['material_supply_chain']),
            'care' => !empty($this->getCareInfo($productId)),
            'compliance' => !empty($this->getComplianceInfo($productId)),
            'circularity' => !empty($this->getCircularityInfo($productId)),
            'sustainability' => !empty($this->getSustainabilityInfo($productId)),
        ];

        $completed = count(array_filter($sections));
        $total = count($sections);

        return [
            'percentage' => round(($completed / $total) * 100),
            'sections' => $sections
        ];
    }

    private function buildTrace4ValueDpp(int|string $productId): array {
        $brandInfo = $this->getBrandInfo($productId);
        $productInfo = $this->getProductInfo($productId);
        $materials = $this->getMaterialInfo($productId);
        $supplyChain = $this->getSupplyChainInfo($productId);
        $care = $this->getCareInfo($productId);
        $compliance = $this->getComplianceInfo($productId);
        $circularity = $this->getCircularityInfo($productId);
        $sustainability = $this->getSustainabilityInfo($productId);

        // Build compositions array from all materials
        $compositions = [];
        foreach ($materials as $material) {
            foreach ($material['compositions'] ?? [] as $comp) {
                $compositions[] = [
                    'fiberType' => $comp['fiber_type'],
                    'percentage' => (float)$comp['percentage'],
                    'source' => $comp['fiber_source'],
                    'isRecycled' => (bool)$comp['is_recycled']
                ];
            }
        }

        // Build certifications array
        $certifications = [];
        foreach ($materials as $material) {
            foreach ($material['certifications'] ?? [] as $cert) {
                $certifications[] = [
                    'type' => $cert['certification_type'],
                    'other' => $cert['certification_other'],
                    'number' => $cert['certificate_number'],
                    'validUntil' => $cert['valid_until']
                ];
            }
        }

        return [
            'brandInformation' => [
                'brandName' => $brandInfo['brand_name'] ?? null,
                'brandLogo' => $brandInfo['brand_logo_url'] ?? null,
                'brandWebsite' => $brandInfo['brand_website'] ?? null,
                'companyName' => $brandInfo['company_name'] ?? null,
                'organizationNumber' => $brandInfo['org_number'] ?? null,
            ],
            'productInformation' => [
                'productName' => $productInfo['product_name'] ?? null,
                'productDescription' => $productInfo['product_description'] ?? null,
                'productCategory' => $productInfo['product_category'] ?? null,
                'productType' => $productInfo['product_type'] ?? null,
                'gender' => $productInfo['product_gender'] ?? null,
                'styleNumber' => $productInfo['style_number'] ?? null,
                'season' => $productInfo['season'] ?? null,
                'countryOfOrigin' => $productInfo['country_of_origin'] ?? null,
                'imageUrl' => $productInfo['product_image_url'] ?? null,
                'variants' => $productInfo['variants'] ?? [],
            ],
            'materialInformation' => [
                'compositions' => $compositions,
                'certifications' => $certifications,
            ],
            'supplyChainInformation' => [
                'steps' => array_map(fn($step) => [
                    'processStage' => $step['process_stage'],
                    'supplierName' => $step['supplier_name'],
                    'country' => $step['country'],
                    'facilityId' => $step['facility_id'],
                ], $supplyChain['material_supply_chain'] ?? []),
            ],
            'careInformation' => $care ? [
                'careImageUrl' => $care['care_image_url'] ?? null,
                'careText' => $care['care_text'] ?? null,
                'safetyInformation' => $care['safety_information'] ?? null,
            ] : null,
            'complianceInformation' => $compliance ? [
                'containsSvhc' => (bool)($compliance['contains_svhc'] ?? false),
                'svhcDetails' => $compliance['svhc_details'] ?? null,
                'scan4chemLink' => $compliance['scan4chem_link'] ?? null,
                'shedsMicrofibers' => $compliance['sheds_microfibers'] ?? null,
                'traceabilityProvider' => $compliance['traceability_provider'] ?? null,
            ] : null,
            'circularityInformation' => $circularity ? [
                'performance' => $circularity['performance'] ?? null,
                'recyclability' => $circularity['recyclability'] ?? null,
                'takeBackInstructions' => $circularity['take_back_instructions'] ?? null,
                'recyclingInstructions' => $circularity['recycling_instructions'] ?? null,
                'repairInstructions' => $circularity['repair_instructions'] ?? null,
            ] : null,
            'sustainabilityInformation' => $sustainability ? [
                'brandStatement' => $sustainability['brand_statement'] ?? null,
                'brandStatementLink' => $sustainability['brand_statement_link'] ?? null,
                'environmentalFootprint' => $sustainability['environmental_footprint'] ?? null,
            ] : null,
        ];
    }
}
