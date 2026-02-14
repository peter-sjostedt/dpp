<?php
/**
 * DPP API Test Suite
 * Testar ALLA endpoints och rapporterar fel
 *
 * Uppdaterad för nytt PO-flöde:
 * - PurchaseOrderController (ny)
 * - BatchController (supplier skapar under PO)
 * - BatchMaterialController (supplier CRUD)
 * - ItemController (supplier skapar)
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Konfig
$baseUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/api';
$adminKey = 'dpp_admin_master_key_2024_secure';

// Hämta test-API-nycklar från databasen
$db = \App\Config\Database::getInstance()->getConnection();
$brandRow = $db->query("SELECT id, api_key FROM brands WHERE _is_active = 1 LIMIT 1")->fetch();
$supplierRow = $db->query("SELECT id, api_key FROM suppliers WHERE _is_active = 1 LIMIT 1")->fetch();

$brandKey = $brandRow['api_key'] ?? null;
$brandId = $brandRow['id'] ?? 1;
$supplierKey = $supplierRow['api_key'] ?? null;
$supplierId = $supplierRow['id'] ?? 1;

// Hämta existerande IDs för tester
$productId = $db->query("SELECT id FROM products WHERE _is_active = 1 LIMIT 1")->fetchColumn() ?: 1;
$variantId = $db->query("SELECT id FROM product_variants WHERE _is_active = 1 LIMIT 1")->fetchColumn() ?: 1;
$poId = $db->query("SELECT id FROM purchase_orders LIMIT 1")->fetchColumn() ?: 1;
$batchId = $db->query("SELECT id FROM batches LIMIT 1")->fetchColumn() ?: 1;
$itemId = $db->query("SELECT id FROM items LIMIT 1")->fetchColumn() ?: 1;
$materialId = $db->query("SELECT id FROM factory_materials WHERE _is_active = 1 LIMIT 1")->fetchColumn() ?: 1;
$componentId = $db->query("SELECT id FROM product_components LIMIT 1")->fetchColumn() ?: 1;
$compositionId = $db->query("SELECT id FROM factory_material_compositions LIMIT 1")->fetchColumn() ?: 1;
$materialCertId = $db->query("SELECT id FROM factory_material_certifications LIMIT 1")->fetchColumn() ?: 1;
$supplyChainId = $db->query("SELECT id FROM factory_material_supply_chain LIMIT 1")->fetchColumn() ?: 1;
$batchMaterialId = $db->query("SELECT id FROM batch_materials LIMIT 1")->fetchColumn() ?: 1;
$brandSupplierId = $db->query("SELECT id FROM brand_suppliers WHERE _is_active = 1 LIMIT 1")->fetchColumn() ?: 1;
$relationId = $brandSupplierId;

// Resultat
$results = [];
$totalPassed = 0;
$totalFailed = 0;

/**
 * Gör ett API-anrop
 */
