<?php
require_once __DIR__ . '/../../src/Config/Auth.php';
use App\Config\Auth;
Auth::requireLogin();
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <title>Items - DPP Test</title>
    <style>
        body { font-family: Arial; margin: 0; padding: 20px; background: #f5f5f5; }
        .header { background: #1a237e; color: white; padding: 20px; margin: -20px -20px 20px; }
        .header a { color: white; }
        .container { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-section { background: white; padding: 20px; border-radius: 8px; max-height: 85vh; overflow-y: auto; }
        .response-section { background: #263238; color: #4CAF50; padding: 20px; border-radius: 8px; font-family: monospace; white-space: pre-wrap; min-height: 400px; overflow-y: auto; }
        input, select { width: 100%; padding: 10px; margin: 5px 0 15px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        label { font-weight: bold; color: #666; }
        button { padding: 12px 24px; margin: 5px 5px 5px 0; border: none; border-radius: 4px; cursor: pointer; color: white; }
        .btn-get { background: #4CAF50; }
        .btn-post { background: #2196F3; }
        .btn-delete { background: #f44336; }
        .btn-refresh { background: #9C27B0; }
        .error { color: #f44336; }

        /* Collapsible sections */
        .section { border: 1px solid #e0e0e0; border-radius: 8px; margin-bottom: 10px; overflow: hidden; }
        .section-header { background: #f5f5f5; padding: 12px 15px; cursor: pointer; display: flex; align-items: center; user-select: none; }
        .section-header:hover { background: #eeeeee; }
        .section-header h2 { margin: 0; color: #2196F3; font-size: 16px; flex: 1; }
        .section-header::before { content: '\25B6'; margin-right: 10px; font-size: 12px; transition: transform 0.2s; }
        .section.open .section-header::before { transform: rotate(90deg); }
        .section-content { display: none; padding: 15px; border-top: 1px solid #e0e0e0; }
        .section.open .section-content { display: block; }
    </style>
</head>
<body>
    <div class="header">
        <a href="test.php">&larr; Tillbaka</a>
        <a href="docs/dataflow.html" style="float: right;">Dataflöde &rarr;</a>
        <h1>Items (Individuella produkter)</h1>
        <p style="margin: 5px 0 0; opacity: 0.8; font-size: 14px;">🆕 Per plagg (Fas 2)</p>
        <div id="tenant_banner" style="margin-top: 10px; padding: 8px 15px; background: rgba(255,255,255,0.2); border-radius: 4px; display: inline-block; font-size: 13px;"></div>
    </div>

    <div class="container">
        <div class="form-section">
            <button class="btn-refresh" onclick="loadAll()">Ladda om data</button>

            <div class="section" id="sec-select">
                <div class="section-header" onclick="toggle('sec-select')"><h2>Välj batch</h2></div>
                <div class="section-content">
                    <div id="brand_wrapper">
                        <label>Brand:</label>
                        <select id="brand_id"></select>
                    </div>
                    <label>Product:</label>
                    <select id="product_id"></select>
                    <label>Batch:</label>
                    <select id="batch_id"></select>
                    <button class="btn-get" onclick="api('GET', '/api/batches/' + document.getElementById('batch_id').value + '/items')">Hämta items</button>
                </div>
            </div>

            <div class="section" id="sec-create">
                <div class="section-header" onclick="toggle('sec-create')"><h2>Skapa enskild item</h2></div>
                <div class="section-content">
                    <label>SGTIN (genereras automatiskt om tomt):</label>
                    <input type="text" id="sgtin" placeholder="7350012345001.000001">
                    <label>Data Carrier Type:</label>
                    <input type="text" id="data_carrier_type" placeholder="QR, NFC, RFID">
                    <button class="btn-post" onclick="create()">Skapa</button>
                </div>
            </div>

            <div class="section" id="sec-bulk">
                <div class="section-header" onclick="toggle('sec-bulk')"><h2>Skapa flera (bulk)</h2></div>
                <div class="section-content">
                    <label>Antal:</label>
                    <input type="number" id="bulk_quantity" placeholder="10" value="10">
                    <label>Prefix:</label>
                    <input type="text" id="bulk_prefix" placeholder="DPP" value="DPP">
                    <button class="btn-post" onclick="createBulk()">Skapa bulk</button>
                </div>
            </div>

            <div class="section" id="sec-get">
                <div class="section-header" onclick="toggle('sec-get')"><h2>Hämta item</h2></div>
                <div class="section-content">
                    <label>Item:</label>
                    <select id="item_id_select"></select>
                    <button class="btn-get" onclick="api('GET', '/api/items/' + getItemId())">Hämta</button>
                    <button class="btn-delete" onclick="api('DELETE', '/api/items/' + getItemId())">Ta bort</button>
                </div>
            </div>

            <div class="section" id="sec-search">
                <div class="section-header" onclick="toggle('sec-search')"><h2>Sök på serienummer</h2></div>
                <div class="section-content">
                    <label>Serienummer:</label>
                    <input type="text" id="search_serial" placeholder="DPP-XXXXXXXX-TIMESTAMP">
                    <button class="btn-get" onclick="api('GET', '/api/items/serial/' + document.getElementById('search_serial').value)">Sök</button>
                </div>
            </div>
        </div>

        <div class="response-section" id="response">Klicka på en knapp för att se resultat...</div>
    </div>

    <script>
        function toggle(id) {
            document.getElementById(id).classList.toggle('open');
        }

        function getItemId() {
            const val = document.getElementById('item_id_select').value;
            if (!val) {
                document.getElementById('response').textContent = 'Välj en item först!';
                document.getElementById('response').className = 'response-section error';
                return null;
            }
            return val;
        }

        function getApiKey() {
            const apiKey = localStorage.getItem('dpp_api_key');
            if (!apiKey) {
                document.getElementById('response').textContent = 'Välj en tenant på huvudsidan först!';
                document.getElementById('response').className = 'response-section error';
                return null;
            }
            return apiKey;
        }

        function showTenantBanner() {
            const tenantName = localStorage.getItem('dpp_tenant_name');
            const tenantType = localStorage.getItem('dpp_tenant_type');
            const banner = document.getElementById('tenant_banner');
            if (tenantName && tenantType) {
                const typeLabel = tenantType === 'brand' ? 'Brand' : 'Supplier';
                banner.textContent = 'Testar som ' + typeLabel + ': ' + tenantName;
                banner.style.display = 'inline-block';
                banner.style.background = tenantType === 'brand' ? 'rgba(123,31,162,0.5)' : 'rgba(21,101,192,0.5)';
            } else {
                banner.textContent = 'Ingen tenant vald - välj på huvudsidan';
                banner.style.background = 'rgba(255,0,0,0.3)';
            }
        }

        async function api(method, endpoint, data = null) {
            // Prevent API calls with invalid IDs
            if (endpoint.includes('/null/') || endpoint.includes('//') || endpoint.endsWith('/null')) {
                return;
            }
            const apiKey = getApiKey();
            if (!apiKey) return;

            const opts = {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-API-Key': apiKey
                }
            };
            if (data) opts.body = JSON.stringify(data);
            try {
                const res = await fetch(endpoint, opts);
                const json = await res.json();
                document.getElementById('response').textContent = JSON.stringify(json, null, 2);
                document.getElementById('response').className = 'response-section' + (json.error ? ' error' : '');
            } catch (e) {
                document.getElementById('response').textContent = 'Error: ' + e.message;
                document.getElementById('response').className = 'response-section error';
            }
        }

        async function loadBrands() {
            const apiKey = getApiKey();
            if (!apiKey) return;

            const tenantType = localStorage.getItem('dpp_tenant_type');
            const tenantId = localStorage.getItem('dpp_tenant_id');

            const res = await fetch('/api/brands/all', {
                headers: { 'X-API-Key': apiKey }
            });
            const json = await res.json();
            const select = document.getElementById('brand_id');
            select.innerHTML = '<option value="">-- Välj brand --</option>';
            if (json.data) {
                json.data.forEach(b => {
                    const selected = (tenantType === 'brand' && b.id == tenantId) ? ' selected' : '';
                    select.innerHTML += `<option value="${b.id}"${selected}>${b.id}: ${b.brand_name}</option>`;
                });
                // Hide brand dropdown if tenant is a brand (only one option)
                if (tenantType === 'brand' && tenantId) {
                    document.getElementById('brand_wrapper').style.display = 'none';
                    loadProducts();
                }
            }
        }

        async function loadProducts() {
            const apiKey = getApiKey();
            if (!apiKey) return;

            const brandId = document.getElementById('brand_id').value;
            const select = document.getElementById('product_id');
            select.innerHTML = '<option value="">-- Välj produkt --</option>';
            clearDown('batch_id', 'item_id_select');

            if (brandId) {
                const res = await fetch('/api/brands/' + brandId + '/products', {
                    headers: { 'X-API-Key': apiKey }
                });
                const json = await res.json();
                if (json.data) {
                    json.data.forEach(p => {
                        select.innerHTML += `<option value="${p.id}">${p.id}: ${p.product_name}</option>`;
                    });
                }
            }
        }

        async function loadBatches() {
            const apiKey = getApiKey();
            if (!apiKey) return;

            const productId = document.getElementById('product_id').value;
            const select = document.getElementById('batch_id');
            select.innerHTML = '<option value="">-- Välj batch --</option>';
            clearDown('item_id_select');

            if (productId) {
                const res = await fetch('/api/products/' + productId + '/batches', {
                    headers: { 'X-API-Key': apiKey }
                });
                const json = await res.json();
                if (json.data) {
                    json.data.forEach(b => {
                        select.innerHTML += `<option value="${b.id}">${b.id}: ${b.batch_number}</option>`;
                    });
                }
            }
        }

        async function loadItems() {
            const apiKey = getApiKey();
            if (!apiKey) return;

            const batchId = document.getElementById('batch_id').value;
            const select = document.getElementById('item_id_select');
            select.innerHTML = '<option value="">-- Välj item --</option>';

            if (batchId) {
                const res = await fetch('/api/batches/' + batchId + '/items', {
                    headers: { 'X-API-Key': apiKey }
                });
                const json = await res.json();
                if (json.data) {
                    json.data.forEach(i => {
                        select.innerHTML += `<option value="${i.id}">${i.id}: ${i.sgtin}</option>`;
                    });
                }
            }
        }

        function clearDown(...ids) {
            ids.forEach(id => {
                const el = document.getElementById(id);
                const label = id.replace('_', ' ');
                el.innerHTML = `<option value="">-- Välj ${label} --</option>`;
            });
        }

        function loadAll() {
            loadBrands();
            clearDown('product_id', 'batch_id', 'item_id_select');
        }

        document.getElementById('brand_id').addEventListener('change', loadProducts);
        document.getElementById('product_id').addEventListener('change', loadBatches);
        document.getElementById('batch_id').addEventListener('change', loadItems);

        showTenantBanner();
        loadAll();

        function create() {
            const data = {
                data_carrier_type: document.getElementById('data_carrier_type').value || null
            };
            const sgtin = document.getElementById('sgtin').value;
            if (sgtin) data.sgtin = sgtin;

            api('POST', '/api/batches/' + document.getElementById('batch_id').value + '/items', data)
                .then(() => loadItems());
        }

        function createBulk() {
            api('POST', '/api/batches/' + document.getElementById('batch_id').value + '/items/bulk', {
                quantity: parseInt(document.getElementById('bulk_quantity').value),
                prefix: document.getElementById('bulk_prefix').value || 'DPP'
            }).then(() => loadItems());
        }
    </script>
</body>
</html>
