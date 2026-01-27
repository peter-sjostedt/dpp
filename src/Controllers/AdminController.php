<?php

namespace App\Controllers;

use App\Config\AdminAuth;
use App\Config\Database;
use App\Helpers\Response;
use App\Helpers\Validator;

/**
 * Admin controller for managing brands, suppliers, and relations.
 * Requires master key authentication via X-Admin-Key header.
 */
class AdminController
{
    protected \PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // ============================================
    // BRANDS
    // ============================================

    public function listBrands(array $params): void
    {
        $stmt = $this->db->query(
            'SELECT id, brand_name, logo_url, sub_brand, parent_company,
                    trader, trader_location, lei, gs1_company_prefix,
                    api_key, _is_active, _last_used_at, _expires_at,
                    created_at, updated_at
             FROM brands ORDER BY brand_name'
        );
        Response::success($stmt->fetchAll());
    }

    public function showBrand(array $params): void
    {
        $id = (int) $params['id'];
        $stmt = $this->db->prepare('SELECT * FROM brands WHERE id = ?');
        $stmt->execute([$id]);
        $brand = $stmt->fetch();

        if (!$brand) {
            Response::error('Brand not found', 404);
            return;
        }

        Response::success($brand);
    }

    public function createBrand(array $params): void
    {
        $data = Validator::getJsonBody();

        if (empty($data['brand_name'])) {
            Response::error('brand_name is required');
            return;
        }

        // Validate LEI if provided
        if (!empty($data['lei'])) {
            if (!preg_match('/^[A-Z0-9]{20}$/', $data['lei'])) {
                Response::error('Invalid LEI format. Must be exactly 20 alphanumeric characters (A-Z, 0-9).');
                return;
            }
        }

        // Validate GS1 Company Prefix if provided
        if (!empty($data['gs1_company_prefix'])) {
            if (!preg_match('/^[0-9]{6,12}$/', $data['gs1_company_prefix'])) {
                Response::error('Invalid GS1 Company Prefix. Must be 6-12 digits.');
                return;
            }
        }

        // Generate API key
        $apiKey = AdminAuth::generateApiKey('brand');

        $stmt = $this->db->prepare(
            'INSERT INTO brands (brand_name, logo_url, sub_brand, parent_company,
                                trader, trader_location, lei, gs1_company_prefix,
                                api_key, _is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, TRUE)'
        );
        $stmt->execute([
            $data['brand_name'],
            $data['logo_url'] ?? null,
            $data['sub_brand'] ?? null,
            $data['parent_company'] ?? null,
            $data['trader'] ?? null,
            $data['trader_location'] ?? null,
            $data['lei'] ?? null,
            $data['gs1_company_prefix'] ?? null,
            $apiKey
        ]);

