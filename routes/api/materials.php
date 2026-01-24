<?php
use FastRoute\RouteCollector;

return function (RouteCollector $r) {
    // Factory Materials
    $r->addRoute('GET', '/api/materials', ['App\Controllers\FactoryMaterialController', 'indexAll']);
    $r->addRoute('GET', '/api/suppliers/{supplierId:\d+}/materials', ['App\Controllers\FactoryMaterialController', 'index']);
    $r->addRoute('POST', '/api/suppliers/{supplierId:\d+}/materials', ['App\Controllers\FactoryMaterialController', 'create']);
    $r->addRoute('GET', '/api/materials/{id:\d+}', ['App\Controllers\FactoryMaterialController', 'show']);
    $r->addRoute('PUT', '/api/materials/{id:\d+}', ['App\Controllers\FactoryMaterialController', 'update']);
    $r->addRoute('DELETE', '/api/materials/{id:\d+}', ['App\Controllers\FactoryMaterialController', 'delete']);
    $r->addRoute('GET', '/api/materials/{materialId:\d+}/batches', ['App\Controllers\BatchMaterialController', 'indexByMaterial']);

    // Material Compositions
    $r->addRoute('GET', '/api/materials/{materialId:\d+}/compositions', ['App\Controllers\FactoryMaterialCompositionController', 'index']);
    $r->addRoute('POST', '/api/materials/{materialId:\d+}/compositions', ['App\Controllers\FactoryMaterialCompositionController', 'create']);
    $r->addRoute('GET', '/api/compositions/{id:\d+}', ['App\Controllers\FactoryMaterialCompositionController', 'show']);
    $r->addRoute('PUT', '/api/compositions/{id:\d+}', ['App\Controllers\FactoryMaterialCompositionController', 'update']);
    $r->addRoute('DELETE', '/api/compositions/{id:\d+}', ['App\Controllers\FactoryMaterialCompositionController', 'delete']);

    // Material Certifications
    $r->addRoute('GET', '/api/materials/{materialId:\d+}/certifications', ['App\Controllers\FactoryMaterialCertificationController', 'index']);
    $r->addRoute('POST', '/api/materials/{materialId:\d+}/certifications', ['App\Controllers\FactoryMaterialCertificationController', 'create']);
    $r->addRoute('GET', '/api/material-certifications/{id:\d+}', ['App\Controllers\FactoryMaterialCertificationController', 'show']);
    $r->addRoute('PUT', '/api/material-certifications/{id:\d+}', ['App\Controllers\FactoryMaterialCertificationController', 'update']);
    $r->addRoute('DELETE', '/api/material-certifications/{id:\d+}', ['App\Controllers\FactoryMaterialCertificationController', 'delete']);

    // Material Supply Chain
    $r->addRoute('GET', '/api/materials/{materialId:\d+}/supply-chain', ['App\Controllers\FactoryMaterialSupplyChainController', 'index']);
    $r->addRoute('POST', '/api/materials/{materialId:\d+}/supply-chain', ['App\Controllers\FactoryMaterialSupplyChainController', 'create']);
    $r->addRoute('GET', '/api/supply-chain/{id:\d+}', ['App\Controllers\FactoryMaterialSupplyChainController', 'show']);
    $r->addRoute('PUT', '/api/supply-chain/{id:\d+}', ['App\Controllers\FactoryMaterialSupplyChainController', 'update']);
    $r->addRoute('DELETE', '/api/supply-chain/{id:\d+}', ['App\Controllers\FactoryMaterialSupplyChainController', 'delete']);
};
