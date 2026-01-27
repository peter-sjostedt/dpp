<?php
use FastRoute\RouteCollector;

return function (RouteCollector $r) {
    $r->addRoute('GET', '/api/brands', ['App\Controllers\BrandController', 'index']);
    $r->addRoute('GET', '/api/brands/all', ['App\Controllers\BrandController', 'indexAll']);
    $r->addRoute('POST', '/api/brands', ['App\Controllers\BrandController', 'create']);
    $r->addRoute('GET', '/api/brands/{id:\d+}', ['App\Controllers\BrandController', 'show']);
    $r->addRoute('PUT', '/api/brands/{id:\d+}', ['App\Controllers\BrandController', 'update']);
    $r->addRoute('DELETE', '/api/brands/{id:\d+}', ['App\Controllers\BrandController', 'delete']);
};
