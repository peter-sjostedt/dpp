<?php
namespace App\Controllers;

use App\Config\TenantContext;
use App\Helpers\Response;
use App\Helpers\Validator;

/**
 * Supplier controller with multi-tenant access control.
 * - As Supplier: Full CRUD on own supplier
 * - As Brand: Read-only access to related suppliers
 */
class SupplierController extends TenantAwareController
{
    public function index(array $params): void
    {
        if (TenantContext::isSupplier()) {
            // Supplier sees only their own supplier
            $stmt = $this->db->prepare('SELECT * FROM suppliers WHERE id = ?');
            $stmt->execute([TenantContext::getSupplierId()]);
        } else {
            // Brand sees suppliers they have relationships with
            $stmt = $this->db->prepare(
                'SELECT DISTINCT s.*
                 FROM suppliers s
                 JOIN brand_suppliers bs ON s.id = bs.supplier_id
                 WHERE bs.brand_id = ? AND bs._is_active = TRUE AND s._is_active = TRUE
                 ORDER BY s.supplier_name'
            );
            $stmt->execute([TenantContext::getBrandId()]);
        }

        Response::success($stmt->fetchAll());
    }

    public function show(array $params): void
    {
        $id = (int) $params['id'];

        if (TenantContext::isSupplier()) {
            // Supplier can only see their own supplier
            if ($id !== TenantContext::getSupplierId()) {
                Response::error('Supplier not found', 404);
                return;
            }
            $stmt = $this->db->prepare('SELECT * FROM suppliers WHERE id = ?');
            $stmt->execute([$id]);
        } else {
            // Brand can see suppliers they have relationship with
            if (!$this->canAccessSupplier($id)) {
                Response::error('Supplier not found', 404);
                return;
            }
            $stmt = $this->db->prepare('SELECT * FROM suppliers WHERE id = ?');
            $stmt->execute([$id]);
        }

        $supplier = $stmt->fetch();
        if (!$supplier) {
            Response::error('Supplier not found', 404);
            return;
        }

        Response::success($supplier);
    }

    public function create(array $params): void
    {
        // Suppliers cannot create other suppliers via API
        // Brands cannot create suppliers
        Response::error('Supplier creation not allowed via API. Contact administrator.', 403);
    }

    public function update(array $params): void
    {
        $this->requireSupplier();

        $id = (int) $params['id'];

        // Can only update own supplier
        if ($id !== TenantContext::getSupplierId()) {
            Response::error('Supplier not found', 404);
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
            'UPDATE suppliers SET
                supplier_name = COALESCE(?, supplier_name),
                supplier_location = COALESCE(?, supplier_location),
                facility_registry = COALESCE(?, facility_registry),
                facility_identifier = COALESCE(?, facility_identifier),
                operator_registry = COALESCE(?, operator_registry),
                operator_identifier = COALESCE(?, operator_identifier),
                lei = COALESCE(?, lei),
                gs1_company_prefix = COALESCE(?, gs1_company_prefix)
             WHERE id = ?'
        );
        $stmt->execute([
            $data['supplier_name'] ?? null,
            $data['supplier_location'] ?? null,
            $data['facility_registry'] ?? null,
            $data['facility_identifier'] ?? null,
            $data['operator_registry'] ?? null,
            $data['operator_identifier'] ?? null,
            $data['lei'] ?? null,
            $data['gs1_company_prefix'] ?? null,
            $id
        ]);

        $this->show(['id' => $id]);
    }

    public function delete(array $params): void
    {
        // Suppliers cannot be deleted via API
        Response::error('Supplier deletion not allowed via API. Contact administrator.', 403);
    }
}
