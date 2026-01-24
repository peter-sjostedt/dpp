<?php
use FastRoute\RouteCollector;

return function (RouteCollector $r) {
    $r->addRoute('GET', '/api/suppliers', ['App\Controllers\SupplierController', 'index']);
    $r->addRoute('POST', '/api/suppliers', ['App\Controllers\SupplierController', 'create']);
    $r->addRoute('GET', '/api/suppliers/{id:\d+}', ['App\Controllers\SupplierController', 'show']);
    $r->addRoute('PUT', '/api/suppliers/{id:\d+}', ['App\Controllers\SupplierController', 'update']);
    $r->addRoute('DELETE', '/api/suppliers/{id:\d+}', ['App\Controllers\SupplierController', 'delete']);
};
