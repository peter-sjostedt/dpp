<?php
require_once __DIR__ . '/../src/Config/Auth.php';
use App\Config\Auth;
Auth::requireLogin();
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <title>Products - DPP Test</title>
    <style>
        body { font-family: Arial; margin: 0; padding: 20px; background: #f5f5f5; }
        .header { background: #1a237e; color: white; padding: 20px; margin: -20px -20px 20px; }
        .header a { color: white; }
        .container { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-section { background: white; padding: 20px; border-radius: 8px; max-height: 85vh; overflow-y: auto; }
        .response-section { background: #263238; color: #4CAF50; padding: 20px; border-radius: 8px; font-family: monospace; white-space: pre-wrap; min-height: 400px; overflow-y: auto; }
        input, textarea, select { width: 100%; padding: 10px; margin: 5px 0 15px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        textarea { height: 60px; }
        label { font-weight: bold; color: #666; font-size: 13px; }
        button { padding: 10px 16px; margin: 3px 3px 3px 0; border: none; border-radius: 4px; cursor: pointer; color: white; font-size: 12px; }
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
        <h1>Products</h1>
        <p style="margin: 5px 0 0; opacity: 0.8; font-size: 14px;">♻️ Registrera en gång</p>
        <div id="tenant_banner" style="margin-top: 10px; padding: 8px 15px; background: rgba(255,255,255,0.2); border-radius: 4px; display: inline-block; font-size: 13px;"></div>
    </div>

    <div class="container">
        <div class="form-section">
            <button class="btn-refresh" onclick="loadAll()">Ladda om data</button>

            <div class="section" id="sec-list">
                <div class="section-header" onclick="toggle('sec-list')"><h2>Lista per brand</h2></div>
                <div class="section-content">
                    <div id="brand_wrapper">
                        <label>Brand:</label>
                        <select id="brand_id"></select>
                    </div>
                    <button class="btn-get" onclick="api('GET', '/api/brands/' + document.getElementById('brand_id').value + '/products')">Hämta produkter</button>
                </div>
            </div>

            <div class="section" id="sec-create">
                <div class="section-header" onclick="toggle('sec-create')"><h2>Skapa ny</h2></div>
                <div class="section-content">
                    <label>Produktnamn:</label>
                    <input type="text" id="product_name" placeholder="Produktnamn">
                    <label>Kategori:</label>
                    <input type="text" id="product_category" placeholder="t.ex. Clothing">
                    <button class="btn-post" onclick="create()">Skapa</button>
                </div>
            </div>

            <div class="section" id="sec-get">
                <div class="section-header" onclick="toggle('sec-get')"><h2>Hämta/Ta bort</h2></div>
                <div class="section-content">
                    <label>Product:</label>
                    <select id="product_id_select"></select>
                    <button class="btn-get" onclick="api('GET', '/api/products/' + getProductId())">Hämta</button>
                    <button class="btn-delete" onclick="api('DELETE', '/api/products/' + getProductId())">Ta bort</button>
                </div>
            </div>

            <div class="section" id="sec-dpp">
                <div class="section-header" onclick="toggle('sec-dpp')"><h2>DPP Export</h2></div>
                <div class="section-content">
                    <button class="btn-get" onclick="api('GET', '/api/products/' + getProductId() + '/dpp')">Snabbvy (legacy)</button>
                    <button class="btn-get" onclick="api('GET', '/api/products/' + getProductId() + '/dpp/preview')">Preview</button>
                    <button class="btn-get" onclick="api('GET', '/api/products/' + getProductId() + '/dpp/validate')">Validate</button>
                    <button class="btn-get" onclick="api('GET', '/api/products/' + getProductId() + '/dpp/export')">Export</button>
                    <p style="font-size: 12px; color: #666; margin-top: 10px;">
                        <strong>Preview</strong> - Visa all DPP-data<br>
                        <strong>Validate</strong> - Kontrollera fullständighet<br>
                        <strong>Export</strong> - Strukturerat format för DPP-host
                    </p>
                </div>
            </div>

            <div class="section" id="sec-care">
                <div class="section-header" onclick="toggle('sec-care')"><h2>Care Information</h2></div>
                <div class="section-content">
                    <button class="btn-get" onclick="api('GET', '/api/products/' + getProductId() + '/care')">Hämta</button>
                    <button class="btn-put" onclick="updateCare()">Spara</button>
                    <label>Tvättinstruktioner:</label>
                    <input type="text" id="washing" placeholder="Machine wash 30°C">
                </div>
            </div>

            <div class="section" id="sec-cert">
                <div class="section-header" onclick="toggle('sec-cert')"><h2>Certifications</h2></div>
                <div class="section-content">
                    <button class="btn-get" onclick="api('GET', '/api/products/' + getProductId() + '/certifications')">Lista</button>
                    <button class="btn-post" onclick="addCert()">Lägg till</button>
                    <label>Certifiering:</label>
                    <input type="text" id="cert_name" placeholder="GOTS, OEKO-TEX...">
                </div>
            </div>

            <div class="section" id="sec-compliance">
                <div class="section-header" onclick="toggle('sec-compliance')"><h2>Compliance</h2></div>
                <div class="section-content">
                    <button class="btn-get" onclick="api('GET', '/api/products/' + getProductId() + '/compliance')">Hämta</button>
                    <button class="btn-put" onclick="updateCompliance()">Spara</button>
                    <label>REACH-kompatibel:</label>
                    <select id="reach_compliant">
                        <option value="1">Ja</option>
                        <option value="0">Nej</option>
                    </select>
                </div>
            </div>

            <div class="section" id="sec-circularity">
                <div class="section-header" onclick="toggle('sec-circularity')"><h2>Circularity</h2></div>
                <div class="section-content">
                    <button class="btn-get" onclick="api('GET', '/api/products/' + getProductId() + '/circularity')">Hämta</button>
                    <button class="btn-put" onclick="updateCircularity()">Spara</button>
                    <label>Återvunnet innehåll (%):</label>
                    <input type="number" id="recycled_pct" placeholder="0-100">
                </div>
            </div>

            <div class="section" id="sec-sustainability">
                <div class="section-header" onclick="toggle('sec-sustainability')"><h2>Sustainability</h2></div>
                <div class="section-content">
                    <button class="btn-get" onclick="api('GET', '/api/products/' + getProductId() + '/sustainability')">Hämta</button>
                    <button class="btn-put" onclick="updateSustainability()">Spara</button>
                    <label>CO2-avtryck (kg):</label>
                    <input type="number" id="carbon_footprint" placeholder="12.5" step="0.1">
                </div>
            </div>

            <div class="section" id="sec-components">
                <div class="section-header" onclick="toggle('sec-components')"><h2>Components</h2></div>
                <div class="section-content">
                    <button class="btn-get" onclick="api('GET', '/api/products/' + getProductId() + '/components')">Lista</button>
                    <button class="btn-post" onclick="addComponent()">Lägg till</button>
                    <label>Komponenttyp:</label>
                    <select id="component_type">
                        <option value="body_fabric">Body Fabric</option>
                        <option value="lining">Lining</option>
                        <option value="trim">Trim</option>
                        <option value="padding">Padding</option>
                        <option value="other">Other</option>
                    </select>
                    <label>Vikt (kg):</label>
                    <input type="number" id="component_weight" placeholder="0.25" step="0.0001">
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
            const val = document.getElementById('product_id_select').value;
            if (!val) {
                document.getElementById('response').textContent = 'Välj en produkt först!';
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
        }

        async function loadProducts() {
            const apiKey = getApiKey();
            if (!apiKey) return;

            const brandId = document.getElementById('brand_id').value;
            const select = document.getElementById('product_id_select');
            select.innerHTML = '<option value="">-- Välj produkt --</option>';

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

        function loadAll() {
            loadBrands();
            document.getElementById('product_id_select').innerHTML = '<option value="">-- Välj produkt --</option>';
        }

        document.getElementById('brand_id').addEventListener('change', loadProducts);

        showTenantBanner();
        loadAll();

        function create() {
            api('POST', '/api/brands/' + document.getElementById('brand_id').value + '/products', {
                product_name: document.getElementById('product_name').value,
                product_category: document.getElementById('product_category').value
            }).then(() => loadProducts());
        }

        function updateCare() {
            api('PUT', '/api/products/' + getProductId() + '/care', {
                washing_instructions: document.getElementById('washing').value
            });
        }

        function addCert() {
            api('POST', '/api/products/' + getProductId() + '/certifications', {
                certification_name: document.getElementById('cert_name').value
            });
        }

        function updateCompliance() {
            api('PUT', '/api/products/' + getProductId() + '/compliance', {
                reach_compliant: parseInt(document.getElementById('reach_compliant').value)
            });
        }

        function updateCircularity() {
            api('PUT', '/api/products/' + getProductId() + '/circularity', {
                recycled_content_percentage: parseFloat(document.getElementById('recycled_pct').value)
            });
        }

        function updateSustainability() {
            api('PUT', '/api/products/' + getProductId() + '/sustainability', {
                carbon_footprint_kg: parseFloat(document.getElementById('carbon_footprint').value)
            });
        }

        function addComponent() {
            api('POST', '/api/products/' + getProductId() + '/components', {
                component_type: document.getElementById('component_type').value,
                component_weight: parseFloat(document.getElementById('component_weight').value) || null
            });
        }
    </script>
</body>
</html>
