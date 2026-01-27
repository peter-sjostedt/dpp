<?php
namespace App\Controllers;

use App\Config\TenantContext;
use App\Helpers\Response;
use App\Helpers\Validator;

/**
 * Brand controller with multi-tenant access control.
 * - As Brand: Full CRUD on own brand
 * - As Supplier: Read-only access to related brands
 */
class BrandController extends TenantAwareController
{
    public function index(array $params): void
    {
        if (TenantContext::isBrand()) {
            // Brand sees only their own brand (that has products with items)
            $stmt = $this->db->prepare(
                'SELECT DISTINCT br.*
                 FROM brands br
                 JOIN products p ON p.brand_id = br.id
                 JOIN batches b ON b.product_id = p.id
                 JOIN items i ON i.batch_id = b.id
                 WHERE br.id = ?
                 ORDER BY br.brand_name'
            );
            $stmt->execute([TenantContext::getBrandId()]);
        } else {
            // Supplier sees brands they have relationships with
            $stmt = $this->db->prepare(
                'SELECT DISTINCT br.*
                 FROM brands br
                 JOIN brand_suppliers bs ON br.id = bs.brand_id
                 WHERE bs.supplier_id = ? AND bs._is_active = TRUE
                 ORDER BY br.brand_name'
            );
            $stmt->execute([TenantContext::getSupplierId()]);
        }

        Response::success($stmt->fetchAll());
    }

    /**
     * Get all brands (without item filter) - for dropdowns
     */
    public function indexAll(array $params): void
    {
        if (TenantContext::isBrand()) {
            // Brand sees only their own brand
            $stmt = $this->db->prepare('SELECT * FROM brands WHERE id = ?');
            $stmt->execute([TenantContext::getBrandId()]);
        } else {
            // Supplier sees brands they have relationships with
            $stmt = $this->db->prepare(
                'SELECT DISTINCT br.*
                 FROM brands br
                 JOIN brand_suppliers bs ON br.id = bs.brand_id
                 WHERE bs.supplier_id = ? AND bs._is_active = TRUE
                 ORDER BY br.brand_name'
            );
            $stmt->execute([TenantContext::getSupplierId()]);
        }

        Response::success($stmt->fetchAll());
    }

    public function show(array $params): void
    {
        $id = (int) $params['id'];

        if (TenantContext::isBrand()) {
            // Brand can only see their own brand
            if ($id !== TenantContext::getBrandId()) {
                Response::error('Brand not found', 404);
                return;
            }
            $stmt = $this->db->prepare('SELECT * FROM brands WHERE id = ?');
            $stmt->execute([$id]);
        } else {
            // Supplier can see brands they have relationship with
            if (!$this->canAccessBrand($id)) {
                Response::error('Brand not found', 404);
                return;
            }
            $stmt = $this->db->prepare('SELECT * FROM brands WHERE id = ?');
            $stmt->execute([$id]);
        }

        $brand = $stmt->fetch();
        if (!$brand) {
            Response::error('Brand not found', 404);
            return;
        }

        Response::success($brand);
    }

    public function create(array $params): void
    {
        // Brands cannot create other brands via API
        // Suppliers cannot create brands
        Response::error('Brand creation not allowed via API. Contact administrator.', 403);
    }

    public function update(array $params): void
    {
        $this->requireBrand();

        $id = (int) $params['id'];

        // Can only update own brand
        if ($id !== TenantContext::getBrandId()) {
            Response::error('Brand not found', 404);
            return;
        }

        $data = Validator::getJsonBody();

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
                gs1_company_prefix = COALESCE(?, gs1_company_prefix)
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
            $id
        ]);

        $this->show(['id' => $id]);
    }

    public function delete(array $params): void
    {
        // Brands cannot be deleted via API
        Response::error('Brand deletion not allowed via API. Contact administrator.', 403);
    }
}
