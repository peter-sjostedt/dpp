<?php
namespace App\Config;

// Lösenord för test-panelen (ändra detta!)
define('TEST_PANEL_PASSWORD', 'dpp2025');

class Auth {
    public static function requireLogin(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            header('Location: login.php');
            exit;
        }
    }

    public static function login(string $password): bool {
        if ($password === TEST_PANEL_PASSWORD) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['logged_in'] = true;
            $_SESSION['login_time'] = time();
            return true;
        }
        return false;
    }

    public static function logout(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
    }

    public static function isLoggedIn(): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
}
