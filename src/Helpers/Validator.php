<?php
namespace App\Helpers;

class Validator {
    public static function getJsonBody(): array {
        $json = file_get_contents('php://input');
        return json_decode($json, true) ?? [];
    }

    public static function required(array $data, array $fields): ?string {
        foreach ($fields as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                return "Field '$field' is required";
            }
        }
        return null;
    }

    public static function maxLength(array $data, string $field, int $max): ?string {
        if (isset($data[$field]) && strlen($data[$field]) > $max) {
            return "Field '$field' max length is $max";
        }
        return null;
    }

    public static function numeric(array $data, string $field): ?string {
        if (isset($data[$field]) && !is_numeric($data[$field])) {
            return "Field '$field' must be numeric";
        }
        return null;
    }

    public static function percentage(array $data, string $field): ?string {
        if (isset($data[$field])) {
            $val = $data[$field];
            if (!is_numeric($val) || $val < 0 || $val > 100) {
                return "Field '$field' must be between 0 and 100";
            }
        }
        return null;
    }
}
