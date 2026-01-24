<?php
use FastRoute\RouteCollector;

return function (RouteCollector $r) {
    // Batches
    $r->addRoute('GET', '/api/variants/{variantId:\d+}/batches', ['App\Controllers\BatchController', 'index']);
    $r->addRoute('POST', '/api/variants/{variantId:\d+}/batches', ['App\Controllers\BatchController', 'create']);
    $r->addRoute('GET', '/api/batches/{id:\d+}', ['App\Controllers\BatchController', 'show']);
    $r->addRoute('PUT', '/api/batches/{id:\d+}', ['App\Controllers\BatchController', 'update']);
    $r->addRoute('DELETE', '/api/batches/{id:\d+}', ['App\Controllers\BatchController', 'delete']);

    // Batch Suppliers
    $r->addRoute('GET', '/api/batches/{batchId:\d+}/suppliers', ['App\Controllers\BatchController', 'listSuppliers']);
    $r->addRoute('POST', '/api/batches/{batchId:\d+}/suppliers', ['App\Controllers\BatchController', 'addSupplier']);
    $r->addRoute('DELETE', '/api/batch-suppliers/{id:\d+}', ['App\Controllers\BatchController', 'removeSupplier']);

    // Batch Materials
    $r->addRoute('GET', '/api/batches/{batchId:\d+}/materials', ['App\Controllers\BatchMaterialController', 'index']);
    $r->addRoute('POST', '/api/batches/{batchId:\d+}/materials', ['App\Controllers\BatchMaterialController', 'create']);
    $r->addRoute('GET', '/api/batch-materials/{id:\d+}', ['App\Controllers\BatchMaterialController', 'show']);
    $r->addRoute('PUT', '/api/batch-materials/{id:\d+}', ['App\Controllers\BatchMaterialController', 'update']);
    $r->addRoute('DELETE', '/api/batch-materials/{id:\d+}', ['App\Controllers\BatchMaterialController', 'delete']);
};
