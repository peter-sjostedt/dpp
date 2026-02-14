<?php
require_once __DIR__ . '/../../src/Config/Auth.php';
use App\Config\Auth;
Auth::requireLogin();
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <title>Variants - DPP Test</title>
    <style>
        body { font-family: Arial; margin: 0; padding: 20px; background: #f5f5f5; }
        .header { background: #1a237e; color: white; padding: 20px; margin: -20px -20px 20px; }
        .header a { color: white; }
        .container { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-section { background: white; padding: 20px; border-radius: 8px; }
        .response-section { background: #263238; color: #4CAF50; padding: 20px; border-radius: 8px; font-family: monospace; white-space: pre-wrap; min-height: 400px; }
        input, select { width: 100%; padding: 10px; margin: 5px 0 15px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        label { font-weight: bold; color: #666; }
        button { padding: 12px 24px; margin: 5px 5px 5px 0; border: none; border-radius: 4px; cursor: pointer; color: white; }
        .btn-get { background: #4CAF50; }
        .btn-post { background: #2196F3; }
        .btn-put { background: #FF9800; }
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
        <h1>Product Variants</h1>
        <p style="margin: 5px 0 0; opacity: 0.8; font-size: 14px;">♻️ Registrera en gång</p>
        <div id="tenant_banner" style="margin-top: 10px; padding: 8px 15px; background: rgba(255,255,255,0.2); border-radius: 4px; display: inline-block; font-size: 13px;"></div>
    </div>

    <div class="container">
        <div class="form-section">
            <button class="btn-refresh" onclick="loadAll()">Ladda om data</button>

            <div class="section" id="sec-select">
                <div class="section-header" onclick="toggle('sec-select')" id="sec-select-header"><h2>Välj produkt</h2></div>
                <div class="section-content">
                    <div id="brand_wrapper">
                        <label>Brand:</label>
                        <select id="brand_id"></select>
                    </div>
                    <label>Product:</label>
                    <select id="product_id"></select>
                    <button class="btn-get" onclick="api('GET', '/api/products/' + getProductId() + '/variants')">Hämta varianter</button>
                </div>
            </div>

            <div class="section" id="sec-create">
                <div class="section-header" onclick="toggle('sec-create')"><h2>Skapa ny</h2></div>
                <div class="section-content">
                    <label>Artikelnummer:</label>
                    <input type="text" id="item_number" placeholder="ART-12345-M-BLK">
                    <label>Storlek:</label>
                    <input type="text" id="size" placeholder="M">
                    <label>Landskod (ISO):</label>
                    <input type="text" id="size_country_code" placeholder="SE" maxlength="2">
                    <label>Varumärkesfärg:</label>
                    <input type="text" id="color_brand" placeholder="Navy Blue">
                    <label>Standardfärg:</label>
                    <select id="color_general">
                        <option value="">-- Välj --</option>
                        <option value="black">Black</option>
                        <option value="white">White</option>
                        <option value="grey">Grey</option>
                        <option value="navy">Navy</option>
                        <option value="blue">Blue</option>
                        <option value="red">Red</option>
                        <option value="green">Green</option>
                        <option value="yellow">Yellow</option>
                        <option value="orange">Orange</option>
                        <option value="pink">Pink</option>
                        <option value="purple">Purple</option>
                        <option value="brown">Brown</option>
                        <option value="beige">Beige</option>
                        <option value="multicolour">Multicolour</option>
                        <option value="print">Print</option>
                        <option value="other">Other</option>
                    </select>
                    <label>GTIN:</label>
                    <input type="text" id="gtin" placeholder="1234567890123" maxlength="14">
                    <button class="btn-post" onclick="create()">Skapa</button>
                </div>
            </div>

            <div class="section" id="sec-get">
                <div class="section-header" onclick="toggle('sec-get')"><h2>Hämta/Ta bort</h2></div>
                <div class="section-content">
                    <label>Variant:</label>
                    <select id="variant_id_select"></select>
                    <button class="btn-get" onclick="api('GET', '/api/variants/' + getVariantId())">Hämta</button>
                    <button class="btn-delete" onclick="api('DELETE', '/api/variants/' + getVariantId())">Ta bort</button>
                </div>
            </div>
        </div>

        <div class="response-section" id="response">Klicka på en knapp för att se resultat...</div>
    </div>

    <script>
        function toggle(id) {
            document.getElementById(id).classList.toggle('open');
        }

        function getProductId() {
            const val = document.getElementById('product_id').value;
            if (!val) {
                document.getElementById('response').textContent = 'Välj en produkt först!';
                document.getElementById('response').className = 'response-section error';
                return null;
            }
            return val;
        }

        function getVariantId() {
            const val = document.getElementById('variant_id_select').value;
            if (!val) {
                document.getElementById('response').textContent = 'Välj en variant först!';
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
            document.getElementById('variant_id_select').innerHTML = '<option value="">-- Välj variant --</option>';

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

        async function loadVariants() {
            const apiKey = getApiKey();
            if (!apiKey) return;

            const productId = document.getElementById('product_id').value;
            const select = document.getElementById('variant_id_select');
            select.innerHTML = '<option value="">-- Välj variant --</option>';

            if (productId) {
                const res = await fetch('/api/products/' + productId + '/variants', {
                    headers: { 'X-API-Key': apiKey }
                });
                const json = await res.json();
                if (json.data) {
                    json.data.forEach(v => {
                        select.innerHTML += `<option value="${v.id}">${v.id}: ${v.item_number || v.gtin || '-'} (${v.size || '-'} / ${v.color_brand || '-'})</option>`;
                    });
                }
            }
        }

        function loadAll() {
            loadBrands();
            document.getElementById('product_id').innerHTML = '<option value="">-- Välj produkt --</option>';
            document.getElementById('variant_id_select').innerHTML = '<option value="">-- Välj variant --</option>';
        }

        document.getElementById('brand_id').addEventListener('change', loadProducts);
        document.getElementById('product_id').addEventListener('change', loadVariants);

        showTenantBanner();
        loadAll();

        function create() {
            api('POST', '/api/products/' + document.getElementById('product_id').value + '/variants', {
                item_number: document.getElementById('item_number').value || null,
                size: document.getElementById('size').value || null,
                size_country_code: document.getElementById('size_country_code').value || null,
                color_brand: document.getElementById('color_brand').value || null,
                color_general: document.getElementById('color_general').value || null,
                gtin: document.getElementById('gtin').value || null
            }).then(() => loadVariants());
        }
    </script>
</body>
</html>
