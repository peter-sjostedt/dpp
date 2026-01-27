<?php
use FastRoute\RouteCollector;

return function (RouteCollector $r) {
    // Batches (nested under products)
    $r->addRoute('GET', '/api/products/{productId:\d+}/batches', ['App\Controllers\BatchController', 'index']);
    $r->addRoute('POST', '/api/products/{productId:\d+}/batches', ['App\Controllers\BatchController', 'create']);

    // All batches (no product filter)
    $r->addRoute('GET', '/api/batches', ['App\Controllers\BatchController', 'indexAll']);

    // Single batch operations
    $r->addRoute('GET', '/api/batches/{id:\d+}', ['App\Controllers\BatchController', 'show']);
    $r->addRoute('PUT', '/api/batches/{id:\d+}', ['App\Controllers\BatchController', 'update']);
    $r->addRoute('DELETE', '/api/batches/{id:\d+}', ['App\Controllers\BatchController', 'delete']);

    // Batch Materials
    $r->addRoute('GET', '/api/batches/{batchId:\d+}/materials', ['App\Controllers\BatchMaterialController', 'index']);
    $r->addRoute('POST', '/api/batches/{batchId:\d+}/materials', ['App\Controllers\BatchMaterialController', 'create']);
    $r->addRoute('GET', '/api/batch-materials/{id:\d+}', ['App\Controllers\BatchMaterialController', 'show']);
    $r->addRoute('PUT', '/api/batch-materials/{id:\d+}', ['App\Controllers\BatchMaterialController', 'update']);
    $r->addRoute('DELETE', '/api/batch-materials/{id:\d+}', ['App\Controllers\BatchMaterialController', 'delete']);
};
