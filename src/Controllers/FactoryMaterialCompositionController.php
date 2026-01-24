<?php
namespace App\Controllers;

use App\Config\Database;
use App\Helpers\Response;
use App\Helpers\Validator;

class FactoryMaterialCompositionController {
    private \PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function index(array $params): void {
        $stmt = $this->db->prepare(
            'SELECT * FROM factory_material_compositions WHERE factory_material_id = ? ORDER BY content_percentage DESC'
        );
        $stmt->execute([$params['materialId']]);
        Response::success($stmt->fetchAll());
    }

    public function create(array $params): void {
        $data = Validator::getJsonBody();
        Validator::required($data, ['content_name', 'content_percentage']);

        // Verify material exists
        $stmt = $this->db->prepare('SELECT id FROM factory_materials WHERE id = ?');
        $stmt->execute([$params['materialId']]);
        if (!$stmt->fetch()) {
            Response::error('Factory material not found', 404);
        }

        $stmt = $this->db->prepare(
            'INSERT INTO factory_material_compositions (
                factory_material_id, content_name, content_percentage, content_source,
                material_trademark, is_recycled, recycled_percentage, recycled_source
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $params['materialId'],
            $data['content_name'],
            $data['content_percentage'],
            $data['content_source'] ?? null,
            $data['material_trademark'] ?? null,
            $data['is_recycled'] ?? false,
            $data['recycled_percentage'] ?? null,
            $data['recycled_source'] ?? null
        ]);

        $id = (int) $this->db->lastInsertId();
        $this->show(['id' => $id]);
    }

    public function show(array $params): void {
        $stmt = $this->db->prepare('SELECT * FROM factory_material_compositions WHERE id = ?');
        $stmt->execute([$params['id']]);
        $composition = $stmt->fetch();

        if (!$composition) {
            Response::error('Composition not found', 404);
        }
        Response::success($composition);
    }

    public function update(array $params): void {
        $data = Validator::getJsonBody();

        $stmt = $this->db->prepare('SELECT id FROM factory_material_compositions WHERE id = ?');
        $stmt->execute([$params['id']]);
        if (!$stmt->fetch()) {
            Response::error('Composition not found', 404);
        }

        $stmt = $this->db->prepare(
            'UPDATE factory_material_compositions SET
                content_name = COALESCE(?, content_name),
                content_percentage = COALESCE(?, content_percentage),
                content_source = COALESCE(?, content_source),
                material_trademark = COALESCE(?, material_trademark),
                is_recycled = COALESCE(?, is_recycled),
                recycled_percentage = COALESCE(?, recycled_percentage),
                recycled_source = COALESCE(?, recycled_source)
            WHERE id = ?'
        );
        $stmt->execute([
            $data['content_name'] ?? null,
            $data['content_percentage'] ?? null,
            $data['content_source'] ?? null,
            $data['material_trademark'] ?? null,
            $data['is_recycled'] ?? null,
            $data['recycled_percentage'] ?? null,
            $data['recycled_source'] ?? null,
            $params['id']
        ]);

        $this->show($params);
    }

    public function delete(array $params): void {
        $stmt = $this->db->prepare('SELECT id FROM factory_material_compositions WHERE id = ?');
        $stmt->execute([$params['id']]);
        if (!$stmt->fetch()) {
            Response::error('Composition not found', 404);
        }

        $stmt = $this->db->prepare('DELETE FROM factory_material_compositions WHERE id = ?');
        $stmt->execute([$params['id']]);

        Response::success(['deleted' => true]);
    }
}
