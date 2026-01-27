<?php
/**
 * Admin API Routes
 * Requires X-Admin-Key header authentication (master key)
 */
use FastRoute\RouteCollector;

return function (RouteCollector $r) {
    // Brands management
    $r->addRoute('GET', '/api/admin/brands', ['App\Controllers\AdminController', 'listBrands']);
    $r->addRoute('POST', '/api/admin/brands', ['App\Controllers\AdminController', 'createBrand']);
    $r->addRoute('GET', '/api/admin/brands/{id:\d+}', ['App\Controllers\AdminController', 'showBrand']);
    $r->addRoute('PUT', '/api/admin/brands/{id:\d+}', ['App\Controllers\AdminController', 'updateBrand']);
    $r->addRoute('DELETE', '/api/admin/brands/{id:\d+}', ['App\Controllers\AdminController', 'deleteBrand']);
    $r->addRoute('POST', '/api/admin/brands/{id:\d+}/regenerate-key', ['App\Controllers\AdminController', 'regenerateBrandApiKey']);

    // Suppliers management
    $r->addRoute('GET', '/api/admin/suppliers', ['App\Controllers\AdminController', 'listSuppliers']);
    $r->addRoute('POST', '/api/admin/suppliers', ['App\Controllers\AdminController', 'createSupplier']);
    $r->addRoute('GET', '/api/admin/suppliers/{id:\d+}', ['App\Controllers\AdminController', 'showSupplier']);
    $r->addRoute('PUT', '/api/admin/suppliers/{id:\d+}', ['App\Controllers\AdminController', 'updateSupplier']);
    $r->addRoute('DELETE', '/api/admin/suppliers/{id:\d+}', ['App\Controllers\AdminController', 'deleteSupplier']);
    $r->addRoute('POST', '/api/admin/suppliers/{id:\d+}/regenerate-key', ['App\Controllers\AdminController', 'regenerateSupplierApiKey']);

    // Brand-Supplier relations
    $r->addRoute('GET', '/api/admin/relations', ['App\Controllers\AdminController', 'listRelations']);
    $r->addRoute('POST', '/api/admin/relations', ['App\Controllers\AdminController', 'createRelation']);
    $r->addRoute('GET', '/api/admin/relations/{id:\d+}', ['App\Controllers\AdminController', 'showRelation']);
    $r->addRoute('PUT', '/api/admin/relations/{id:\d+}', ['App\Controllers\AdminController', 'updateRelation']);
    $r->addRoute('DELETE', '/api/admin/relations/{id:\d+}', ['App\Controllers\AdminController', 'deleteRelation']);

    // Statistics
    $r->addRoute('GET', '/api/admin/stats', ['App\Controllers\AdminController', 'getStats']);
};
