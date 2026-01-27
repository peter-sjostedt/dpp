<?php
/**
 * Tenant list routes - Public endpoints for test panel
 * These endpoints do NOT require authentication
 */
use FastRoute\RouteCollector;

return function (RouteCollector $r) {
    // List all active brands with API keys (for test panel selector)
    $r->addRoute('GET', '/api/tenants/brands', function() {
        $db = \App\Config\Database::getInstance()->getConnection();
        $stmt = $db->query(
            'SELECT id, brand_name, api_key
             FROM brands
             WHERE _is_active = TRUE
             ORDER BY brand_name'
        );
        \App\Helpers\Response::success($stmt->fetchAll());
    });

    // List all active suppliers with API keys (for test panel selector)
    $r->addRoute('GET', '/api/tenants/suppliers', function() {
        $db = \App\Config\Database::getInstance()->getConnection();
        $stmt = $db->query(
            'SELECT id, supplier_name, api_key
             FROM suppliers
             WHERE _is_active = TRUE
             ORDER BY supplier_name'
        );
        \App\Helpers\Response::success($stmt->fetchAll());
    });
};
