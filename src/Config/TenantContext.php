<?php

namespace App\Config;

use App\Helpers\Response;

/**
 * TenantContext - Handles multi-tenant authentication via API key
 *
 * Supports two tenant types: 'brand' and 'supplier'
 * Looks up API key in both brands and suppliers tables.
 */
class TenantContext
{
    private static ?int $tenantId = null;
    private static ?string $tenantType = null; // 'brand' or 'supplier'
    private static bool $authenticated = false;

    /**
     * Authenticate via API key in X-API-Key header
     * Called at the beginning of api.php before routing
     */
    public static function authenticate(): void
    {
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? null;

        if (!$apiKey) {
            Response::error('API key required. Use X-API-Key header.', 401);
            exit;
        }

        $db = Database::getInstance()->getConnection();

        // Try brands first
        $stmt = $db->prepare('
            SELECT id, brand_name as name, _is_active, _expires_at
            FROM brands
            WHERE api_key = ?
        ');
        $stmt->execute([$apiKey]);
        $tenant = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($tenant) {
            self::$tenantType = 'brand';
        } else {
            // Try suppliers
            $stmt = $db->prepare('
                SELECT id, supplier_name as name, _is_active, _expires_at
                FROM suppliers
                WHERE api_key = ?
            ');
            $stmt->execute([$apiKey]);
            $tenant = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($tenant) {
                self::$tenantType = 'supplier';
            }
        }

        if (!$tenant) {
            Response::error('Invalid API key', 401);
            exit;
        }

        if (!$tenant['_is_active']) {
            Response::error(ucfirst(self::$tenantType) . ' account is inactive', 403);
            exit;
        }

        if ($tenant['_expires_at'] && strtotime($tenant['_expires_at']) < time()) {
            Response::error('API key has expired', 403);
            exit;
        }

        // Update last_used_at
        $table = self::$tenantType === 'brand' ? 'brands' : 'suppliers';
        $updateStmt = $db->prepare("UPDATE {$table} SET _last_used_at = NOW() WHERE id = ?");
        $updateStmt->execute([$tenant['id']]);

        self::$tenantId = (int) $tenant['id'];
        self::$authenticated = true;
    }

    /**
     * Get tenant type ('brand' or 'supplier')
     */
    public static function getTenantType(): string
    {
        self::ensureAuthenticated();
        return self::$tenantType;
    }

    /**
     * Get tenant ID (brand_id or supplier_id depending on type)
     */
    public static function getTenantId(): int
    {
        self::ensureAuthenticated();
        return self::$tenantId;
    }

    /**
     * Check if authenticated as a brand
     */
    public static function isBrand(): bool
    {
        return self::$authenticated && self::$tenantType === 'brand';
    }

    /**
     * Check if authenticated as a supplier
     */
    public static function isSupplier(): bool
    {
        return self::$authenticated && self::$tenantType === 'supplier';
    }

    /**
     * Get brand ID (only valid when tenant is a brand)
     * @throws \RuntimeException if not authenticated as brand
     */
    public static function getBrandId(): int
    {
        if (!self::isBrand()) {
            throw new \RuntimeException('TenantContext: Not authenticated as brand');
        }
        return self::$tenantId;
    }

    /**
     * Get supplier ID (only valid when tenant is a supplier)
     * @throws \RuntimeException if not authenticated as supplier
     */
    public static function getSupplierId(): int
    {
        if (!self::isSupplier()) {
            throw new \RuntimeException('TenantContext: Not authenticated as supplier');
        }
        return self::$tenantId;
    }

    /**
     * Check if we are authenticated
     */
    public static function isAuthenticated(): bool
    {
        return self::$authenticated;
    }

    /**
     * DEPRECATED: For backward compatibility during migration
     * Returns brand_id if brand, throws if supplier
     * @deprecated Use getBrandId() or getTenantId() instead
     */
    public static function getCompanyId(): int
    {
        // During transition, this maps to brand_id for brands
        if (self::isBrand()) {
            return self::$tenantId;
        }
        throw new \RuntimeException('TenantContext: getCompanyId() not available for suppliers. Use getTenantId() or getSupplierId().');
    }

    /**
     * Set tenant context manually (for testing)
     */
    public static function setTenantForTesting(string $type, int $id): void
    {
        if (!in_array($type, ['brand', 'supplier'])) {
            throw new \InvalidArgumentException('Tenant type must be "brand" or "supplier"');
        }
        self::$tenantType = $type;
        self::$tenantId = $id;
        self::$authenticated = true;
    }

    /**
     * Reset context (for testing)
     */
    public static function reset(): void
    {
        self::$tenantId = null;
        self::$tenantType = null;
        self::$authenticated = false;
    }

    private static function ensureAuthenticated(): void
    {
        if (!self::$authenticated || self::$tenantId === null) {
            throw new \RuntimeException('TenantContext: Not authenticated');
        }
    }
}
