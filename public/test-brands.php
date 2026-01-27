<?php
require_once __DIR__ . '/../src/Config/Auth.php';
use App\Config\Auth;
Auth::requireLogin();
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <title>Brands - DPP Test</title>
    <style>
        body { font-family: Arial; margin: 0; padding: 20px; background: #f5f5f5; }
        .header { background: #1a237e; color: white; padding: 20px; margin: -20px -20px 20px; }
        .header a { color: white; }
        .header.brand { background: #7B1FA2; }
        .header.supplier { background: #1565C0; }
        .container { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-section { background: white; padding: 20px; border-radius: 8px; max-height: 85vh; overflow-y: auto; }
        .response-section { background: #263238; color: #4CAF50; padding: 20px; border-radius: 8px; font-family: monospace; white-space: pre-wrap; min-height: 400px; overflow: auto; }
        input, select { width: 100%; padding: 10px; margin: 5px 0 15px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        input:disabled { background: #f5f5f5; color: #666; }
        label { font-weight: bold; color: #333; display: block; margin-bottom: 5px; }
        label span { font-weight: normal; color: #666; font-size: 12px; margin-left: 8px; }
        button { padding: 12px 24px; margin: 5px 5px 5px 0; border: none; border-radius: 4px; cursor: pointer; color: white; }
        .btn-put { background: #FF9800; }
        .btn-put:disabled { background: #ccc; cursor: not-allowed; }
        .error { color: #f44336; }
        .readonly-notice { background: #fff3e0; border: 1px solid #ff9800; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 13px; }
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
    <div class="header" id="header">
        <a href="test.php">&larr; Tillbaka</a>
        <a href="docs/dataflow.html" style="float: right;">Dataflöde &rarr;</a>
        <h1>Brands</h1>
        <p style="margin: 5px 0 0; opacity: 0.8; font-size: 14px;">Registrerade varumärken</p>
        <div id="tenant_banner" style="margin-top: 10px; padding: 8px 15px; background: rgba(255,255,255,0.2); border-radius: 4px; display: inline-block; font-size: 13px;"></div>
    </div>

    <div class="container">
        <div class="form-section">
            <div id="readonly-notice" class="readonly-notice" style="display: none;">
                Du är inloggad som <strong>Supplier</strong> - endast läsåtkomst till relaterade brands.
            </div>

            <select id="brand_id_select" onchange="onBrandSelect()" style="display: none;"></select>

            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">
                <label>Varumärke <span>Det registrerade varumärkesnamnet</span></label>
                <input type="text" id="brand_name">

                <label>Sub-brand <span>Undervarumärke eller produktlinje (valfritt)</span></label>
                <input type="text" id="sub_brand">

                <label>Parent Company <span>Moderbolag som äger varumärket (valfritt)</span></label>
                <input type="text" id="parent_company">

                <label>Trader <span>Juridiskt namn på den ekonomiska aktören</span></label>
                <input type="text" id="trader">

                <label>Trader Location <span>Fullständig adress till den ekonomiska aktören</span></label>
                <input type="text" id="trader_location">

                <label>Logo URL <span>URL till varumärkets logotyp</span></label>
                <input type="text" id="logo_url">

                <label>LEI <span>ISO 17442, exakt 20 tecken (A-Z, 0-9)</span></label>
                <input type="text" id="lei" maxlength="20" style="text-transform: uppercase;">

                <label>GS1 Company Prefix <span>6-12 siffror</span></label>
                <input type="text" id="gs1_company_prefix" maxlength="12">

                <div id="update-buttons">
                    <button class="btn-put" onclick="update()">Uppdatera</button>
                </div>
            </div>

            <div class="section" id="sec-info" style="margin-top: 20px;">
                <div class="section-header" onclick="toggle('sec-info')"><h2>API Information</h2></div>
                <div class="section-content">
                    <p><strong>Som Brand:</strong></p>
                    <ul>
                        <li>GET /api/brands - Ditt eget brand (med items-filter)</li>
                        <li>GET /api/brands/all - Ditt eget brand (för dropdowns)</li>
                        <li>GET /api/brands/{id} - Hämta ditt brand</li>
                        <li>PUT /api/brands/{id} - Uppdatera ditt brand</li>
                    </ul>
                    <p><strong>Som Supplier:</strong></p>
                    <ul>
                        <li>GET /api/brands - Relaterade brands (via brand_suppliers)</li>
                        <li>GET /api/brands/{id} - Hämta relaterat brand (läsåtkomst)</li>
                    </ul>
                    <p><em>Obs: Brands kan inte skapas eller tas bort via API.</em></p>
                </div>
            </div>
        </div>

        <div class="response-section" id="response">Laddar...</div>
    </div>

    <script>
        function toggle(id) {
            document.getElementById(id).classList.toggle('open');
        }

        function getApiKey() {
            const apiKey = localStorage.getItem('dpp_api_key');
            if (!apiKey) {
                document.getElementById('response').textContent = 'Ingen API-nyckel vald. Gå till huvudsidan och välj tenant.';
                document.getElementById('response').className = 'response-section error';
                return null;
            }
            return apiKey;
        }

        function getTenantType() {
            return localStorage.getItem('dpp_tenant_type') || 'brand';
        }

        function getTenantName() {
            return localStorage.getItem('dpp_tenant_name') || 'Inte vald';
        }

        function isReadonly() {
            return getTenantType() === 'supplier';
        }

        function showTenantBanner() {
            const tenantName = getTenantName();
            const tenantType = getTenantType();
            const banner = document.getElementById('tenant_banner');
            const header = document.getElementById('header');

            header.classList.remove('brand', 'supplier');
            header.classList.add(tenantType);

            if (tenantName && tenantName !== 'Inte vald') {
                const typeLabel = tenantType === 'brand' ? 'Varumärke' : 'Fabrik';
                banner.textContent = `Testar som ${typeLabel}: ${tenantName}`;
                banner.style.display = 'inline-block';
                banner.style.background = 'rgba(255,255,255,0.2)';
            } else {
                banner.textContent = 'Ingen tenant vald - välj på huvudsidan';
                banner.style.background = 'rgba(255,0,0,0.3)';
            }

            const readonly = isReadonly();
            document.getElementById('readonly-notice').style.display = readonly ? 'block' : 'none';
            document.getElementById('update-buttons').style.display = readonly ? 'none' : 'block';

            const inputs = document.querySelectorAll('input[type="text"]');
            inputs.forEach(input => input.disabled = readonly);
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

            try {
                const res = await fetch('/api/brands/all', {
                    headers: { 'X-API-Key': apiKey }
                });
                const json = await res.json();
                document.getElementById('response').textContent = JSON.stringify(json, null, 2);
                document.getElementById('response').className = 'response-section' + (json.error ? ' error' : '');

                const select = document.getElementById('brand_id_select');
                select.innerHTML = '';
                if (json.data && json.data.length > 0) {
                    json.data.forEach(b => {
                        select.innerHTML += `<option value="${b.id}">${b.id}: ${b.brand_name}</option>`;
                    });
                    // Visa dropdown bara om det finns flera alternativ
                    select.style.display = json.data.length > 1 ? 'block' : 'none';
                    await loadBrandData(json.data[0].id);
                } else {
                    select.style.display = 'none';
                    clearForm();
                }
            } catch (e) {
                console.error('Error loading brands:', e);
                document.getElementById('response').textContent = 'Error: ' + e.message;
                document.getElementById('response').className = 'response-section error';
            }
        }

        async function onBrandSelect() {
            const id = document.getElementById('brand_id_select').value;
            if (id) {
                await loadBrandData(id);
            }
        }

        async function loadBrandData(id) {
            const json = await api('GET', '/api/brands/' + id);
            if (json && json.data) {
                const b = json.data;
                document.getElementById('brand_name').value = b.brand_name || '';
                document.getElementById('sub_brand').value = b.sub_brand || '';
                document.getElementById('parent_company').value = b.parent_company || '';
                document.getElementById('trader').value = b.trader || '';
                document.getElementById('trader_location').value = b.trader_location || '';
                document.getElementById('logo_url').value = b.logo_url || '';
                document.getElementById('lei').value = b.lei || '';
                document.getElementById('gs1_company_prefix').value = b.gs1_company_prefix || '';
            }
        }

        function clearForm() {
            document.getElementById('brand_name').value = '';
            document.getElementById('sub_brand').value = '';
            document.getElementById('parent_company').value = '';
            document.getElementById('trader').value = '';
            document.getElementById('trader_location').value = '';
            document.getElementById('logo_url').value = '';
            document.getElementById('lei').value = '';
            document.getElementById('gs1_company_prefix').value = '';
        }

        function update() {
            const id = document.getElementById('brand_id_select').value;
            if (!id) return;

            const lei = document.getElementById('lei').value.toUpperCase().trim();
            if (lei && !/^[A-Z0-9]{20}$/.test(lei)) {
                document.getElementById('response').textContent = 'Felaktigt LEI-format. Måste vara exakt 20 tecken (A-Z, 0-9).';
                document.getElementById('response').className = 'response-section error';
                return;
            }

            const gs1 = document.getElementById('gs1_company_prefix').value.trim();
            if (gs1 && !/^[0-9]{6,12}$/.test(gs1)) {
                document.getElementById('response').textContent = 'Felaktigt GS1 Company Prefix. Måste vara 6-12 siffror.';
                document.getElementById('response').className = 'response-section error';
                return;
            }

            api('PUT', '/api/brands/' + id, {
                brand_name: document.getElementById('brand_name').value || null,
                sub_brand: document.getElementById('sub_brand').value || null,
                parent_company: document.getElementById('parent_company').value || null,
                trader: document.getElementById('trader').value || null,
                trader_location: document.getElementById('trader_location').value || null,
                logo_url: document.getElementById('logo_url').value || null,
                lei: lei || null,
                gs1_company_prefix: gs1 || null
            });
        }

        showTenantBanner();
        loadBrands();
    </script>
</body>
</html>
