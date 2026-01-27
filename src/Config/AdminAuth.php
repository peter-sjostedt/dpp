<?php

namespace App\Config;

use App\Helpers\Response;

/**
 * AdminAuth - Handles admin authentication via master key
 *
 * Admin API uses X-Admin-Key header for authentication.
 * This is separate from tenant authentication (X-API-Key).
 */
class AdminAuth
{
    private static bool $authenticated = false;

    // Master key for admin operations (should be in environment variable in production)
    private const MASTER_KEY = 'dpp_admin_master_key_2024_secure';

    /**
     * Authenticate via admin key in X-Admin-Key header
     * Called at the beginning of api.php for admin routes
     */
    public static function authenticate(): void
    {
        $adminKey = $_SERVER['HTTP_X_ADMIN_KEY'] ?? null;

        if (!$adminKey) {
            Response::error('Admin key required. Use X-Admin-Key header.', 401);
            exit;
        }

        if ($adminKey !== self::MASTER_KEY) {
            Response::error('Invalid admin key', 401);
            exit;
        }

        self::$authenticated = true;
    }

    /**
     * Check if admin is authenticated
     */
    public static function isAuthenticated(): bool
    {
        return self::$authenticated;
    }

    /**
     * Require admin authentication (throws if not authenticated)
     */
    public static function requireAdmin(): void
    {
        if (!self::$authenticated) {
            Response::error('Admin authentication required', 403);
            exit;
        }
    }

    /**
     * Generate a new API key for brands/suppliers
     */
    public static function generateApiKey(string $prefix = ''): string
    {
        $key = bin2hex(random_bytes(32));
        return $prefix ? $prefix . '_' . $key : $key;
    }

    /**
     * Reset authentication (for testing)
     */
    public static function reset(): void
    {
        self::$authenticated = false;
    }
}
