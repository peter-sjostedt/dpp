<?php
namespace App\Controllers;

use App\Config\Database;
use App\Helpers\Response;
use App\Helpers\Validator;

class FactoryMaterialController {
    private \PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function index(array $params): void {
        $stmt = $this->db->prepare(
            'SELECT fm.*, s.supplier_name
             FROM factory_materials fm
             LEFT JOIN suppliers s ON fm.supplier_id = s.id
             WHERE fm.supplier_id = ?
             ORDER BY fm.created_at DESC'
        );
        $stmt->execute([$params['supplierId']]);
        Response::success($stmt->fetchAll());
    }

    public function indexAll(array $params): void {
        $stmt = $this->db->query(
            'SELECT fm.*, s.supplier_name
             FROM factory_materials fm
             LEFT JOIN suppliers s ON fm.supplier_id = s.id
             ORDER BY fm.created_at DESC'
        );
        Response::success($stmt->fetchAll());
    }

    public function show(array $params): void {
        $stmt = $this->db->prepare(
            'SELECT fm.*, s.supplier_name
             FROM factory_materials fm
             LEFT JOIN suppliers s ON fm.supplier_id = s.id
             WHERE fm.id = ?'
        );
        $stmt->execute([$params['id']]);
        $material = $stmt->fetch();

        if (!$material) {
            Response::error('Material not found', 404);
        }

        Response::success($material);
    }

    public function create(array $params): void {
        $data = Validator::getJsonBody();

        if ($error = Validator::required($data, ['material_name'])) {
            Response::error($error);
        }

        // Verify supplier exists
        $stmt = $this->db->prepare('SELECT id FROM suppliers WHERE id = ?');
        $stmt->execute([$params['supplierId']]);
        if (!$stmt->fetch()) {
            Response::error('Supplier not found', 404);
        }

        $stmt = $this->db->prepare(
            'INSERT INTO factory_materials (
                supplier_id, material_name, material_type, internal_code,
                material_color, net_weight_per_meter, width_cm
             ) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $params['supplierId'],
            $data['material_name'],
            $data['material_type'] ?? null,
            $data['internal_code'] ?? null,
            $data['material_color'] ?? null,
            $data['net_weight_per_meter'] ?? null,
            $data['width_cm'] ?? null
        ]);

        $id = $this->db->lastInsertId();
        $this->show(['id' => $id]);
    }

    public function update(array $params): void {
        $data = Validator::getJsonBody();

        $stmt = $this->db->prepare('SELECT id FROM factory_materials WHERE id = ?');
        $stmt->execute([$params['id']]);
        if (!$stmt->fetch()) {
            Response::error('Material not found', 404);
        }

        $stmt = $this->db->prepare(
            'UPDATE factory_materials SET
                material_name = COALESCE(?, material_name),
                material_type = COALESCE(?, material_type),
                internal_code = COALESCE(?, internal_code),
                material_color = COALESCE(?, material_color),
                net_weight_per_meter = COALESCE(?, net_weight_per_meter),
                width_cm = COALESCE(?, width_cm)
             WHERE id = ?'
        );
        $stmt->execute([
            $data['material_name'] ?? null,
            $data['material_type'] ?? null,
            $data['internal_code'] ?? null,
            $data['material_color'] ?? null,
            $data['net_weight_per_meter'] ?? null,
            $data['width_cm'] ?? null,
            $params['id']
        ]);

        $this->show($params);
    }

    public function delete(array $params): void {
        $stmt = $this->db->prepare('SELECT id FROM factory_materials WHERE id = ?');
        $stmt->execute([$params['id']]);
        if (!$stmt->fetch()) {
            Response::error('Material not found', 404);
        }

        $stmt = $this->db->prepare('DELETE FROM factory_materials WHERE id = ?');
        $stmt->execute([$params['id']]);

        Response::success(['deleted' => (int)$params['id']]);
    }
}
