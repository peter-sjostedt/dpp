<?php
namespace App\Controllers;

use App\Config\Database;
use App\Helpers\Response;
use App\Helpers\Validator;

class CertificationController {
    private \PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function index(array $params): void {
        $stmt = $this->db->prepare(
            'SELECT * FROM certifications WHERE product_id = ? ORDER BY certification_name'
        );
        $stmt->execute([$params['productId']]);
        Response::success($stmt->fetchAll());
    }

    public function show(array $params): void {
        $stmt = $this->db->prepare('SELECT * FROM certifications WHERE id = ?');
        $stmt->execute([$params['id']]);
        $cert = $stmt->fetch();

        if (!$cert) {
            Response::error('Certification not found', 404);
        }
        Response::success($cert);
    }

    public function create(array $params): void {
        $data = Validator::getJsonBody();

        if ($error = Validator::required($data, ['certification_name'])) {
            Response::error($error);
        }

        // Verify product exists
        $stmt = $this->db->prepare('SELECT id FROM products WHERE id = ?');
        $stmt->execute([$params['productId']]);
        if (!$stmt->fetch()) {
            Response::error('Product not found', 404);
        }

        $stmt = $this->db->prepare(
            'INSERT INTO certifications (
                product_id, certification_name, certification_other,
                validation_document_url, valid_until
             ) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $params['productId'],
            $data['certification_name'],
            $data['certification_other'] ?? null,
            $data['validation_document_url'] ?? null,
            $data['valid_until'] ?? null
        ]);

        $id = $this->db->lastInsertId();
        $this->show(['id' => $id]);
    }

    public function update(array $params): void {
        $data = Validator::getJsonBody();

        $stmt = $this->db->prepare('SELECT id FROM certifications WHERE id = ?');
        $stmt->execute([$params['id']]);
        if (!$stmt->fetch()) {
            Response::error('Certification not found', 404);
        }

        $stmt = $this->db->prepare(
            'UPDATE certifications SET
                certification_name = COALESCE(?, certification_name),
                certification_other = COALESCE(?, certification_other),
                validation_document_url = COALESCE(?, validation_document_url),
                valid_until = COALESCE(?, valid_until)
             WHERE id = ?'
        );
        $stmt->execute([
            $data['certification_name'] ?? null,
            $data['certification_other'] ?? null,
            $data['validation_document_url'] ?? null,
            $data['valid_until'] ?? null,
            $params['id']
        ]);

        $this->show($params);
    }

    public function delete(array $params): void {
        $stmt = $this->db->prepare('SELECT id FROM certifications WHERE id = ?');
        $stmt->execute([$params['id']]);
        if (!$stmt->fetch()) {
            Response::error('Certification not found', 404);
        }

        $stmt = $this->db->prepare('DELETE FROM certifications WHERE id = ?');
        $stmt->execute([$params['id']]);

        Response::success(['deleted' => (int)$params['id']]);
    }
}
