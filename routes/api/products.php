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

    // Product Components
    $r->addRoute('GET', '/api/products/{productId:\d+}/components', ['App\Controllers\ProductComponentController', 'index']);
    $r->addRoute('POST', '/api/products/{productId:\d+}/components', ['App\Controllers\ProductComponentController', 'create']);
    $r->addRoute('GET', '/api/components/{id:\d+}', ['App\Controllers\ProductComponentController', 'show']);
    $r->addRoute('PUT', '/api/components/{id:\d+}', ['App\Controllers\ProductComponentController', 'update']);
    $r->addRoute('DELETE', '/api/components/{id:\d+}', ['App\Controllers\ProductComponentController', 'delete']);

    // Component Materials
    $r->addRoute('GET', '/api/components/{componentId:\d+}/materials', ['App\Controllers\ProductComponentController', 'listMaterials']);
    $r->addRoute('POST', '/api/components/{componentId:\d+}/materials', ['App\Controllers\ProductComponentController', 'addMaterial']);
    $r->addRoute('DELETE', '/api/component-materials/{id:\d+}', ['App\Controllers\ProductComponentController', 'removeMaterial']);

    // Care Information
    $r->addRoute('GET', '/api/products/{productId:\d+}/care', ['App\Controllers\CareInformationController', 'show']);
    $r->addRoute('PUT', '/api/products/{productId:\d+}/care', ['App\Controllers\CareInformationController', 'createOrUpdate']);
    $r->addRoute('DELETE', '/api/products/{productId:\d+}/care', ['App\Controllers\CareInformationController', 'delete']);

    // Certifications
    $r->addRoute('GET', '/api/products/{productId:\d+}/certifications', ['App\Controllers\CertificationController', 'index']);
    $r->addRoute('POST', '/api/products/{productId:\d+}/certifications', ['App\Controllers\CertificationController', 'create']);
    $r->addRoute('GET', '/api/certifications/{id:\d+}', ['App\Controllers\CertificationController', 'show']);
    $r->addRoute('PUT', '/api/certifications/{id:\d+}', ['App\Controllers\CertificationController', 'update']);
    $r->addRoute('DELETE', '/api/certifications/{id:\d+}', ['App\Controllers\CertificationController', 'delete']);

    // Compliance
    $r->addRoute('GET', '/api/products/{productId:\d+}/compliance', ['App\Controllers\ComplianceController', 'show']);
    $r->addRoute('PUT', '/api/products/{productId:\d+}/compliance', ['App\Controllers\ComplianceController', 'createOrUpdate']);
    $r->addRoute('DELETE', '/api/products/{productId:\d+}/compliance', ['App\Controllers\ComplianceController', 'delete']);

    // Chemical Compliance
    $r->addRoute('GET', '/api/products/{productId:\d+}/chemicals', ['App\Controllers\ComplianceController', 'listChemicals']);
    $r->addRoute('POST', '/api/products/{productId:\d+}/chemicals', ['App\Controllers\ComplianceController', 'addChemical']);
    $r->addRoute('DELETE', '/api/chemicals/{id:\d+}', ['App\Controllers\ComplianceController', 'removeChemical']);

    // Circularity
    $r->addRoute('GET', '/api/products/{productId:\d+}/circularity', ['App\Controllers\CircularityController', 'show']);
    $r->addRoute('PUT', '/api/products/{productId:\d+}/circularity', ['App\Controllers\CircularityController', 'createOrUpdate']);
    $r->addRoute('DELETE', '/api/products/{productId:\d+}/circularity', ['App\Controllers\CircularityController', 'delete']);

    // Sustainability
    $r->addRoute('GET', '/api/products/{productId:\d+}/sustainability', ['App\Controllers\SustainabilityController', 'show']);
    $r->addRoute('PUT', '/api/products/{productId:\d+}/sustainability', ['App\Controllers\SustainabilityController', 'createOrUpdate']);
    $r->addRoute('DELETE', '/api/products/{productId:\d+}/sustainability', ['App\Controllers\SustainabilityController', 'delete']);
};
