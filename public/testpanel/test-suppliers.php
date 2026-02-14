<?php
require_once __DIR__ . '/../../src/Config/Auth.php';
use App\Config\Auth;
Auth::requireLogin();
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <title>Suppliers - DPP Test</title>
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
        <h1>Suppliers</h1>
        <p style="margin: 5px 0 0; opacity: 0.8; font-size: 14px;">Registrerade leverantörer/fabriker</p>
        <div id="tenant_banner" style="margin-top: 10px; padding: 8px 15px; background: rgba(255,255,255,0.2); border-radius: 4px; display: inline-block; font-size: 13px;"></div>
    </div>

    <div class="container">
        <div class="form-section">
            <div id="readonly-notice" class="readonly-notice" style="display: none;">
                Du är inloggad som <strong>Brand</strong> - endast läsåtkomst till relaterade suppliers.
            </div>

            <select id="supplier_id_select" onchange="onSupplierSelect()" style="display: none;"></select>

            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">
                <label>Leverantörsnamn <span>Fabrikens officiella namn</span></label>
                <input type="text" id="supplier_name">

                <label>Plats/Land <span>Adress för produktionsanläggningen</span></label>
                <input type="text" id="supplier_location">

                <label>Facility Registry <span>GLN, OSH eller OTHER</span></label>
                <input type="text" id="facility_registry">

                <label>Facility Identifier <span>Unik identifierare i valt register</span></label>
                <input type="text" id="facility_identifier">

                <label>Operator Registry <span>GLN, EORI, VAT eller OTHER</span></label>
                <input type="text" id="operator_registry">

                <label>Operator Identifier <span>Unik identifierare i valt register</span></label>
                <input type="text" id="operator_identifier">

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
                    <p><strong>Som Supplier:</strong></p>
                    <ul>
                        <li>GET /api/suppliers - Din egen supplier</li>
                        <li>GET /api/suppliers/{id} - Hämta din supplier</li>
                        <li>PUT /api/suppliers/{id} - Uppdatera din supplier</li>
                    </ul>
                    <p><strong>Som Brand:</strong></p>
                    <ul>
                        <li>GET /api/suppliers - Relaterade suppliers (via brand_suppliers)</li>
                        <li>GET /api/suppliers/{id} - Hämta relaterad supplier (läsåtkomst)</li>
                    </ul>
                    <p><em>Obs: Suppliers kan inte skapas eller tas bort via API.</em></p>
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
            return getTenantType() === 'brand';
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

        async function loadSuppliers() {
            const apiKey = getApiKey();
            if (!apiKey) return;

            try {
                const res = await fetch('/api/suppliers', {
                    headers: { 'X-API-Key': apiKey }
                });
                const json = await res.json();
                document.getElementById('response').textContent = JSON.stringify(json, null, 2);
                document.getElementById('response').className = 'response-section' + (json.error ? ' error' : '');

                const select = document.getElementById('supplier_id_select');
                select.innerHTML = '';
                if (json.data && json.data.length > 0) {
                    json.data.forEach(s => {
                        select.innerHTML += `<option value="${s.id}">${s.id}: ${s.supplier_name}</option>`;
                    });
                    // Visa dropdown bara om det finns flera alternativ
                    select.style.display = json.data.length > 1 ? 'block' : 'none';
                    await loadSupplierData(json.data[0].id);
                } else {
                    select.style.display = 'none';
                    clearForm();
                }
            } catch (e) {
                console.error('Error loading suppliers:', e);
                document.getElementById('response').textContent = 'Error: ' + e.message;
                document.getElementById('response').className = 'response-section error';
            }
        }

        async function onSupplierSelect() {
            const id = document.getElementById('supplier_id_select').value;
            if (id) {
                await loadSupplierData(id);
            }
        }

        async function loadSupplierData(id) {
            const json = await api('GET', '/api/suppliers/' + id);
            if (json && json.data) {
                const s = json.data;
                document.getElementById('supplier_name').value = s.supplier_name || '';
                document.getElementById('supplier_location').value = s.supplier_location || '';
                document.getElementById('facility_registry').value = s.facility_registry || '';
                document.getElementById('facility_identifier').value = s.facility_identifier || '';
                document.getElementById('operator_registry').value = s.operator_registry || '';
                document.getElementById('operator_identifier').value = s.operator_identifier || '';
                document.getElementById('lei').value = s.lei || '';
                document.getElementById('gs1_company_prefix').value = s.gs1_company_prefix || '';
            }
        }

        function clearForm() {
            document.getElementById('supplier_name').value = '';
            document.getElementById('supplier_location').value = '';
            document.getElementById('facility_registry').value = '';
            document.getElementById('facility_identifier').value = '';
            document.getElementById('operator_registry').value = '';
            document.getElementById('operator_identifier').value = '';
            document.getElementById('lei').value = '';
            document.getElementById('gs1_company_prefix').value = '';
        }

        function update() {
            const id = document.getElementById('supplier_id_select').value;
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

            api('PUT', '/api/suppliers/' + id, {
                supplier_name: document.getElementById('supplier_name').value || null,
                supplier_location: document.getElementById('supplier_location').value || null,
                facility_registry: document.getElementById('facility_registry').value || null,
                facility_identifier: document.getElementById('facility_identifier').value || null,
                operator_registry: document.getElementById('operator_registry').value || null,
                operator_identifier: document.getElementById('operator_identifier').value || null,
                lei: lei || null,
                gs1_company_prefix: gs1 || null
            });
        }

        showTenantBanner();
        loadSuppliers();
    </script>
</body>
</html>
