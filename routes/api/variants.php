<?php
use FastRoute\RouteCollector;

return function (RouteCollector $r) {
    $r->addRoute('GET', '/api/products/{productId:\d+}/variants', ['App\Controllers\ProductVariantController', 'index']);
    $r->addRoute('POST', '/api/products/{productId:\d+}/variants', ['App\Controllers\ProductVariantController', 'create']);
    $r->addRoute('GET', '/api/variants/{id:\d+}', ['App\Controllers\ProductVariantController', 'show']);
    $r->addRoute('PUT', '/api/variants/{id:\d+}', ['App\Controllers\ProductVariantController', 'update']);
    $r->addRoute('DELETE', '/api/variants/{id:\d+}', ['App\Controllers\ProductVariantController', 'delete']);
};
