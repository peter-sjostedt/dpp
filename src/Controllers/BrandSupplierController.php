<?php

namespace App\Controllers;

use App\Config\TenantContext;
use App\Helpers\Response;
use App\Helpers\Validator;

/**
 * Controller for managing brand-supplier relationships.
 * Brands can create/manage relationships with suppliers.
 * Suppliers can view their brand relationships.
 */
class BrandSupplierController extends TenantAwareController
{
    /**
     * List all brand-supplier relationships for current tenant
     */
    public function index(array $params): void
    {
        if (TenantContext::isBrand()) {
            // Brand sees their supplier relationships
            $stmt = $this->db->prepare(
                'SELECT bs.id, bs.brand_id, bs.supplier_id, bs._is_active, bs.created_at,
                        s.supplier_name, s.supplier_location
                 FROM brand_suppliers bs
                 JOIN suppliers s ON bs.supplier_id = s.id
                 WHERE bs.brand_id = ?
                 ORDER BY s.supplier_name'
            );
            $stmt->execute([TenantContext::getBrandId()]);
        } else {
            // Supplier sees their brand relationships
            $stmt = $this->db->prepare(
                'SELECT bs.id, bs.brand_id, bs.supplier_id, bs._is_active, bs.created_at,
                        b.brand_name, b.logo_url
                 FROM brand_suppliers bs
                 JOIN brands b ON bs.brand_id = b.id
                 WHERE bs.supplier_id = ?
                 ORDER BY b.brand_name'
            );
            $stmt->execute([TenantContext::getSupplierId()]);
        }

        Response::success($stmt->fetchAll());
    }

    /**
     * Get a specific relationship
     */
    public function show(array $params): void
    {
        $id = (int) $params['id'];

        if (TenantContext::isBrand()) {
            $stmt = $this->db->prepare(
                'SELECT bs.*, s.supplier_name, s.supplier_location
                 FROM brand_suppliers bs
                 JOIN suppliers s ON bs.supplier_id = s.id
                 WHERE bs.id = ? AND bs.brand_id = ?'
            );
            $stmt->execute([$id, TenantContext::getBrandId()]);
        } else {
            $stmt = $this->db->prepare(
                'SELECT bs.*, b.brand_name, b.logo_url
                 FROM brand_suppliers bs
                 JOIN brands b ON bs.brand_id = b.id
                 WHERE bs.id = ? AND bs.supplier_id = ?'
            );
            $stmt->execute([$id, TenantContext::getSupplierId()]);
        }

        $relation = $stmt->fetch();
        if (!$relation) {
            Response::error('Relationship not found', 404);
            return;
        }

        Response::success($relation);
    }

    /**
     * Create a new brand-supplier relationship (brand only)
     */
    public function create(array $params): void
    {
        $this->requireBrand();

        $data = Validator::getJsonBody();

        if ($error = Validator::required($data, ['supplier_id'])) {
            Response::error($error);
            return;
        }

        $supplierId = (int) $data['supplier_id'];

        // Verify supplier exists and is active
        $stmt = $this->db->prepare(
            'SELECT id, supplier_name FROM suppliers WHERE id = ? AND _is_active = TRUE'
        );
        $stmt->execute([$supplierId]);
        $supplier = $stmt->fetch();

        if (!$supplier) {
            Response::error('Supplier not found or inactive', 404);
            return;
        }

        // Check if relationship already exists
        $stmt = $this->db->prepare(
            'SELECT id, _is_active FROM brand_suppliers WHERE brand_id = ? AND supplier_id = ?'
        );
        $stmt->execute([TenantContext::getBrandId(), $supplierId]);
        $existing = $stmt->fetch();

        if ($existing) {
            if ($existing['_is_active']) {
                Response::error('Relationship already exists', 409);
                return;
            }

            // Reactivate existing relationship
            $stmt = $this->db->prepare(
                'UPDATE brand_suppliers SET _is_active = TRUE, updated_at = NOW() WHERE id = ?'
            );
            $stmt->execute([$existing['id']]);

            $this->show(['id' => $existing['id']]);
            return;
        }

        // Create new relationship
        $stmt = $this->db->prepare(
            'INSERT INTO brand_suppliers (brand_id, supplier_id, _is_active)
             VALUES (?, ?, TRUE)'
        );
        $stmt->execute([TenantContext::getBrandId(), $supplierId]);

        $newId = $this->db->lastInsertId();
        $this->show(['id' => $newId]);
    }

    /**
     * Activate a relationship (brand only)
     */
    public function activate(array $params): void
    {
        $this->requireBrand();

        $id = (int) $params['id'];

        $stmt = $this->db->prepare(
            'UPDATE brand_suppliers SET _is_active = TRUE, updated_at = NOW()
             WHERE id = ? AND brand_id = ?'
        );
        $stmt->execute([$id, TenantContext::getBrandId()]);

        if ($stmt->rowCount() === 0) {
            Response::error('Relationship not found', 404);
            return;
        }

        $this->show(['id' => $id]);
    }

    /**
     * Deactivate a relationship (brand only)
     */
    public function deactivate(array $params): void
    {
        $this->requireBrand();

        $id = (int) $params['id'];

        $stmt = $this->db->prepare(
            'UPDATE brand_suppliers SET _is_active = FALSE, updated_at = NOW()
             WHERE id = ? AND brand_id = ?'
        );
        $stmt->execute([$id, TenantContext::getBrandId()]);

        if ($stmt->rowCount() === 0) {
            Response::error('Relationship not found', 404);
            return;
        }

        $this->show(['id' => $id]);
    }

    /**
     * Delete a relationship permanently (brand only)
     */
    public function delete(array $params): void
    {
        $this->requireBrand();

        $id = (int) $params['id'];

        // Check if there are any batches using this supplier
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) as count FROM batches b
             WHERE b.supplier_id = (SELECT supplier_id FROM brand_suppliers WHERE id = ?)
             AND b.brand_id = ?'
        );
        $stmt->execute([$id, TenantContext::getBrandId()]);
        $usage = $stmt->fetch();

        if ($usage && $usage['count'] > 0) {
            Response::error('Cannot delete: supplier is used in ' . $usage['count'] . ' batch(es). Deactivate instead.', 409);
            return;
        }

        $stmt = $this->db->prepare(
            'DELETE FROM brand_suppliers WHERE id = ? AND brand_id = ?'
        );
        $stmt->execute([$id, TenantContext::getBrandId()]);

        if ($stmt->rowCount() === 0) {
            Response::error('Relationship not found', 404);
            return;
        }

        Response::success(['deleted' => $id]);
    }

    /**
     * List available suppliers that brand can add (not yet related)
     */
    public function available(array $params): void
    {
        $this->requireBrand();

        $stmt = $this->db->prepare(
            'SELECT s.id, s.supplier_name, s.supplier_location
             FROM suppliers s
             WHERE s._is_active = TRUE
             AND s.id NOT IN (
                 SELECT supplier_id FROM brand_suppliers
                 WHERE brand_id = ? AND _is_active = TRUE
             )
             ORDER BY s.supplier_name'
        );
        $stmt->execute([TenantContext::getBrandId()]);

        Response::success($stmt->fetchAll());
    }
}
