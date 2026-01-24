<?php
namespace App\Controllers;

use App\Config\Database;
use App\Helpers\Response;
use App\Helpers\Validator;

class FactoryMaterialCertificationController {
    private \PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function index(array $params): void {
        $stmt = $this->db->prepare(
            'SELECT * FROM factory_material_certifications WHERE factory_material_id = ? ORDER BY valid_until DESC'
        );
        $stmt->execute([$params['materialId']]);
        Response::success($stmt->fetchAll());
    }

    public function create(array $params): void {
        $data = Validator::getJsonBody();
        Validator::required($data, ['certification_type']);

        // Verify material exists
        $stmt = $this->db->prepare('SELECT id FROM factory_materials WHERE id = ?');
        $stmt->execute([$params['materialId']]);
        if (!$stmt->fetch()) {
            Response::error('Factory material not found', 404);
        }

        $stmt = $this->db->prepare(
            'INSERT INTO factory_material_certifications (
                factory_material_id, certification_type, certification_other,
                certificate_number, valid_from, valid_until, document_url
            ) VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $params['materialId'],
            $data['certification_type'],
            $data['certification_other'] ?? null,
            $data['certificate_number'] ?? null,
            $data['valid_from'] ?? null,
            $data['valid_until'] ?? null,
            $data['document_url'] ?? null
        ]);

        $id = (int) $this->db->lastInsertId();
        $this->show(['id' => $id]);
    }

    public function show(array $params): void {
        $stmt = $this->db->prepare('SELECT * FROM factory_material_certifications WHERE id = ?');
        $stmt->execute([$params['id']]);
        $certification = $stmt->fetch();

        if (!$certification) {
            Response::error('Certification not found', 404);
        }
        Response::success($certification);
    }

    public function update(array $params): void {
        $data = Validator::getJsonBody();

        $stmt = $this->db->prepare('SELECT id FROM factory_material_certifications WHERE id = ?');
        $stmt->execute([$params['id']]);
        if (!$stmt->fetch()) {
            Response::error('Certification not found', 404);
        }

        $stmt = $this->db->prepare(
            'UPDATE factory_material_certifications SET
                certification_type = COALESCE(?, certification_type),
                certification_other = COALESCE(?, certification_other),
                certificate_number = COALESCE(?, certificate_number),
                valid_from = COALESCE(?, valid_from),
                valid_until = COALESCE(?, valid_until),
                document_url = COALESCE(?, document_url)
            WHERE id = ?'
        );
        $stmt->execute([
            $data['certification_type'] ?? null,
            $data['certification_other'] ?? null,
            $data['certificate_number'] ?? null,
            $data['valid_from'] ?? null,
            $data['valid_until'] ?? null,
            $data['document_url'] ?? null,
            $params['id']
        ]);

        $this->show($params);
    }

    public function delete(array $params): void {
        $stmt = $this->db->prepare('SELECT id FROM factory_material_certifications WHERE id = ?');
        $stmt->execute([$params['id']]);
        if (!$stmt->fetch()) {
            Response::error('Certification not found', 404);
        }

        $stmt = $this->db->prepare('DELETE FROM factory_material_certifications WHERE id = ?');
        $stmt->execute([$params['id']]);

        Response::success(['deleted' => true]);
    }
}
