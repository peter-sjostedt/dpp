<?php
require_once __DIR__ . '/../src/Config/Auth.php';
use App\Config\Auth;
Auth::requireLogin();
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <title>Batches - DPP Test</title>
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
        <h1>Batches</h1>
        <p style="margin: 5px 0 0; opacity: 0.8; font-size: 14px;">Produktionsordrar per produkt</p>
        <div id="tenant_banner" style="margin-top: 10px; padding: 8px 15px; background: rgba(255,255,255,0.2); border-radius: 4px; display: inline-block; font-size: 13px;"></div>
    </div>

    <div class="container">
        <div class="form-section">
            <button class="btn-refresh" onclick="loadAll()">Ladda om data</button>

            <div class="section open" id="sec-select">
                <div class="section-header" onclick="toggle('sec-select')"><h2>Välj produkt & batch</h2></div>
                <div class="section-content">
                    <div id="brand_wrapper">
                        <label>Brand:</label>
                        <select id="brand_id"></select>
                    </div>
                    <label>Product:</label>
                    <select id="product_id"></select>
                    <label>Batch:</label>
                    <select id="batch_id_select"></select>
                    <button class="btn-get" onclick="api('GET', '/api/batches/' + getBatchId())">Hämta batch-detaljer</button>
                    <button class="btn-delete" onclick="deleteBatch()">Ta bort batch</button>
                </div>
            </div>

            <div class="section" id="sec-create">
                <div class="section-header" onclick="toggle('sec-create')"><h2>Skapa ny batch</h2></div>
                <div class="section-content">
                    <div id="supplier_wrapper">
                        <label>Konfektionsfabrik (Supplier):</label>
                        <select id="supplier_id"></select>
                    </div>
                    <label>Batch-nummer:</label>
                    <input type="text" id="batch_number" placeholder="BATCH-2024-001">
                    <label>PO-nummer:</label>
                    <input type="text" id="po_number" placeholder="PO-12345">
                    <label>Produktionsdatum:</label>
                    <input type="date" id="production_date">
                    <label>Antal:</label>
                    <input type="number" id="quantity" placeholder="100">
                    <label>Status:</label>
                    <select id="status">
                        <option value="planned">Planned</option>
                        <option value="in_production">In Production</option>
                        <option value="completed">Completed</option>
                    </select>
                    <button class="btn-post" onclick="createBatch()">Skapa</button>
                </div>
            </div>


            <div class="section" id="sec-materials">
                <div class="section-header" onclick="toggle('sec-materials')"><h2>Batch Materials</h2></div>
                <div class="section-content">
                    <label>Factory Material:</label>
                    <select id="factory_material_id"></select>
                    <label>Komponent:</label>
                    <select id="component">
                        <option value="body_fabric">Body fabric</option>
                        <option value="lining">Lining</option>
                        <option value="trim">Trim</option>
                        <option value="padding">Padding</option>
                        <option value="other">Other</option>
                    </select>
                    <button class="btn-get" onclick="api('GET', '/api/batches/' + getBatchId() + '/materials')">Lista materials</button>
                    <button class="btn-post" onclick="addMaterial()">Lägg till material</button>
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

        function getBatchId() {
            const val = document.getElementById('batch_id_select').value;
            if (!val) {
                document.getElementById('response').textContent = 'Välj en batch först!';
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
            if (endpoint.includes('/null') || endpoint.endsWith('/')) {
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
                return json;
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

            try {
                const res = await fetch('/api/brands/all', {
                    headers: { 'X-API-Key': apiKey }
                });
                const json = await res.json();
                const select = document.getElementById('brand_id');
                select.innerHTML = '<option value="">-- Välj brand --</option>';
                if (json.data) {
                    json.data.forEach(b => {
                        const selected = (tenantType === 'brand' && b.id == tenantId) ? ' selected' : '';
                        select.innerHTML += `<option value="${b.id}"${selected}>${b.brand_name}</option>`;
                    });
                    // Hide brand dropdown if tenant is a brand (only one option)
                    if (tenantType === 'brand' && tenantId) {
                        document.getElementById('brand_wrapper').style.display = 'none';
                        loadProducts();
                    }
                }
            } catch (e) {
                console.error('Failed to load brands:', e);
            }
        }

        async function loadProducts() {
            const apiKey = getApiKey();
            if (!apiKey) return;

            const brandId = document.getElementById('brand_id').value;
            const select = document.getElementById('product_id');
            select.innerHTML = '<option value="">-- Välj produkt --</option>';
            document.getElementById('batch_id_select').innerHTML = '<option value="">-- Välj batch --</option>';

            if (brandId) {
                try {
                    const res = await fetch('/api/brands/' + brandId + '/products', {
                        headers: { 'X-API-Key': apiKey }
                    });
                    const json = await res.json();
                    if (json.data) {
                        json.data.forEach(p => {
                            select.innerHTML += `<option value="${p.id}">${p.product_name}</option>`;
                        });
                    }
                } catch (e) {
                    console.error('Failed to load products:', e);
                }
            }
        }

        async function loadBatches() {
            const apiKey = getApiKey();
            if (!apiKey) return;

            const productId = document.getElementById('product_id').value;
            const select = document.getElementById('batch_id_select');
            select.innerHTML = '<option value="">-- Välj batch --</option>';

            if (productId) {
                try {
                    const res = await fetch('/api/products/' + productId + '/batches', {
                        headers: { 'X-API-Key': apiKey }
                    });
                    const json = await res.json();
                    if (json.data) {
                        json.data.forEach(b => {
                            select.innerHTML += `<option value="${b.id}">${b.batch_number} (${b.supplier_name || 'no supplier'})</option>`;
                        });
                    }
                } catch (e) {
                    console.error('Failed to load batches:', e);
                }
            }
        }

        async function fetchBatches() {
            const productId = getProductId();
            if (!productId) return;
            await api('GET', '/api/products/' + productId + '/batches');
            loadBatches();
        }

        async function loadSuppliers() {
            const apiKey = getApiKey();
            if (!apiKey) return;

            const tenantType = localStorage.getItem('dpp_tenant_type');
            const tenantId = localStorage.getItem('dpp_tenant_id');

            try {
                const res = await fetch('/api/suppliers', {
                    headers: { 'X-API-Key': apiKey }
                });
                const json = await res.json();
                const select = document.getElementById('supplier_id');
                select.innerHTML = '<option value="">-- Välj supplier --</option>';
                if (json.data) {
                    json.data.forEach(s => {
                        const selected = (tenantType === 'supplier' && s.id == tenantId) ? ' selected' : '';
                        select.innerHTML += `<option value="${s.id}"${selected}>${s.supplier_name}</option>`;
                    });
                    // Hide supplier dropdown if tenant is a supplier (only one option)
                    if (tenantType === 'supplier' && tenantId) {
                        document.getElementById('supplier_wrapper').style.display = 'none';
                    }
                }
            } catch (e) {
                console.error('Failed to load suppliers:', e);
            }
        }

        async function loadFactoryMaterials() {
            const apiKey = getApiKey();
            if (!apiKey) return;

            try {
                const res = await fetch('/api/materials', {
                    headers: { 'X-API-Key': apiKey }
                });
                const json = await res.json();
                const select = document.getElementById('factory_material_id');
                select.innerHTML = '<option value="">-- Välj material --</option>';
                if (json.data) {
                    json.data.forEach(m => {
                        select.innerHTML += `<option value="${m.id}">${m.material_name}</option>`;
                    });
                }
            } catch (e) {
                console.error('Failed to load materials:', e);
            }
        }

        function loadAll() {
            loadBrands();
            loadSuppliers();
            loadFactoryMaterials();
            document.getElementById('product_id').innerHTML = '<option value="">-- Välj produkt --</option>';
            document.getElementById('batch_id_select').innerHTML = '<option value="">-- Välj batch --</option>';
        }

        document.getElementById('brand_id').addEventListener('change', loadProducts);
        document.getElementById('product_id').addEventListener('change', loadBatches);

        showTenantBanner();
        loadAll();

        async function createBatch() {
            const productId = getProductId();
            if (!productId) return;

            const supplierId = document.getElementById('supplier_id').value;
            if (!supplierId) {
                document.getElementById('response').textContent = 'Välj en supplier först!';
                document.getElementById('response').className = 'response-section error';
                return;
            }

            await api('POST', '/api/products/' + productId + '/batches', {
                supplier_id: parseInt(supplierId),
                batch_number: document.getElementById('batch_number').value,
                po_number: document.getElementById('po_number').value || null,
                production_date: document.getElementById('production_date').value || null,
                quantity: parseInt(document.getElementById('quantity').value) || null,
                status: document.getElementById('status').value
            });
            loadBatches();
        }

        async function deleteBatch() {
            const batchId = getBatchId();
            if (!batchId) return;
            await api('DELETE', '/api/batches/' + batchId);
            loadBatches();
        }

        async function addMaterial() {
            const batchId = getBatchId();
            if (!batchId) return;

            const materialId = document.getElementById('factory_material_id').value;
            if (!materialId) {
                document.getElementById('response').textContent = 'Välj ett material först!';
                document.getElementById('response').className = 'response-section error';
                return;
            }

            await api('POST', '/api/batches/' + batchId + '/materials', {
                factory_material_id: parseInt(materialId),
                component: document.getElementById('component').value
            });
        }
    </script>
</body>
</html>