        $id = $this->db->lastInsertId();
        $this->showBrand(['id' => $id]);
    }

    public function updateBrand(array $params): void
    {
        $id = (int) $params['id'];
        $data = Validator::getJsonBody();

        // Check brand exists
        $stmt = $this->db->prepare('SELECT id FROM brands WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            Response::error('Brand not found', 404);
            return;
        }

        // Validate LEI if provided
        if (isset($data['lei']) && $data['lei'] !== null && $data['lei'] !== '') {
            if (!preg_match('/^[A-Z0-9]{20}$/', $data['lei'])) {
                Response::error('Invalid LEI format. Must be exactly 20 alphanumeric characters (A-Z, 0-9).');
                return;
            }
        }

        // Validate GS1 Company Prefix if provided
        if (isset($data['gs1_company_prefix']) && $data['gs1_company_prefix'] !== null && $data['gs1_company_prefix'] !== '') {
            if (!preg_match('/^[0-9]{6,12}$/', $data['gs1_company_prefix'])) {
                Response::error('Invalid GS1 Company Prefix. Must be 6-12 digits.');
                return;
            }
        }

        $stmt = $this->db->prepare(
            'UPDATE brands SET
                brand_name = COALESCE(?, brand_name),
                logo_url = COALESCE(?, logo_url),
                sub_brand = COALESCE(?, sub_brand),
                parent_company = COALESCE(?, parent_company),
                trader = COALESCE(?, trader),
                trader_location = COALESCE(?, trader_location),
                lei = COALESCE(?, lei),
                gs1_company_prefix = COALESCE(?, gs1_company_prefix),
                _is_active = COALESCE(?, _is_active),
                _expires_at = COALESCE(?, _expires_at)
             WHERE id = ?'
        );
        $stmt->execute([
            $data['brand_name'] ?? null,
            $data['logo_url'] ?? null,
            $data['sub_brand'] ?? null,
            $data['parent_company'] ?? null,
            $data['trader'] ?? null,
            $data['trader_location'] ?? null,
            $data['lei'] ?? null,
            $data['gs1_company_prefix'] ?? null,
            isset($data['_is_active']) ? ($data['_is_active'] ? 1 : 0) : null,
            $data['_expires_at'] ?? null,
            $id
        ]);

        $this->showBrand(['id' => $id]);
    }

    public function deleteBrand(array $params): void
    {
        $id = (int) $params['id'];

        // Check brand exists
        $stmt = $this->db->prepare('SELECT id FROM brands WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            Response::error('Brand not found', 404);
            return;
        }

        // Check for related products
        $stmt = $this->db->prepare('SELECT COUNT(*) as cnt FROM products WHERE brand_id = ?');
        $stmt->execute([$id]);
        $count = $stmt->fetch()['cnt'];

        if ($count > 0) {
            Response::error("Cannot delete brand with {$count} associated products. Delete products first.", 400);
            return;
        }

        $stmt = $this->db->prepare('DELETE FROM brands WHERE id = ?');
        $stmt->execute([$id]);

        Response::success(['message' => 'Brand deleted', 'id' => $id]);
    }

    public function regenerateBrandApiKey(array $params): void
    {
        $id = (int) $params['id'];

        // Check brand exists
        $stmt = $this->db->prepare('SELECT id FROM brands WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            Response::error('Brand not found', 404);
            return;
        }

        $newApiKey = AdminAuth::generateApiKey('brand');

        $stmt = $this->db->prepare('UPDATE brands SET api_key = ? WHERE id = ?');
        $stmt->execute([$newApiKey, $id]);

        Response::success(['id' => $id, 'api_key' => $newApiKey]);
    }

    // ============================================
    // SUPPLIERS
    // ============================================

    public function listSuppliers(array $params): void
    {
        $stmt = $this->db->query(
            'SELECT id, supplier_name, supplier_location,
                    facility_registry, facility_identifier,
                    operator_registry, operator_identifier,
                    country_of_origin_confection, country_of_origin_dyeing, country_of_origin_weaving,
                    lei, gs1_company_prefix,
                    api_key, _is_active, _last_used_at, _expires_at,
                    created_at, updated_at
             FROM suppliers ORDER BY supplier_name'
        );
        Response::success($stmt->fetchAll());
    }

    public function showSupplier(array $params): void
    {
        $id = (int) $params['id'];
        $stmt = $this->db->prepare('SELECT * FROM suppliers WHERE id = ?');
        $stmt->execute([$id]);
        $supplier = $stmt->fetch();

        if (!$supplier) {
            Response::error('Supplier not found', 404);
            return;
        }

        Response::success($supplier);
    }

    public function createSupplier(array $params): void
    {
        $data = Validator::getJsonBody();

        if (empty($data['supplier_name'])) {
            Response::error('supplier_name is required');
            return;
        }

        // Validate LEI if provided
        if (!empty($data['lei'])) {
            if (!preg_match('/^[A-Z0-9]{20}$/', $data['lei'])) {
                Response::error('Invalid LEI format. Must be exactly 20 alphanumeric characters (A-Z, 0-9).');
                return;
            }
        }

        // Validate GS1 Company Prefix if provided
        if (!empty($data['gs1_company_prefix'])) {
            if (!preg_match('/^[0-9]{6,12}$/', $data['gs1_company_prefix'])) {
                Response::error('Invalid GS1 Company Prefix. Must be 6-12 digits.');
                return;
            }
        }

        // Generate API key
        $apiKey = AdminAuth::generateApiKey('supplier');

        $stmt = $this->db->prepare(
            'INSERT INTO suppliers (supplier_name, supplier_location,
                                   facility_registry, facility_identifier,
                                   operator_registry, operator_identifier,
                                   country_of_origin_confection, country_of_origin_dyeing, country_of_origin_weaving,
                                   lei, gs1_company_prefix, api_key, _is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, TRUE)'
        );
        $stmt->execute([
            $data['supplier_name'],
            $data['supplier_location'] ?? null,
            $data['facility_registry'] ?? null,
            $data['facility_identifier'] ?? null,
            $data['operator_registry'] ?? null,
            $data['operator_identifier'] ?? null,
            $data['country_of_origin_confection'] ?? null,
            $data['country_of_origin_dyeing'] ?? null,
            $data['country_of_origin_weaving'] ?? null,
            $data['lei'] ?? null,
            $data['gs1_company_prefix'] ?? null,
            $apiKey
        ]);

        $id = $this->db->lastInsertId();
        $this->showSupplier(['id' => $id]);
    }

    public function updateSupplier(array $params): void
    {
        $id = (int) $params['id'];
        $data = Validator::getJsonBody();

        // Check supplier exists
        $stmt = $this->db->prepare('SELECT id FROM suppliers WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            Response::error('Supplier not found', 404);
            return;
        }

        // Validate LEI if provided
        if (isset($data['lei']) && $data['lei'] !== null && $data['lei'] !== '') {
            if (!preg_match('/^[A-Z0-9]{20}$/', $data['lei'])) {
                Response::error('Invalid LEI format. Must be exactly 20 alphanumeric characters (A-Z, 0-9).');
                return;
            }
        }

        // Validate GS1 Company Prefix if provided
        if (isset($data['gs1_company_prefix']) && $data['gs1_company_prefix'] !== null && $data['gs1_company_prefix'] !== '') {
            if (!preg_match('/^[0-9]{6,12}$/', $data['gs1_company_prefix'])) {
                Response::error('Invalid GS1 Company Prefix. Must be 6-12 digits.');
                return;
            }
        }

        $stmt = $this->db->prepare(
            'UPDATE suppliers SET
                supplier_name = COALESCE(?, supplier_name),
                supplier_location = COALESCE(?, supplier_location),
                facility_registry = COALESCE(?, facility_registry),
                facility_identifier = COALESCE(?, facility_identifier),
                operator_registry = COALESCE(?, operator_registry),
                operator_identifier = COALESCE(?, operator_identifier),
                country_of_origin_confection = COALESCE(?, country_of_origin_confection),
                country_of_origin_dyeing = COALESCE(?, country_of_origin_dyeing),
                country_of_origin_weaving = COALESCE(?, country_of_origin_weaving),
                lei = COALESCE(?, lei),
                gs1_company_prefix = COALESCE(?, gs1_company_prefix),
                _is_active = COALESCE(?, _is_active),
                _expires_at = COALESCE(?, _expires_at)
             WHERE id = ?'
        );
        $stmt->execute([
            $data['supplier_name'] ?? null,
            $data['supplier_location'] ?? null,
            $data['facility_registry'] ?? null,
            $data['facility_identifier'] ?? null,
            $data['operator_registry'] ?? null,
            $data['operator_identifier'] ?? null,
            $data['country_of_origin_confection'] ?? null,
            $data['country_of_origin_dyeing'] ?? null,
            $data['country_of_origin_weaving'] ?? null,
            $data['lei'] ?? null,
            $data['gs1_company_prefix'] ?? null,
            isset($data['_is_active']) ? ($data['_is_active'] ? 1 : 0) : null,
            $data['_expires_at'] ?? null,
            $id
        ]);

        $this->showSupplier(['id' => $id]);
    }

    public function deleteSupplier(array $params): void
    {
        $id = (int) $params['id'];

        // Check supplier exists
        $stmt = $this->db->prepare('SELECT id FROM suppliers WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            Response::error('Supplier not found', 404);
            return;
        }

        // Check for related materials
        $stmt = $this->db->prepare('SELECT COUNT(*) as cnt FROM factory_materials WHERE supplier_id = ?');
        $stmt->execute([$id]);
        $count = $stmt->fetch()['cnt'];

        if ($count > 0) {
            Response::error("Cannot delete supplier with {$count} associated materials. Delete materials first.", 400);
            return;
        }

        $stmt = $this->db->prepare('DELETE FROM suppliers WHERE id = ?');
        $stmt->execute([$id]);

        Response::success(['message' => 'Supplier deleted', 'id' => $id]);
    }

    public function regenerateSupplierApiKey(array $params): void
    {
        $id = (int) $params['id'];

        // Check supplier exists
        $stmt = $this->db->prepare('SELECT id FROM suppliers WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            Response::error('Supplier not found', 404);
            return;
        }

        $newApiKey = AdminAuth::generateApiKey('supplier');

        $stmt = $this->db->prepare('UPDATE suppliers SET api_key = ? WHERE id = ?');
        $stmt->execute([$newApiKey, $id]);

        Response::success(['id' => $id, 'api_key' => $newApiKey]);
    }

    // ============================================
    // BRAND-SUPPLIER RELATIONS
    // ============================================

    public function listRelations(array $params): void
    {
        $stmt = $this->db->query(
            'SELECT bs.*, br.brand_name, s.supplier_name
             FROM brand_suppliers bs
             JOIN brands br ON bs.brand_id = br.id
             JOIN suppliers s ON bs.supplier_id = s.id
             ORDER BY br.brand_name, s.supplier_name'
        );
        Response::success($stmt->fetchAll());
    }

    public function showRelation(array $params): void
    {
        $id = (int) $params['id'];
        $stmt = $this->db->prepare(
            'SELECT bs.*, br.brand_name, s.supplier_name
             FROM brand_suppliers bs
             JOIN brands br ON bs.brand_id = br.id
             JOIN suppliers s ON bs.supplier_id = s.id
             WHERE bs.id = ?'
        );
        $stmt->execute([$id]);
        $relation = $stmt->fetch();

        if (!$relation) {
            Response::error('Relation not found', 404);
            return;
        }

        Response::success($relation);
    }

    public function createRelation(array $params): void
    {
        $data = Validator::getJsonBody();

        if (empty($data['brand_id']) || empty($data['supplier_id'])) {
            Response::error('brand_id and supplier_id are required');
            return;
        }

        // Check brand exists
        $stmt = $this->db->prepare('SELECT id FROM brands WHERE id = ?');
        $stmt->execute([$data['brand_id']]);
        if (!$stmt->fetch()) {
            Response::error('Brand not found', 404);
            return;
        }

        // Check supplier exists
        $stmt = $this->db->prepare('SELECT id FROM suppliers WHERE id = ?');
        $stmt->execute([$data['supplier_id']]);
        if (!$stmt->fetch()) {
            Response::error('Supplier not found', 404);
            return;
        }

        // Check if relation already exists
        $stmt = $this->db->prepare(
            'SELECT id FROM brand_suppliers WHERE brand_id = ? AND supplier_id = ?'
        );
        $stmt->execute([$data['brand_id'], $data['supplier_id']]);
        if ($stmt->fetch()) {
            Response::error('Relation already exists', 409);
            return;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO brand_suppliers (brand_id, supplier_id, _is_active)
             VALUES (?, ?, TRUE)'
        );
        $stmt->execute([$data['brand_id'], $data['supplier_id']]);

        $id = $this->db->lastInsertId();
        $this->showRelation(['id' => $id]);
    }

    public function updateRelation(array $params): void
    {
        $id = (int) $params['id'];
        $data = Validator::getJsonBody();

        // Check relation exists
        $stmt = $this->db->prepare('SELECT id FROM brand_suppliers WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            Response::error('Relation not found', 404);
            return;
        }

        $stmt = $this->db->prepare(
            'UPDATE brand_suppliers SET
                _is_active = COALESCE(?, _is_active)
             WHERE id = ?'
        );
        $stmt->execute([
            isset($data['_is_active']) ? ($data['_is_active'] ? 1 : 0) : null,
            $id
        ]);

        $this->showRelation(['id' => $id]);
    }

    public function deleteRelation(array $params): void
    {
        $id = (int) $params['id'];

        // Check relation exists
        $stmt = $this->db->prepare('SELECT id FROM brand_suppliers WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            Response::error('Relation not found', 404);
            return;
        }

        $stmt = $this->db->prepare('DELETE FROM brand_suppliers WHERE id = ?');
        $stmt->execute([$id]);

        Response::success(['message' => 'Relation deleted', 'id' => $id]);
    }

    // ============================================
    // STATISTICS
    // ============================================

    public function getStats(array $params): void
    {
        $stats = [];

        $tables = ['brands', 'suppliers', 'brand_suppliers', 'products',
                   'product_variants', 'batches', 'items', 'factory_materials'];

        foreach ($tables as $table) {
            $stmt = $this->db->query("SELECT COUNT(*) as cnt FROM {$table}");
            $stats[$table] = (int) $stmt->fetch()['cnt'];
        }

        // Active brands/suppliers
        $stmt = $this->db->query('SELECT COUNT(*) as cnt FROM brands WHERE _is_active = TRUE');
        $stats['active_brands'] = (int) $stmt->fetch()['cnt'];

        $stmt = $this->db->query('SELECT COUNT(*) as cnt FROM suppliers WHERE _is_active = TRUE');
        $stats['active_suppliers'] = (int) $stmt->fetch()['cnt'];

        Response::success($stats);
    }
}
