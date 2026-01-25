<?php
namespace App\Controllers;

use App\Config\Database;
use App\Helpers\Response;
use App\Helpers\Validator;

class BatchMaterialController {
    private \PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function index(array $params): void {
        $stmt = $this->db->prepare(
            'SELECT bm.*, fm.material_name, fm.material_type, fm._internal_code
             FROM batch_materials bm
             JOIN factory_materials fm ON bm.factory_material_id = fm.id
             WHERE bm.batch_id = ?
             ORDER BY bm.component_type'
        );
        $stmt->execute([$params['batchId']]);
        Response::success($stmt->fetchAll());
    }

    public function create(array $params): void {
        $data = Validator::getJsonBody();
        Validator::required($data, ['factory_material_id', 'component_type']);

        // Verify batch exists
        $stmt = $this->db->prepare('SELECT id FROM batches WHERE id = ?');
        $stmt->execute([$params['batchId']]);
        if (!$stmt->fetch()) {
            Response::error('Batch not found', 404);
        }

        // Verify material exists
        $stmt = $this->db->prepare('SELECT id FROM factory_materials WHERE id = ?');
        $stmt->execute([$data['factory_material_id']]);
        if (!$stmt->fetch()) {
            Response::error('Factory material not found', 404);
        }

        $stmt = $this->db->prepare(
            'INSERT INTO batch_materials (
                batch_id, factory_material_id, component_type, quantity_meters
            ) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $params['batchId'],
            $data['factory_material_id'],
            $data['component_type'],
            $data['quantity_meters'] ?? null
        ]);

        $id = (int) $this->db->lastInsertId();
        $this->show(['id' => $id]);
    }

    public function show(array $params): void {
        $stmt = $this->db->prepare(
            'SELECT bm.*, fm.material_name, fm.material_type, fm._internal_code
             FROM batch_materials bm
             JOIN factory_materials fm ON bm.factory_material_id = fm.id
             WHERE bm.id = ?'
        );
        $stmt->execute([$params['id']]);
        $material = $stmt->fetch();

        if (!$material) {
            Response::error('Batch material not found', 404);
        }
        Response::success($material);
    }

    public function update(array $params): void {
        $data = Validator::getJsonBody();

        $stmt = $this->db->prepare('SELECT id FROM batch_materials WHERE id = ?');
        $stmt->execute([$params['id']]);
        if (!$stmt->fetch()) {
            Response::error('Batch material not found', 404);
        }

        $stmt = $this->db->prepare(
            'UPDATE batch_materials SET
                component_type = COALESCE(?, component_type),
                quantity_meters = COALESCE(?, quantity_meters)
            WHERE id = ?'
        );
        $stmt->execute([
            $data['component_type'] ?? null,
            $data['quantity_meters'] ?? null,
            $params['id']
        ]);

        $this->show($params);
    }

    public function delete(array $params): void {
        $stmt = $this->db->prepare('SELECT id FROM batch_materials WHERE id = ?');
        $stmt->execute([$params['id']]);
        if (!$stmt->fetch()) {
            Response::error('Batch material not found', 404);
        }

        $stmt = $this->db->prepare('DELETE FROM batch_materials WHERE id = ?');
        $stmt->execute([$params['id']]);

        Response::success(['deleted' => true]);
    }

    // Get materials by factory material ID (reverse lookup)
    public function indexByMaterial(array $params): void {
        $stmt = $this->db->prepare(
            'SELECT bm.*, b.batch_number, pv.sku
             FROM batch_materials bm
             JOIN batches b ON bm.batch_id = b.id
             JOIN product_variants pv ON b.product_variant_id = pv.id
             WHERE bm.factory_material_id = ?
             ORDER BY b.created_at DESC'
        );
        $stmt->execute([$params['materialId']]);
        Response::success($stmt->fetchAll());
    }
}
