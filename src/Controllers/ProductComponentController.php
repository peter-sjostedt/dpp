<?php
namespace App\Controllers;

use App\Config\Database;
use App\Helpers\Response;
use App\Helpers\Validator;

class ProductComponentController {
    private \PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // ========== Product Components ==========

    public function index(array $params): void {
        $stmt = $this->db->prepare(
            'SELECT * FROM product_components WHERE product_id = ? ORDER BY component_type'
        );
        $stmt->execute([$params['productId']]);
        Response::success($stmt->fetchAll());
    }

    public function show(array $params): void {
        $stmt = $this->db->prepare('SELECT * FROM product_components WHERE id = ?');
        $stmt->execute([$params['id']]);
        $component = $stmt->fetch();

        if (!$component) {
            Response::error('Component not found', 404);
        }

        // Include materials
        $component['materials'] = $this->getComponentMaterials($params['id']);

        Response::success($component);
    }

    public function create(array $params): void {
        $data = Validator::getJsonBody();

        if ($error = Validator::required($data, ['component_type'])) {
            Response::error($error);
        }

        // Validate component_type enum
        $validTypes = ['body_fabric', 'lining', 'trim', 'padding', 'other'];
        if (!in_array($data['component_type'], $validTypes)) {
            Response::error('Invalid component_type. Must be one of: ' . implode(', ', $validTypes));
        }

        // Verify product exists
        $stmt = $this->db->prepare('SELECT id FROM products WHERE id = ?');
        $stmt->execute([$params['productId']]);
        if (!$stmt->fetch()) {
            Response::error('Product not found', 404);
        }

        $stmt = $this->db->prepare(
            'INSERT INTO product_components (product_id, component_type, component_weight)
             VALUES (?, ?, ?)'
        );
        $stmt->execute([
            $params['productId'],
            $data['component_type'],
            $data['component_weight'] ?? null
        ]);

        $id = $this->db->lastInsertId();
        $this->show(['id' => $id]);
    }

    public function update(array $params): void {
        $data = Validator::getJsonBody();

        $stmt = $this->db->prepare('SELECT id FROM product_components WHERE id = ?');
        $stmt->execute([$params['id']]);
        if (!$stmt->fetch()) {
            Response::error('Component not found', 404);
        }

        // Validate component_type if provided
        if (isset($data['component_type'])) {
            $validTypes = ['body_fabric', 'lining', 'trim', 'padding', 'other'];
            if (!in_array($data['component_type'], $validTypes)) {
                Response::error('Invalid component_type. Must be one of: ' . implode(', ', $validTypes));
            }
        }

        $stmt = $this->db->prepare(
            'UPDATE product_components SET
                component_type = COALESCE(?, component_type),
                component_weight = COALESCE(?, component_weight)
             WHERE id = ?'
        );
        $stmt->execute([
            $data['component_type'] ?? null,
            $data['component_weight'] ?? null,
            $params['id']
        ]);

        $this->show($params);
    }

    public function delete(array $params): void {
        $stmt = $this->db->prepare('SELECT id FROM product_components WHERE id = ?');
        $stmt->execute([$params['id']]);
        if (!$stmt->fetch()) {
            Response::error('Component not found', 404);
        }

        $stmt = $this->db->prepare('DELETE FROM product_components WHERE id = ?');
        $stmt->execute([$params['id']]);

        Response::success(['deleted' => (int)$params['id']]);
    }

    // ========== Component Materials ==========

    private function getComponentMaterials(int|string $componentId): array {
        $stmt = $this->db->prepare(
            'SELECT cm.*, fm.material_name, fm.material_type
             FROM component_materials cm
             LEFT JOIN factory_materials fm ON cm.factory_material_id = fm.id
             WHERE cm.component_id = ?
             ORDER BY cm.percentage DESC'
        );
        $stmt->execute([$componentId]);
        return $stmt->fetchAll();
    }

    public function listMaterials(array $params): void {
        Response::success($this->getComponentMaterials($params['componentId']));
    }

    public function addMaterial(array $params): void {
        $data = Validator::getJsonBody();

        if ($error = Validator::required($data, ['factory_material_id', 'percentage'])) {
            Response::error($error);
        }

        // Verify component exists
        $stmt = $this->db->prepare('SELECT id FROM product_components WHERE id = ?');
        $stmt->execute([$params['componentId']]);
        if (!$stmt->fetch()) {
            Response::error('Component not found', 404);
        }

        // Verify material exists
        $stmt = $this->db->prepare('SELECT id FROM factory_materials WHERE id = ?');
        $stmt->execute([$data['factory_material_id']]);
        if (!$stmt->fetch()) {
            Response::error('Material not found', 404);
        }

        $stmt = $this->db->prepare(
            'INSERT INTO component_materials (component_id, factory_material_id, percentage)
             VALUES (?, ?, ?)'
        );
        $stmt->execute([
            $params['componentId'],
            $data['factory_material_id'],
            $data['percentage']
        ]);

        Response::success(['id' => (int)$this->db->lastInsertId()], 201);
    }

    public function removeMaterial(array $params): void {
        $stmt = $this->db->prepare('SELECT id FROM component_materials WHERE id = ?');
        $stmt->execute([$params['id']]);
        if (!$stmt->fetch()) {
            Response::error('Component material not found', 404);
        }

        $stmt = $this->db->prepare('DELETE FROM component_materials WHERE id = ?');
        $stmt->execute([$params['id']]);

        Response::success(['deleted' => (int)$params['id']]);
    }
}
