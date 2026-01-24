<?php
namespace App\Controllers;

use App\Config\Database;
use App\Helpers\Response;
use App\Helpers\Validator;

class FactoryMaterialSupplyChainController {
    private \PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function index(array $params): void {
        $stmt = $this->db->prepare(
            "SELECT * FROM factory_material_supply_chain
             WHERE factory_material_id = ?
             ORDER BY FIELD(process_stage, 'fiber', 'spinning', 'weaving_knitting', 'dyeing', 'finishing')"
        );
        $stmt->execute([$params['materialId']]);
        Response::success($stmt->fetchAll());
    }

    public function create(array $params): void {
        $data = Validator::getJsonBody();
        Validator::required($data, ['process_stage']);

        // Verify material exists
        $stmt = $this->db->prepare('SELECT id FROM factory_materials WHERE id = ?');
        $stmt->execute([$params['materialId']]);
        if (!$stmt->fetch()) {
            Response::error('Factory material not found', 404);
        }

        $stmt = $this->db->prepare(
            'INSERT INTO factory_material_supply_chain (
                factory_material_id, process_stage, supplier_name, country, facility_id
            ) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $params['materialId'],
            $data['process_stage'],
            $data['supplier_name'] ?? null,
            $data['country'] ?? null,
            $data['facility_id'] ?? null
        ]);

        $id = (int) $this->db->lastInsertId();
        $this->show(['id' => $id]);
    }

    public function show(array $params): void {
        $stmt = $this->db->prepare('SELECT * FROM factory_material_supply_chain WHERE id = ?');
        $stmt->execute([$params['id']]);
        $step = $stmt->fetch();

        if (!$step) {
            Response::error('Supply chain step not found', 404);
        }
        Response::success($step);
    }

    public function update(array $params): void {
        $data = Validator::getJsonBody();

        $stmt = $this->db->prepare('SELECT id FROM factory_material_supply_chain WHERE id = ?');
        $stmt->execute([$params['id']]);
        if (!$stmt->fetch()) {
            Response::error('Supply chain step not found', 404);
        }

        $stmt = $this->db->prepare(
            'UPDATE factory_material_supply_chain SET
                process_stage = COALESCE(?, process_stage),
                supplier_name = COALESCE(?, supplier_name),
                country = COALESCE(?, country),
                facility_id = COALESCE(?, facility_id)
            WHERE id = ?'
        );
        $stmt->execute([
            $data['process_stage'] ?? null,
            $data['supplier_name'] ?? null,
            $data['country'] ?? null,
            $data['facility_id'] ?? null,
            $params['id']
        ]);

        $this->show($params);
    }

    public function delete(array $params): void {
        $stmt = $this->db->prepare('SELECT id FROM factory_material_supply_chain WHERE id = ?');
        $stmt->execute([$params['id']]);
        if (!$stmt->fetch()) {
            Response::error('Supply chain step not found', 404);
        }

        $stmt = $this->db->prepare('DELETE FROM factory_material_supply_chain WHERE id = ?');
        $stmt->execute([$params['id']]);

        Response::success(['deleted' => true]);
    }
}
