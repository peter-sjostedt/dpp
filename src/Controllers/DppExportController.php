<?php
namespace App\Controllers;

use App\Helpers\Response;

/**
 * DPP Export Controller - Generates Digital Product Passport data
 * Only available to brands (not suppliers)
 */
class DppExportController extends TenantAwareController
{
    /**
     * Preview complete DPP data for a product
     */
    public function preview(array $params): void
    {
        $this->requireBrand();

        $productId = (int) $params['id'];

        if (!$this->verifyProductOwnership($productId)) {
            Response::error('Product not found', 404);
            return;
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
    public function validate(array $params): void
    {
        $this->requireBrand();

        $productId = (int) $params['id'];

        if (!$this->verifyProductOwnership($productId)) {
            Response::error('Product not found', 404);
            return;
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
     * Export DPP in structured format
     */
    public function export(array $params): void
    {
        $this->requireBrand();

        $productId = (int) $params['id'];

        if (!$this->verifyProductOwnership($productId)) {
            Response::error('Product not found', 404);
            return;
        }

        $dpp = $this->buildDppExport($productId);

        Response::success([
            'format' => 'DPP',
            'version' => '1.0',
            'exported_at' => date('c'),
            'dpp' => $dpp
        ]);
    }

    // ========== Helper Methods ==========

    private function getBrandInfo(int $productId): array
    {
        $stmt = $this->db->prepare(
            'SELECT b.brand_name, b.logo_url, b.sub_brand, b.parent_company,
                    b.trader, b.trader_location, b.lei, b.gs1_company_prefix
             FROM products p
             JOIN brands b ON p.brand_id = b.id
             WHERE p.id = ?'
        );
        $stmt->execute([$productId]);
        return $stmt->fetch() ?: [];
    }

    private function getProductInfo(int $productId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM products WHERE id = ?');
        $stmt->execute([$productId]);
        $product = $stmt->fetch() ?: [];

        // Get variants
        if ($product) {
            $stmt = $this->db->prepare(
                'SELECT id, item_number, gtin, size, size_country_code, color_brand, color_general
                 FROM product_variants WHERE product_id = ? AND _is_active = TRUE'
            );
            $stmt->execute([$productId]);
            $product['variants'] = $stmt->fetchAll();
        }

        return $product;
    }

    private function getMaterialInfo(int $productId): array
    {
        // Get materials linked via batches
        $stmt = $this->db->prepare(
            'SELECT DISTINCT fm.*, s.supplier_name, s.supplier_location
             FROM factory_materials fm
             JOIN suppliers s ON fm.supplier_id = s.id
             JOIN batch_materials bm ON bm.factory_material_id = fm.id
             JOIN batches b ON b.id = bm.batch_id
             WHERE b.product_id = ?'
        );
        $stmt->execute([$productId]);
        $materials = $stmt->fetchAll();

        // Enrich each material with compositions and certifications
        foreach ($materials as &$material) {
            $stmt = $this->db->prepare(
                'SELECT content_name, content_value, content_source,
                        recycled, recycled_percentage, recycled_input_source
                 FROM factory_material_compositions WHERE factory_material_id = ?'
            );
            $stmt->execute([$material['id']]);
            $material['compositions'] = $stmt->fetchAll();

            $stmt = $this->db->prepare(
                'SELECT certification, certification_id, valid_until
                 FROM factory_material_certifications WHERE factory_material_id = ?'
            );
            $stmt->execute([$material['id']]);
            $material['certifications'] = $stmt->fetchAll();
        }

        return $materials;
    }

    private function getSupplyChainInfo(int $productId): array
    {
        // Get supply chain from materials (raw material traceability)
        $stmt = $this->db->prepare(
            'SELECT DISTINCT fmsc.*, fm.material_name
             FROM factory_material_supply_chain fmsc
             JOIN factory_materials fm ON fm.id = fmsc.factory_material_id
             JOIN batch_materials bm ON bm.factory_material_id = fm.id
             JOIN batches b ON b.id = bm.batch_id
             WHERE b.product_id = ?
             ORDER BY fmsc.sequence'
        );
        $stmt->execute([$productId]);
        $materialSupplyChain = $stmt->fetchAll();

        // Get raw material suppliers
        $stmt = $this->db->prepare(
            'SELECT DISTINCT s.supplier_name, s.supplier_location,
                    s.facility_registry, s.facility_identifier,
                    s.country_of_origin_confection, s.country_of_origin_dyeing, s.country_of_origin_weaving
             FROM suppliers s
             JOIN factory_materials fm ON fm.supplier_id = s.id
             JOIN batch_materials bm ON bm.factory_material_id = fm.id
             JOIN batches b ON b.id = bm.batch_id
             WHERE b.product_id = ?'
        );
        $stmt->execute([$productId]);
        $materialSuppliers = $stmt->fetchAll();

        // Get confection/production suppliers (directly from batches)
        $stmt = $this->db->prepare(
            'SELECT DISTINCT s.supplier_name, s.supplier_location,
                    s.facility_registry, s.facility_identifier,
                    s.country_of_origin_confection, s.country_of_origin_dyeing, s.country_of_origin_weaving
             FROM suppliers s
             JOIN batches b ON b.supplier_id = s.id
             WHERE b.product_id = ?'
        );
        $stmt->execute([$productId]);
        $confectionSuppliers = $stmt->fetchAll();

        return [
            'material_supply_chain' => $materialSupplyChain,
            'material_suppliers' => $materialSuppliers,
            'confection_suppliers' => $confectionSuppliers
        ];
    }

    private function getCareInfo(int $productId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM care_information WHERE product_id = ?');
        $stmt->execute([$productId]);
        return $stmt->fetch() ?: null;
    }

    private function getComplianceInfo(int $productId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM compliance_info WHERE product_id = ?');
        $stmt->execute([$productId]);
        return $stmt->fetch() ?: null;
    }

    private function getCircularityInfo(int $productId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM circularity_info WHERE product_id = ?');
        $stmt->execute([$productId]);
        return $stmt->fetch() ?: null;
    }

    private function getSustainabilityInfo(int $productId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM sustainability_info WHERE product_id = ?');
        $stmt->execute([$productId]);
        return $stmt->fetch() ?: null;
    }

    private function calculateCompleteness(int $productId): array
    {
        $sections = [
            'brand' => !empty($this->getBrandInfo($productId)['brand_name']),
            'product' => !empty($this->getProductInfo($productId)['product_name']),
            'materials' => !empty($this->getMaterialInfo($productId)),
            'supply_chain' => !empty($this->getSupplyChainInfo($productId)['material_supply_chain']) || !empty($this->getSupplyChainInfo($productId)['confection_suppliers']),
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

    private function buildDppExport(int $productId): array
    {
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
                    'materialName' => $material['material_name'],
                    'contentName' => $comp['content_name'],
                    'percentage' => (float) $comp['content_value'],
                    'source' => $comp['content_source'],
                    'isRecycled' => (bool) $comp['recycled'],
                    'recycledPercentage' => $comp['recycled_percentage'] ? (float) $comp['recycled_percentage'] : null,
                    'recycledSource' => $comp['recycled_input_source']
                ];
            }
        }

        // Build certifications array
        $certifications = [];
        foreach ($materials as $material) {
            foreach ($material['certifications'] ?? [] as $cert) {
                $certifications[] = [
                    'materialName' => $material['material_name'],
                    'certification' => $cert['certification'],
                    'certificationId' => $cert['certification_id'],
                    'validUntil' => $cert['valid_until']
                ];
            }
        }

        return [
            'brandInformation' => [
                'brandName' => $brandInfo['brand_name'] ?? null,
                'logoUrl' => $brandInfo['logo_url'] ?? null,
                'subBrand' => $brandInfo['sub_brand'] ?? null,
                'parentCompany' => $brandInfo['parent_company'] ?? null,
                'trader' => $brandInfo['trader'] ?? null,
                'traderLocation' => $brandInfo['trader_location'] ?? null,
                'lei' => $brandInfo['lei'] ?? null,
                'gs1CompanyPrefix' => $brandInfo['gs1_company_prefix'] ?? null,
            ],
            'productInformation' => [
                'productName' => $productInfo['product_name'] ?? null,
                'productDescription' => $productInfo['description'] ?? null,
                'productCategory' => $productInfo['category'] ?? null,
                // Trace4Value field names for DPP export
                'product_id_system' => $productInfo['gtin_type'] ?? null, // 300,00
                'product_id_value' => $productInfo['gtin'] ?? null,       // 300,10
                'countryOfOrigin' => $productInfo['country_of_origin'] ?? null,
                'imageUrl' => $productInfo['image_url'] ?? null,
                'variants' => $productInfo['variants'] ?? [],
            ],
            'materialInformation' => [
                'compositions' => $compositions,
                'certifications' => $certifications,
            ],
            'supplyChainInformation' => [
                'steps' => array_map(fn($step) => [
                    'sequence' => $step['sequence'],
                    'processStep' => $step['process_step'],
                    'country' => $step['country'],
                    'facilityName' => $step['facility_name'],
                    'facilityIdentifier' => $step['facility_identifier'],
                ], $supplyChain['material_supply_chain'] ?? []),
                'materialSuppliers' => $supplyChain['material_suppliers'] ?? [],
                'confectionSuppliers' => $supplyChain['confection_suppliers'] ?? [],
            ],
            'careInformation' => $care ? [
                'careImageUrl' => $care['care_image_url'] ?? null,
                'careText' => $care['care_text'] ?? null,
                'safetyInformation' => $care['safety_information'] ?? null,
            ] : null,
            'complianceInformation' => $compliance ? [
                'harmfulSubstances' => $compliance['harmful_substances'] ?? null,
                'harmfulSubstancesInfo' => $compliance['harmful_substances_info'] ?? null,
                'certifications' => $compliance['certifications'] ?? null,
                'chemicalComplianceStandard' => $compliance['chemical_compliance_standard'] ?? null,
                'chemicalComplianceLink' => $compliance['chemical_compliance_link'] ?? null,
                'microfibers' => $compliance['microfibers'] ?? null,
                'traceabilityProvider' => $compliance['traceability_provider'] ?? null,
            ] : null,
            'circularityInformation' => $circularity ? [
                'performance' => $circularity['performance'] ?? null,
                'recyclability' => $circularity['recyclability'] ?? null,
                'takeBackInstructions' => $circularity['take_back_instructions'] ?? null,
                'recyclingInstructions' => $circularity['recycling_instructions'] ?? null,
                'disassemblyInstructionsSorters' => $circularity['disassembly_instructions_sorters'] ?? null,
                'disassemblyInstructionsUser' => $circularity['disassembly_instructions_user'] ?? null,
                'circularDesignStrategy' => $circularity['circular_design_strategy'] ?? null,
                'circularDesignDescription' => $circularity['circular_design_description'] ?? null,
                'repairInstructions' => $circularity['repair_instructions'] ?? null,
            ] : null,
            'sustainabilityInformation' => $sustainability ? [
                'brandStatement' => $sustainability['brand_statement'] ?? null,
                'statementLink' => $sustainability['statement_link'] ?? null,
                'environmentalFootprint' => $sustainability['environmental_footprint'] ?? null,
            ] : null,
        ];
    }
}
