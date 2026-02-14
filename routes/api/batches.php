<?php
use FastRoute\RouteCollector;

return function (RouteCollector $r) {
    // ============================================
    // PURCHASE ORDERS (Brand skapar, Supplier läser)
    // ============================================

    // Brand: lista egna PO:er. Supplier: lista PO:er riktade till sig
    $r->addRoute('GET', '/api/purchase-orders', ['App\Controllers\PurchaseOrderController', 'indexAll']);

    // Brand: lista PO:er för en specifik supplier
    $r->addRoute('GET', '/api/suppliers/{supplierId:\d+}/purchase-orders', ['App\Controllers\PurchaseOrderController', 'indexBySupplier']);

    // Brand: lista PO:er för en specifik produkt
    $r->addRoute('GET', '/api/products/{productId:\d+}/purchase-orders', ['App\Controllers\PurchaseOrderController', 'indexByProduct']);

    // Brand: skapa PO
    $r->addRoute('POST', '/api/purchase-orders', ['App\Controllers\PurchaseOrderController', 'create']);

    // Båda: hämta enskild PO
    $r->addRoute('GET', '/api/purchase-orders/{id:\d+}', ['App\Controllers\PurchaseOrderController', 'show']);

    // Brand: uppdatera PO (draft/sent/cancelled)
    $r->addRoute('PUT', '/api/purchase-orders/{id:\d+}', ['App\Controllers\PurchaseOrderController', 'update']);

    // Supplier: acceptera PO
    $r->addRoute('PUT', '/api/purchase-orders/{id:\d+}/accept', ['App\Controllers\PurchaseOrderController', 'accept']);

    // Brand: ta bort PO (bara draft)
    $r->addRoute('DELETE', '/api/purchase-orders/{id:\d+}', ['App\Controllers\PurchaseOrderController', 'delete']);

    // ============================================
    // PURCHASE ORDER LINES (Brand CRUD, Supplier read-only)
    // ============================================

    $r->addRoute('GET', '/api/purchase-orders/{poId:\d+}/lines', ['App\Controllers\PurchaseOrderLineController', 'index']);
    $r->addRoute('POST', '/api/purchase-orders/{poId:\d+}/lines', ['App\Controllers\PurchaseOrderLineController', 'create']);
    $r->addRoute('GET', '/api/purchase-order-lines/{id:\d+}', ['App\Controllers\PurchaseOrderLineController', 'show']);
    $r->addRoute('PUT', '/api/purchase-order-lines/{id:\d+}', ['App\Controllers\PurchaseOrderLineController', 'update']);
    $r->addRoute('DELETE', '/api/purchase-order-lines/{id:\d+}', ['App\Controllers\PurchaseOrderLineController', 'delete']);

    // ============================================
    // BATCHES (Supplier skapar under en PO)
    // ============================================

    // Lista batchar för en PO
    $r->addRoute('GET', '/api/purchase-orders/{poId:\d+}/batches', ['App\Controllers\BatchController', 'indexByPo']);

    // Supplier: skapa batch under en PO
    $r->addRoute('POST', '/api/purchase-orders/{poId:\d+}/batches', ['App\Controllers\BatchController', 'create']);

    // Alla batchar (filtrerat per tenant)
    $r->addRoute('GET', '/api/batches', ['App\Controllers\BatchController', 'indexAll']);

    // Enskild batch
    $r->addRoute('GET', '/api/batches/{id:\d+}', ['App\Controllers\BatchController', 'show']);

    // Supplier: uppdatera batch (status, quantity, facility, production_date)
    $r->addRoute('PUT', '/api/batches/{id:\d+}', ['App\Controllers\BatchController', 'update']);

    // Supplier: ta bort batch (bara om inga items)
    $r->addRoute('DELETE', '/api/batches/{id:\d+}', ['App\Controllers\BatchController', 'delete']);

    // ============================================
    // BATCH MATERIALS (Supplier CRUD)
    // ============================================

    $r->addRoute('GET', '/api/batches/{batchId:\d+}/materials', ['App\Controllers\BatchMaterialController', 'index']);
    $r->addRoute('POST', '/api/batches/{batchId:\d+}/materials', ['App\Controllers\BatchMaterialController', 'create']);
    $r->addRoute('GET', '/api/batch-materials/{id:\d+}', ['App\Controllers\BatchMaterialController', 'show']);
    $r->addRoute('PUT', '/api/batch-materials/{id:\d+}', ['App\Controllers\BatchMaterialController', 'update']);
    $r->addRoute('DELETE', '/api/batch-materials/{id:\d+}', ['App\Controllers\BatchMaterialController', 'delete']);
};