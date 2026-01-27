<?php
/**
 * Brand-Supplier relationship routes
 * Brands can manage their supplier relationships
 * Suppliers can view their brand relationships
 */
use FastRoute\RouteCollector;

return function (RouteCollector $r) {
    // List relationships
    $r->addRoute('GET', '/api/brand-suppliers', ['App\Controllers\BrandSupplierController', 'index']);

    // List available suppliers (brand only)
    $r->addRoute('GET', '/api/brand-suppliers/available', ['App\Controllers\BrandSupplierController', 'available']);

    // Get specific relationship
    $r->addRoute('GET', '/api/brand-suppliers/{id:\d+}', ['App\Controllers\BrandSupplierController', 'show']);

    // Create new relationship (brand only)
    $r->addRoute('POST', '/api/brand-suppliers', ['App\Controllers\BrandSupplierController', 'create']);

    // Activate relationship (brand only)
    $r->addRoute('PUT', '/api/brand-suppliers/{id:\d+}/activate', ['App\Controllers\BrandSupplierController', 'activate']);

    // Deactivate relationship (brand only)
    $r->addRoute('PUT', '/api/brand-suppliers/{id:\d+}/deactivate', ['App\Controllers\BrandSupplierController', 'deactivate']);

    // Delete relationship permanently (brand only)
    $r->addRoute('DELETE', '/api/brand-suppliers/{id:\d+}', ['App\Controllers\BrandSupplierController', 'delete']);
};