function apiCall(string $method, string $endpoint, ?array $data = null, ?string $apiKey = null, string $keyType = 'tenant'): array
{
    global $baseUrl;

    $ch = curl_init();
    $url = $baseUrl . $endpoint;

    $headers = ['Content-Type: application/json'];
    if ($apiKey) {
        $headers[] = ($keyType === 'admin' ? 'X-Admin-Key: ' : 'X-API-Key: ') . $apiKey;
    }

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 30,
    ]);

    if ($data && in_array($method, ['POST', 'PUT'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    return [
        'code' => $httpCode,
        'body' => json_decode($response, true) ?? $response,
        'curl_error' => $curlError,
    ];
}

/**
 * Testa en endpoint
 */
function testEndpoint(string $name, string $method, string $endpoint, ?array $data, ?string $apiKey, string $keyType, array $expectedCodes): void
{
    global $results, $totalPassed, $totalFailed;

    $response = apiCall($method, $endpoint, $data, $apiKey, $keyType);

    $success = in_array($response['code'], $expectedCodes) && empty($response['curl_error']);

    if ($success && in_array($response['code'], [200, 201])) {
        if (is_array($response['body']) && isset($response['body']['error'])) {
            $success = false;
        }
    }

    $result = [
        'name' => $name,
        'method' => $method,
        'endpoint' => $endpoint,
        'expected' => implode('/', $expectedCodes),
        'actual' => $response['code'],
        'success' => $success,
        'error' => null,
    ];

    if (!$success) {
        $totalFailed++;
        if ($response['curl_error']) {
            $result['error'] = 'CURL: ' . $response['curl_error'];
        } elseif (is_array($response['body']) && isset($response['body']['error'])) {
            $result['error'] = $response['body']['error'];
        } else {
            $result['error'] = "HTTP {$response['code']}";
        }
    } else {
        $totalPassed++;
    }

    $results[] = $result;
}

// ============================================
// DEFINIERA ALLA TESTER
// ============================================

$tests = [
    // ========================================
    // ADMIN API - Brands
    // ========================================
    ['Admin: GET brands', 'GET', '/admin/brands', null, $adminKey, 'admin', [200]],
    ['Admin: GET brand/{id}', 'GET', "/admin/brands/{$brandId}", null, $adminKey, 'admin', [200, 404]],
    ['Admin: GET stats', 'GET', '/admin/stats', null, $adminKey, 'admin', [200]],

    // ========================================
    // ADMIN API - Suppliers
    // ========================================
    ['Admin: GET suppliers', 'GET', '/admin/suppliers', null, $adminKey, 'admin', [200]],
    ['Admin: GET supplier/{id}', 'GET', "/admin/suppliers/{$supplierId}", null, $adminKey, 'admin', [200, 404]],

    // ========================================
    // ADMIN API - Relations
    // ========================================
    ['Admin: GET relations', 'GET', '/admin/relations', null, $adminKey, 'admin', [200]],
    ['Admin: GET relation/{id}', 'GET', "/admin/relations/{$relationId}", null, $adminKey, 'admin', [200, 404]],

    // ========================================
    // TENANT API - No Auth (tenants endpoint)
    // ========================================
    ['Tenants: GET brands', 'GET', '/tenants/brands', null, null, 'tenant', [200]],
    ['Tenants: GET suppliers', 'GET', '/tenants/suppliers', null, null, 'tenant', [200]],

    // ========================================
    // TENANT API - Brands
    // ========================================
    ['Brand: GET brands', 'GET', '/brands', null, $brandKey, 'tenant', [200]],
    ['Brand: GET brands/all', 'GET', '/brands/all', null, $brandKey, 'tenant', [200]],
    ['Brand: GET brand/{id}', 'GET', "/brands/{$brandId}", null, $brandKey, 'tenant', [200, 404]],

    // ========================================
    // TENANT API - Suppliers
    // ========================================
    ['Brand: GET suppliers', 'GET', '/suppliers', null, $brandKey, 'tenant', [200]],
    ['Brand: GET supplier/{id}', 'GET', "/suppliers/{$supplierId}", null, $brandKey, 'tenant', [200, 404]],
    ['Supplier: GET suppliers', 'GET', '/suppliers', null, $supplierKey, 'tenant', [200]],
    ['Supplier: GET supplier/{id}', 'GET', "/suppliers/{$supplierId}", null, $supplierKey, 'tenant', [200, 404]],

    // ========================================
    // TENANT API - Products (Brand)
    // ========================================
    ['Brand: GET products', 'GET', '/products', null, $brandKey, 'tenant', [200]],
    ['Brand: GET products by brand', 'GET', "/brands/{$brandId}/products", null, $brandKey, 'tenant', [200]],
    ['Brand: GET product/{id}', 'GET', "/products/{$productId}", null, $brandKey, 'tenant', [200, 404]],
    ['Brand: GET product DPP', 'GET', "/products/{$productId}/dpp", null, $brandKey, 'tenant', [200, 404]],

    // ========================================
    // TENANT API - Variants (Brand)
    // ========================================
    ['Brand: GET variants by product', 'GET', "/products/{$productId}/variants", null, $brandKey, 'tenant', [200]],
    ['Brand: GET variant/{id}', 'GET', "/variants/{$variantId}", null, $brandKey, 'tenant', [200, 404]],

    // ========================================
    // TENANT API - Product Components
    // ========================================
    ['Brand: GET components', 'GET', "/products/{$productId}/components", null, $brandKey, 'tenant', [200]],
    ['Brand: GET component/{id}', 'GET', "/components/{$componentId}", null, $brandKey, 'tenant', [200, 404]],

    // ========================================
    // TENANT API - Care Information
    // ========================================
    ['Brand: GET care info', 'GET', "/products/{$productId}/care", null, $brandKey, 'tenant', [200, 404]],

    // ========================================
    // TENANT API - Compliance
    // ========================================
    ['Brand: GET compliance', 'GET', "/products/{$productId}/compliance", null, $brandKey, 'tenant', [200, 404]],

    // ========================================
    // TENANT API - Circularity
    // ========================================
    ['Brand: GET circularity', 'GET', "/products/{$productId}/circularity", null, $brandKey, 'tenant', [200, 404]],

    // ========================================
    // TENANT API - Sustainability
    // ========================================
    ['Brand: GET sustainability', 'GET', "/products/{$productId}/sustainability", null, $brandKey, 'tenant', [200, 404]],

    // ========================================
    // TENANT API - DPP Export
    // ========================================
    ['Brand: GET DPP preview', 'GET', "/products/{$productId}/dpp/preview", null, $brandKey, 'tenant', [200, 404]],
    ['Brand: GET DPP validate', 'GET', "/products/{$productId}/dpp/validate", null, $brandKey, 'tenant', [200, 404]],
    ['Brand: GET DPP export', 'GET', "/products/{$productId}/dpp/export", null, $brandKey, 'tenant', [200, 404]],

    // ========================================
    // TENANT API - Purchase Orders (NY!)
    // ========================================
    // Brand
    ['Brand: GET purchase-orders', 'GET', '/purchase-orders', null, $brandKey, 'tenant', [200]],
    ['Brand: GET purchase-order/{id}', 'GET', "/purchase-orders/{$poId}", null, $brandKey, 'tenant', [200, 404]],
    ['Brand: GET POs by supplier', 'GET', "/suppliers/{$supplierId}/purchase-orders", null, $brandKey, 'tenant', [200, 404]],
    ['Brand: GET POs by product', 'GET', "/products/{$productId}/purchase-orders", null, $brandKey, 'tenant', [200, 404]],
    // Supplier
    ['Supplier: GET purchase-orders', 'GET', '/purchase-orders', null, $supplierKey, 'tenant', [200]],
    ['Supplier: GET purchase-order/{id}', 'GET', "/purchase-orders/{$poId}", null, $supplierKey, 'tenant', [200, 404]],
    ['Supplier: GET POs by supplier', 'GET', "/suppliers/{$supplierId}/purchase-orders", null, $supplierKey, 'tenant', [200, 404]],

    // ========================================
    // TENANT API - Batches (ändrad: supplier skapar)
    // ========================================
    // Brand (read-only)
    ['Brand: GET batches', 'GET', '/batches', null, $brandKey, 'tenant', [200]],
    ['Brand: GET batches by PO', 'GET', "/purchase-orders/{$poId}/batches", null, $brandKey, 'tenant', [200, 404]],
    ['Brand: GET batch/{id}', 'GET', "/batches/{$batchId}", null, $brandKey, 'tenant', [200, 404]],
    // Supplier (CRUD)
    ['Supplier: GET batches', 'GET', '/batches', null, $supplierKey, 'tenant', [200]],
    ['Supplier: GET batches by PO', 'GET', "/purchase-orders/{$poId}/batches", null, $supplierKey, 'tenant', [200, 404]],
    ['Supplier: GET batch/{id}', 'GET', "/batches/{$batchId}", null, $supplierKey, 'tenant', [200, 404]],

    // ========================================
    // TENANT API - Batch Materials (ändrad: supplier CRUD)
    // ========================================
    // Brand (read-only)
    ['Brand: GET batch materials', 'GET', "/batches/{$batchId}/materials", null, $brandKey, 'tenant', [200, 404]],
    ['Brand: GET batch-material/{id}', 'GET', "/batch-materials/{$batchMaterialId}", null, $brandKey, 'tenant', [200, 404]],
    // Supplier
    ['Supplier: GET batch materials', 'GET', "/batches/{$batchId}/materials", null, $supplierKey, 'tenant', [200, 404]],
    ['Supplier: GET batch-material/{id}', 'GET', "/batch-materials/{$batchMaterialId}", null, $supplierKey, 'tenant', [200, 404]],

    // ========================================
    // TENANT API - Items (ändrad: supplier skapar)
    // ========================================
    // Brand (read-only)
    ['Brand: GET items by batch', 'GET', "/batches/{$batchId}/items", null, $brandKey, 'tenant', [200, 404]],
    ['Brand: GET item/{id}', 'GET', "/items/{$itemId}", null, $brandKey, 'tenant', [200, 404]],
    // Supplier
    ['Supplier: GET items by batch', 'GET', "/batches/{$batchId}/items", null, $supplierKey, 'tenant', [200, 404]],
    ['Supplier: GET item/{id}', 'GET', "/items/{$itemId}", null, $supplierKey, 'tenant', [200, 404]],

    // ========================================
    // TENANT API - Materials (Supplier)
    // ========================================
    ['Supplier: GET materials', 'GET', '/materials', null, $supplierKey, 'tenant', [200]],
    ['Supplier: GET materials by supplier', 'GET', "/suppliers/{$supplierId}/materials", null, $supplierKey, 'tenant', [200]],
    ['Supplier: GET material/{id}', 'GET', "/materials/{$materialId}", null, $supplierKey, 'tenant', [200, 404]],
    ['Supplier: GET material batches', 'GET', "/materials/{$materialId}/batches", null, $supplierKey, 'tenant', [200]],
    // Brand (read-only via relation)
    ['Brand: GET materials', 'GET', '/materials', null, $brandKey, 'tenant', [200]],
    ['Brand: GET material/{id}', 'GET', "/materials/{$materialId}", null, $brandKey, 'tenant', [200, 404]],
    ['Brand: GET material batches', 'GET', "/materials/{$materialId}/batches", null, $brandKey, 'tenant', [200]],

    // ========================================
    // TENANT API - Material Compositions
    // ========================================
    ['Supplier: GET compositions', 'GET', "/materials/{$materialId}/compositions", null, $supplierKey, 'tenant', [200]],
    ['Supplier: GET composition/{id}', 'GET', "/compositions/{$compositionId}", null, $supplierKey, 'tenant', [200, 404]],

    // ========================================
    // TENANT API - Material Certifications
    // ========================================
    ['Supplier: GET material certs', 'GET', "/materials/{$materialId}/certifications", null, $supplierKey, 'tenant', [200]],
    ['Supplier: GET material cert/{id}', 'GET', "/material-certifications/{$materialCertId}", null, $supplierKey, 'tenant', [200, 404]],

    // ========================================
    // TENANT API - Material Supply Chain
    // ========================================
    ['Supplier: GET supply chain', 'GET', "/materials/{$materialId}/supply-chain", null, $supplierKey, 'tenant', [200]],
    ['Supplier: GET supply-chain/{id}', 'GET', "/supply-chain/{$supplyChainId}", null, $supplierKey, 'tenant', [200, 404]],

    // ========================================
    // TENANT API - Brand-Suppliers
    // ========================================
    ['Brand: GET brand-suppliers', 'GET', '/brand-suppliers', null, $brandKey, 'tenant', [200]],
    ['Brand: GET available suppliers', 'GET', '/brand-suppliers/available', null, $brandKey, 'tenant', [200]],
    ['Brand: GET brand-supplier/{id}', 'GET', "/brand-suppliers/{$brandSupplierId}", null, $brandKey, 'tenant', [200, 404]],
    ['Supplier: GET brand-suppliers', 'GET', '/brand-suppliers', null, $supplierKey, 'tenant', [200]],

    // ========================================
    // AUTH - Verify rejection without key
    // ========================================
    ['Auth: No key rejected', 'GET', '/products', null, null, 'tenant', [401]],
    ['Auth: Invalid key rejected', 'GET', '/products', null, 'invalid_key_12345', 'tenant', [401]],
    ['Admin Auth: No key rejected', 'GET', '/admin/brands', null, null, 'admin', [401]],
    ['Admin Auth: Invalid key rejected', 'GET', '/admin/brands', null, 'invalid_admin_key', 'admin', [401]],

    // ========================================
    // ACCESS CONTROL - Cross-tenant rejection
    // ========================================
    ['Supplier: Cannot create product', 'POST', "/brands/{$brandId}/products", ['product_name' => 'Test'], $supplierKey, 'tenant', [403]],
    ['Supplier: Cannot create PO', 'POST', '/purchase-orders', ['supplier_id' => $supplierId, 'product_id' => $productId, 'po_number' => 'TEST'], $supplierKey, 'tenant', [403]],
    ['Brand: Cannot create batch', 'POST', "/purchase-orders/{$poId}/batches", ['batch_number' => 'TEST'], $brandKey, 'tenant', [403]],
    ['Brand: Cannot create batch material', 'POST', "/batches/{$batchId}/materials", ['factory_material_id' => $materialId], $brandKey, 'tenant', [403, 404]],
    ['Brand: Cannot create item', 'POST', "/batches/{$batchId}/items", ['serial_number' => 'TEST'], $brandKey, 'tenant', [403, 404]],
];

// ============================================
// KÖR ALLA TESTER
// ============================================

foreach ($tests as $test) {
    testEndpoint(...$test);
}

// ============================================
// VISA RESULTAT
// ============================================

$html = true;

if ($html):
?>
<!DOCTYPE html>
<html>
<head>
    <title>DPP API Test Results</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 20px; background: #f5f5f5; }
        h1 { color: #333; }
        .summary { padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .summary.pass { background: #d4edda; border: 1px solid #c3e6cb; }
        .summary.fail { background: #f8d7da; border: 1px solid #f5c6cb; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #333; color: white; }
        tr:hover { background: #f9f9f9; }
        .pass { color: #28a745; font-weight: bold; }
        .fail { color: #dc3545; font-weight: bold; }
        .error { color: #dc3545; font-size: 0.9em; }
        .method { font-family: monospace; padding: 2px 6px; border-radius: 4px; font-size: 0.85em; }
        .method.GET { background: #61affe; color: white; }
        .method.POST { background: #49cc90; color: white; }
        .method.PUT { background: #fca130; color: white; }
        .method.DELETE { background: #f93e3e; color: white; }
        code { background: #eee; padding: 2px 6px; border-radius: 4px; font-size: 0.9em; }
        .back-link { margin-bottom: 20px; }
        .back-link a { color: #007bff; text-decoration: none; }
        .back-link a:hover { text-decoration: underline; }
        .config-info { background: #e3f2fd; border: 1px solid #90caf9; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9em; }
        .config-info code { background: #bbdefb; }
    </style>
</head>
<body>
    <div class="back-link"><a href="test.php">&larr; Tillbaka till Test Panel</a></div>

    <h1>DPP API Test Results</h1>

    <div class="config-info">
        <strong>Test Configuration:</strong><br>
        Brand API Key: <code><?= $brandKey ? substr($brandKey, 0, 20) . '...' : 'NOT FOUND' ?></code> (ID: <?= $brandId ?>)<br>
        Supplier API Key: <code><?= $supplierKey ? substr($supplierKey, 0, 20) . '...' : 'NOT FOUND' ?></code> (ID: <?= $supplierId ?>)<br>
        Test IDs: Product=<?= $productId ?>, Variant=<?= $variantId ?>, PO=<?= $poId ?>, Batch=<?= $batchId ?>, Item=<?= $itemId ?>, Material=<?= $materialId ?>
    </div>

    <div class="summary <?= $totalFailed === 0 ? 'pass' : 'fail' ?>">
        <strong>Totalt:</strong> <?= $totalPassed ?> godkanda, <?= $totalFailed ?> misslyckade av <?= count($results) ?> tester
        <?php if ($totalFailed === 0): ?>
            <br>&#10003; Alla API:er fungerar korrekt
        <?php else: ?>
            <br>&#10007; Det finns fel som behover atgardas
        <?php endif; ?>
    </div>

    <?php if ($totalFailed > 0): ?>
    <h2>Fel</h2>
    <table>
        <tr>
            <th>Test</th>
            <th>Metod</th>
            <th>Endpoint</th>
            <th>Forvantat</th>
            <th>Resultat</th>
            <th>Fel</th>
        </tr>
        <?php foreach ($results as $r): ?>
            <?php if (!$r['success']): ?>
            <tr>
                <td><?= htmlspecialchars($r['name']) ?></td>
                <td><span class="method <?= $r['method'] ?>"><?= $r['method'] ?></span></td>
                <td><code><?= htmlspecialchars($r['endpoint']) ?></code></td>
                <td><?= $r['expected'] ?></td>
                <td class="fail"><?= $r['actual'] ?></td>
                <td class="error"><?= htmlspecialchars($r['error'] ?? '') ?></td>
            </tr>
            <?php endif; ?>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>

    <h2>Alla tester (<?= count($results) ?>)</h2>
    <table>
        <tr>
            <th>Test</th>
            <th>Metod</th>
            <th>Endpoint</th>
            <th>Forvantat</th>
            <th>Resultat</th>
            <th>Status</th>
        </tr>
        <?php foreach ($results as $r): ?>
        <tr>
            <td><?= htmlspecialchars($r['name']) ?></td>
            <td><span class="method <?= $r['method'] ?>"><?= $r['method'] ?></span></td>
            <td><code><?= htmlspecialchars($r['endpoint']) ?></code></td>
            <td><?= $r['expected'] ?></td>
            <td><?= $r['actual'] ?></td>
            <td class="<?= $r['success'] ? 'pass' : 'fail' ?>"><?= $r['success'] ? '&#10003; OK' : '&#10007; FEL' ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <p style="color: #666; margin-top: 20px;">Testat: <?= date('Y-m-d H:i:s') ?></p>
</body>
</html>
<?php
else:
    echo "\n=== DPP API TEST RESULTS ===\n\n";
    echo "Godkanda: {$totalPassed}\n";
    echo "Misslyckade: {$totalFailed}\n";
    echo "Totalt: " . count($results) . "\n\n";

    if ($totalFailed > 0) {
        echo "=== FEL ===\n\n";
        foreach ($results as $r) {
            if (!$r['success']) {
                echo "X {$r['name']}\n";
                echo "  {$r['method']} {$r['endpoint']}\n";
                echo "  Forvantat: {$r['expected']}, Fick: {$r['actual']}\n";
                echo "  Fel: {$r['error']}\n\n";
            }
        }
    } else {
        echo "V Alla API:er fungerar korrekt\n";
    }
endif;