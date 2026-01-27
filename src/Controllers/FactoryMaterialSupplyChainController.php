<?php
namespace App\Controllers;

use App\Config\TenantContext;
use App\Helpers\Response;
use App\Helpers\Validator;

class FactoryMaterialSupplyChainController extends TenantAwareController {

    /**
     * Check if supply chain step belongs to a material owned by current supplier (for writes)
     */
    private function verifySupplyChainStepOwnership(int|string $stepId): bool {
        if (!TenantContext::isSupplier()) {
            return false;
        }

        $supplierId = TenantContext::getSupplierId();
        $stmt = $this->db->prepare(
            'SELECT fmsc.id FROM factory_material_supply_chain fmsc
             JOIN factory_materials fm ON fmsc.factory_material_id = fm.id
             WHERE fmsc.id = ? AND fm.supplier_id = ?'
        );
        $stmt->execute([$stepId, $supplierId]);
        return (bool) $stmt->fetch();
    }

    /**
     * Check if current user can read a supply chain step
     */
    private function canReadSupplyChainStep(int|string $stepId): bool {
        $stmt = $this->db->prepare(
            'SELECT factory_material_id FROM factory_material_supply_chain WHERE id = ?'
        );
        $stmt->execute([$stepId]);
        $step = $stmt->fetch();

        if (!$step) {
            return false;
        }

        return $this->canAccessMaterial($step['factory_material_id']);
    }

    public function index(array $params): void {
        // Both brands and suppliers can read via canAccessMaterial
        if (!$this->canAccessMaterial($params['materialId'])) {
            Response::error('Factory material not found', 404);
        }

        $stmt = $this->db->prepare(
            'SELECT * FROM factory_material_supply_chain
             WHERE factory_material_id = ?
             ORDER BY sequence'
        );
        $stmt->execute([$params['materialId']]);
        Response::success($stmt->fetchAll());
    }

    public function create(array $params): void {
        // Write operations require supplier authentication
        $this->requireSupplier();

        $data = Validator::getJsonBody();
        Validator::required($data, ['process_step']);

        // Only allow creating supply chain steps for OWN materials
        if (!$this->verifyMaterialOwnership($params['materialId'])) {
            Response::error('Factory material not found', 404);
        }

        // Get next sequence number
        $stmt = $this->db->prepare(
            'SELECT COALESCE(MAX(sequence), 0) + 1 FROM factory_material_supply_chain WHERE factory_material_id = ?'
        );
        $stmt->execute([$params['materialId']]);
        $nextSeq = (int) $stmt->fetchColumn();

        $stmt = $this->db->prepare(
            'INSERT INTO factory_material_supply_chain (
                factory_material_id, sequence, process_step, country, facility_name, facility_identifier
            ) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $params['materialId'],
            $data['sequence'] ?? $nextSeq,
            $data['process_step'],
            $data['country'] ?? null,
            $data['facility_name'] ?? null,
            $data['facility_identifier'] ?? null
        ]);

        $id = (int) $this->db->lastInsertId();
        $this->show(['id' => $id]);
    }

    public function show(array $params): void {
        if (!$this->canReadSupplyChainStep($params['id'])) {
            Response::error('Supply chain step not found', 404);
        }

        $stmt = $this->db->prepare('SELECT * FROM factory_material_supply_chain WHERE id = ?');
        $stmt->execute([$params['id']]);
        Response::success($stmt->fetch());
    }

    public function update(array $params): void {
        // Write operations require supplier authentication
        $this->requireSupplier();

        $data = Validator::getJsonBody();

        // Only allow updating supply chain steps for OWN materials
        if (!$this->verifySupplyChainStepOwnership($params['id'])) {
            Response::error('Supply chain step not found', 404);
        }

        $stmt = $this->db->prepare(
            'UPDATE factory_material_supply_chain SET
                sequence = COALESCE(?, sequence),
                process_step = COALESCE(?, process_step),
                country = COALESCE(?, country),
                facility_name = COALESCE(?, facility_name),
                facility_identifier = COALESCE(?, facility_identifier)
            WHERE id = ?'
        );
        $stmt->execute([
            $data['sequence'] ?? null,
            $data['process_step'] ?? null,
            $data['country'] ?? null,
            $data['facility_name'] ?? null,
            $data['facility_identifier'] ?? null,
            $params['id']
        ]);

        $this->show($params);
    }

    public function delete(array $params): void {
        // Write operations require supplier authentication
        $this->requireSupplier();

        // Only allow deleting supply chain steps for OWN materials
        if (!$this->verifySupplyChainStepOwnership($params['id'])) {
            Response::error('Supply chain step not found', 404);
        }

        $stmt = $this->db->prepare('DELETE FROM factory_material_supply_chain WHERE id = ?');
        $stmt->execute([$params['id']]);

        Response::success(['deleted' => true]);
    }
}
