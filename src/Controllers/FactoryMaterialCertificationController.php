<?php
namespace App\Controllers;

use App\Config\TenantContext;
use App\Helpers\Response;
use App\Helpers\Validator;

class FactoryMaterialCertificationController extends TenantAwareController {

    /**
     * Check if certification belongs to a material owned by current supplier (for writes)
     */
    private function verifyCertificationOwnership(int|string $certificationId): bool {
        if (!TenantContext::isSupplier()) {
            return false;
        }

        $supplierId = TenantContext::getSupplierId();
        $stmt = $this->db->prepare(
            'SELECT fmc.id FROM factory_material_certifications fmc
             JOIN factory_materials fm ON fmc.factory_material_id = fm.id
             WHERE fmc.id = ? AND fm.supplier_id = ?'
        );
        $stmt->execute([$certificationId, $supplierId]);
        return (bool) $stmt->fetch();
    }

    /**
     * Check if current user can read a certification
     */
    private function canReadCertification(int|string $certificationId): bool {
        $stmt = $this->db->prepare(
            'SELECT factory_material_id FROM factory_material_certifications WHERE id = ?'
        );
        $stmt->execute([$certificationId]);
        $cert = $stmt->fetch();

        if (!$cert) {
            return false;
        }

        return $this->canAccessMaterial($cert['factory_material_id']);
    }

    public function index(array $params): void {
        // Both brands and suppliers can read via canAccessMaterial
        if (!$this->canAccessMaterial($params['materialId'])) {
            Response::error('Factory material not found', 404);
        }

        $stmt = $this->db->prepare(
            'SELECT * FROM factory_material_certifications WHERE factory_material_id = ? ORDER BY valid_until DESC'
        );
        $stmt->execute([$params['materialId']]);
        Response::success($stmt->fetchAll());
    }

    public function create(array $params): void {
        // Write operations require supplier authentication
        $this->requireSupplier();

        $data = Validator::getJsonBody();
        Validator::required($data, ['certification_type']);

        // Only allow creating certifications for OWN materials
        if (!$this->verifyMaterialOwnership($params['materialId'])) {
            Response::error('Factory material not found', 404);
        }

        $stmt = $this->db->prepare(
            'INSERT INTO factory_material_certifications (
                factory_material_id, certification_type, certification_other, scope,
                certificate_number, valid_from, valid_until, document_url
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $params['materialId'],
            $data['certification_type'],
            $data['certification_other'] ?? null,
            $data['scope'] ?? 'material',
            $data['certificate_number'] ?? null,
            $data['valid_from'] ?? null,
            $data['valid_until'] ?? null,
            $data['document_url'] ?? null
        ]);

        $id = (int) $this->db->lastInsertId();
        $this->show(['id' => $id]);
    }

    public function show(array $params): void {
        if (!$this->canReadCertification($params['id'])) {
            Response::error('Certification not found', 404);
        }

        $stmt = $this->db->prepare('SELECT * FROM factory_material_certifications WHERE id = ?');
        $stmt->execute([$params['id']]);
        Response::success($stmt->fetch());
    }

    public function update(array $params): void {
        // Write operations require supplier authentication
        $this->requireSupplier();

        $data = Validator::getJsonBody();

        // Only allow updating certifications for OWN materials
        if (!$this->verifyCertificationOwnership($params['id'])) {
            Response::error('Certification not found', 404);
        }

        $stmt = $this->db->prepare(
            'UPDATE factory_material_certifications SET
                certification_type = COALESCE(?, certification_type),
                certification_other = COALESCE(?, certification_other),
                scope = COALESCE(?, scope),
                certificate_number = COALESCE(?, certificate_number),
                valid_from = COALESCE(?, valid_from),
                valid_until = COALESCE(?, valid_until),
                document_url = COALESCE(?, document_url)
            WHERE id = ?'
        );
        $stmt->execute([
            $data['certification_type'] ?? null,
            $data['certification_other'] ?? null,
            $data['scope'] ?? null,
            $data['certificate_number'] ?? null,
            $data['valid_from'] ?? null,
            $data['valid_until'] ?? null,
            $data['document_url'] ?? null,
            $params['id']
        ]);

        $this->show($params);
    }

    public function delete(array $params): void {
        // Write operations require supplier authentication
        $this->requireSupplier();

        // Only allow deleting certifications for OWN materials
        if (!$this->verifyCertificationOwnership($params['id'])) {
            Response::error('Certification not found', 404);
        }

        $stmt = $this->db->prepare('DELETE FROM factory_material_certifications WHERE id = ?');
        $stmt->execute([$params['id']]);

        Response::success(['deleted' => true]);
    }
}
