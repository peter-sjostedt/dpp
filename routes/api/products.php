<?php
use FastRoute\RouteCollector;

return function (RouteCollector $r) {
    // Products
    $r->addRoute('GET', '/api/products', ['App\Controllers\ProductController', 'indexAll']);
    $r->addRoute('GET', '/api/brands/{brandId:\d+}/products', ['App\Controllers\ProductController', 'index']);
    $r->addRoute('POST', '/api/brands/{brandId:\d+}/products', ['App\Controllers\ProductController', 'create']);
    $r->addRoute('GET', '/api/products/{id:\d+}', ['App\Controllers\ProductController', 'show']);
    $r->addRoute('PUT', '/api/products/{id:\d+}', ['App\Controllers\ProductController', 'update']);
    $r->addRoute('DELETE', '/api/products/{id:\d+}', ['App\Controllers\ProductController', 'delete']);
    $r->addRoute('GET', '/api/products/{id:\d+}/dpp', ['App\Controllers\ProductController', 'getDpp']);

    // Product Components (material composition stored inline)
    $r->addRoute('GET', '/api/products/{productId:\d+}/components', ['App\Controllers\ProductComponentController', 'index']);
    $r->addRoute('POST', '/api/products/{productId:\d+}/components', ['App\Controllers\ProductComponentController', 'create']);
    $r->addRoute('GET', '/api/components/{id:\d+}', ['App\Controllers\ProductComponentController', 'show']);
    $r->addRoute('PUT', '/api/components/{id:\d+}', ['App\Controllers\ProductComponentController', 'update']);
    $r->addRoute('DELETE', '/api/components/{id:\d+}', ['App\Controllers\ProductComponentController', 'delete']);

    // Care Information
    $r->addRoute('GET', '/api/products/{productId:\d+}/care', ['App\Controllers\CareInformationController', 'show']);
    $r->addRoute('PUT', '/api/products/{productId:\d+}/care', ['App\Controllers\CareInformationController', 'createOrUpdate']);
    $r->addRoute('DELETE', '/api/products/{productId:\d+}/care', ['App\Controllers\CareInformationController', 'delete']);

    // Compliance (includes certifications and chemical compliance inline)
    $r->addRoute('GET', '/api/products/{productId:\d+}/compliance', ['App\Controllers\ComplianceController', 'show']);
    $r->addRoute('PUT', '/api/products/{productId:\d+}/compliance', ['App\Controllers\ComplianceController', 'createOrUpdate']);
    $r->addRoute('DELETE', '/api/products/{productId:\d+}/compliance', ['App\Controllers\ComplianceController', 'delete']);

    // Circularity
    $r->addRoute('GET', '/api/products/{productId:\d+}/circularity', ['App\Controllers\CircularityController', 'show']);
    $r->addRoute('PUT', '/api/products/{productId:\d+}/circularity', ['App\Controllers\CircularityController', 'createOrUpdate']);
    $r->addRoute('DELETE', '/api/products/{productId:\d+}/circularity', ['App\Controllers\CircularityController', 'delete']);

    // Sustainability
    $r->addRoute('GET', '/api/products/{productId:\d+}/sustainability', ['App\Controllers\SustainabilityController', 'show']);
    $r->addRoute('PUT', '/api/products/{productId:\d+}/sustainability', ['App\Controllers\SustainabilityController', 'createOrUpdate']);
    $r->addRoute('DELETE', '/api/products/{productId:\d+}/sustainability', ['App\Controllers\SustainabilityController', 'delete']);

    // DPP Export
    $r->addRoute('GET', '/api/products/{id:\d+}/dpp/preview', ['App\Controllers\DppExportController', 'preview']);
    $r->addRoute('GET', '/api/products/{id:\d+}/dpp/validate', ['App\Controllers\DppExportController', 'validate']);
    $r->addRoute('GET', '/api/products/{id:\d+}/dpp/export', ['App\Controllers\DppExportController', 'export']);
};
