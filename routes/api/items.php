<?php
use FastRoute\RouteCollector;

return function (RouteCollector $r) {
    $r->addRoute('GET', '/api/batches/{batchId:\d+}/items', ['App\Controllers\ItemController', 'index']);
    $r->addRoute('POST', '/api/batches/{batchId:\d+}/items', ['App\Controllers\ItemController', 'create']);
    $r->addRoute('POST', '/api/batches/{batchId:\d+}/items/bulk', ['App\Controllers\ItemController', 'createBulk']);
    $r->addRoute('GET', '/api/items/{id:\d+}', ['App\Controllers\ItemController', 'show']);
    $r->addRoute('GET', '/api/items/sgtin/{sgtin}', ['App\Controllers\ItemController', 'showBySgtin']);
    $r->addRoute('DELETE', '/api/items/{id:\d+}', ['App\Controllers\ItemController', 'delete']);
};
