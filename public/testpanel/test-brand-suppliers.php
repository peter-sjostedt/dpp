<?php
require_once __DIR__ . '/../../src/Config/Auth.php';
use App\Config\Auth;
Auth::requireLogin();
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Brand-Supplier Relations - DPP Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f0f0f0; }
        .container { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; max-width: 1400px; margin: 0 auto; }
        .panel { background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { margin-top: 0; color: #333; }
        h2 { color: #00838F; border-bottom: 2px solid #00838F; padding-bottom: 8px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { padding: 10px 20px; background: #00838F; color: white; border: none; border-radius: 4px; cursor: pointer; margin-right: 5px; margin-bottom: 5px; }
        button:hover { background: #006064; }
        button.danger { background: #C62828; }
        button.danger:hover { background: #8E0000; }
        button.warning { background: #E65100; }
        button.warning:hover { background: #BF360C; }
        button.success { background: #2E7D32; }
        button.success:hover { background: #1B5E20; }
        pre { background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 4px; overflow: auto; max-height: 600px; font-size: 12px; }
        .collapsible { cursor: pointer; padding: 10px; background: #e0e0e0; border: none; text-align: left; width: 100%; border-radius: 4px; margin-bottom: 5px; }
        .collapsible.active, .collapsible:hover { background: #d0d0d0; }
        .content { padding: 10px 0; display: none; }
        .content.show { display: block; }
        .tenant-banner { background: #00838F; color: white; padding: 10px 20px; margin: -20px -20px 20px -20px; border-radius: 8px 8px 0 0; }
        .tenant-banner.brand { background: #7B1FA2; }
        .tenant-banner.supplier { background: #1565C0; }
        .back-link { margin-bottom: 20px; }
        .back-link a { color: #00838F; text-decoration: none; }
        .back-link a:hover { text-decoration: underline; }
        .relation-card { border: 1px solid #ddd; border-radius: 4px; padding: 10px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; }
        .relation-card.inactive { opacity: 0.6; background: #f9f9f9; }
        .relation-info { flex: 1; }
        .relation-name { font-weight: bold; }
        .relation-status { font-size: 12px; color: #666; }
        .relation-actions { display: flex; gap: 5px; }
        .relation-actions button { margin: 0; padding: 5px 10px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="back-link">
        <a href="test.php">← Tillbaka till testpanelen</a>
    </div>

    <div class="container">
        <div class="panel">
            <div class="tenant-banner" id="tenant-banner">
                <strong id="tenant-type-label">Tenant:</strong> <span id="tenant-name">Inte vald</span>
            </div>
            <h1>Brand-Supplier Relations</h1>

            <!-- Current Relations -->
            <h2>Aktuella relationer</h2>
            <div id="relations-list">
                <p>Klicka på "Ladda relationer" för att visa</p>
            </div>
            <button onclick="loadRelations()">Ladda relationer</button>

            <!-- Add New Relation (Brand only) -->
            <div id="add-relation-section" style="display: none;">
                <h2>Lägg till ny relation</h2>
                <div class="form-group">
                    <label>Tillgängliga leverantörer:</label>
                    <select id="available-suppliers">
                        <option value="">-- Välj leverantör --</option>
                    </select>
                </div>
                <button class="success" onclick="addRelation()">Lägg till relation</button>
                <button onclick="loadAvailable()">Uppdatera tillgängliga</button>
            </div>

            <!-- Collapsible sections -->
            <button class="collapsible">📋 API Endpoints</button>
            <div class="content">
                <p><strong>GET</strong> /api/brand-suppliers - Lista relationer</p>
                <p><strong>GET</strong> /api/brand-suppliers/available - Tillgängliga leverantörer (brand)</p>
                <p><strong>POST</strong> /api/brand-suppliers - Skapa relation (brand)</p>
                <p><strong>PUT</strong> /api/brand-suppliers/{id}/activate - Aktivera (brand)</p>
                <p><strong>PUT</strong> /api/brand-suppliers/{id}/deactivate - Inaktivera (brand)</p>
                <p><strong>DELETE</strong> /api/brand-suppliers/{id} - Ta bort (brand)</p>
            </div>
        </div>

        <div class="panel">
            <h2>API Response</h2>
            <pre id="response">// Resultat visas här</pre>
        </div>
    </div>

    <script>
        // Get API key from localStorage
        function getApiKey() {
            return localStorage.getItem('dpp_api_key') || '';
        }

        function getTenantType() {
            return localStorage.getItem('dpp_tenant_type') || 'brand';
        }

        function getTenantName() {
            return localStorage.getItem('dpp_tenant_name') || 'Inte vald';
        }

        // API call helper
        async function api(method, endpoint, data = null) {
            const apiKey = getApiKey();
            if (!apiKey) {
                showResponse({ error: 'Ingen API-nyckel. Gå till test.php och välj tenant.' });
                return null;
            }

            const options = {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-API-Key': apiKey
                }
            };

            if (data && (method === 'POST' || method === 'PUT')) {
                options.body = JSON.stringify(data);
            }

            try {
                const res = await fetch(endpoint, options);
                const json = await res.json();
                showResponse(json);
                return json;
            } catch (err) {
                showResponse({ error: err.message });
                return null;
            }
        }

        function showResponse(data) {
            document.getElementById('response').textContent = JSON.stringify(data, null, 2);
        }

        // Load relations
        async function loadRelations() {
            const json = await api('GET', '/api/brand-suppliers');
            if (json && json.data) {
                renderRelations(json.data);
            }
        }

        function renderRelations(relations) {
            const container = document.getElementById('relations-list');
            const tenantType = getTenantType();

            if (!relations || relations.length === 0) {
                container.innerHTML = '<p>Inga relationer hittades</p>';
                return;
            }

            let html = '';
            relations.forEach(rel => {
                const isActive = rel._is_active === '1' || rel._is_active === true;
                const name = tenantType === 'brand' ? rel.supplier_name : rel.brand_name;

                html += `
                <div class="relation-card ${isActive ? '' : 'inactive'}">
                    <div class="relation-info">
                        <div class="relation-name">${name}</div>
                        <div class="relation-status">ID: ${rel.id} | ${isActive ? 'Aktiv' : 'Inaktiv'} | Skapad: ${rel.created_at}</div>
                    </div>
                    ${tenantType === 'brand' ? `
                    <div class="relation-actions">
                        ${isActive ?
                            `<button class="warning" onclick="deactivate(${rel.id})">Inaktivera</button>` :
                            `<button class="success" onclick="activate(${rel.id})">Aktivera</button>`
                        }
                        <button class="danger" onclick="deleteRelation(${rel.id})">Ta bort</button>
                    </div>
                    ` : ''}
                </div>
                `;
            });

            container.innerHTML = html;
        }

        // Load available suppliers (Brand only)
        async function loadAvailable() {
            const json = await api('GET', '/api/brand-suppliers/available');
            if (json && json.data) {
                const select = document.getElementById('available-suppliers');
                select.innerHTML = '<option value="">-- Välj leverantör --</option>';
                json.data.forEach(s => {
                    select.innerHTML += `<option value="${s.id}">${s.supplier_name} (${s.supplier_location || 'Ingen plats'})</option>`;
                });
            }
        }

        // Add relation
        async function addRelation() {
            const supplierId = document.getElementById('available-suppliers').value;
            if (!supplierId) {
                alert('Välj en leverantör');
                return;
            }

            const json = await api('POST', '/api/brand-suppliers', { supplier_id: parseInt(supplierId) });
            if (json && json.success) {
                loadRelations();
                loadAvailable();
            }
        }

        // Activate relation
        async function activate(id) {
            const json = await api('PUT', `/api/brand-suppliers/${id}/activate`);
            if (json && json.success) {
                loadRelations();
            }
        }

        // Deactivate relation
        async function deactivate(id) {
            const json = await api('PUT', `/api/brand-suppliers/${id}/deactivate`);
            if (json && json.success) {
                loadRelations();
            }
        }

        // Delete relation
        async function deleteRelation(id) {
            if (!confirm('Vill du verkligen ta bort denna relation permanent?')) {
                return;
            }
            const json = await api('DELETE', `/api/brand-suppliers/${id}`);
            if (json && json.success) {
                loadRelations();
            }
        }

        // Collapsible sections
        document.querySelectorAll('.collapsible').forEach(btn => {
            btn.addEventListener('click', function() {
                this.classList.toggle('active');
                const content = this.nextElementSibling;
                content.classList.toggle('show');
            });
        });

        // Initialize
        window.onload = function() {
            const tenantType = getTenantType();
            const tenantName = getTenantName();

            document.getElementById('tenant-name').textContent = tenantName;
            document.getElementById('tenant-type-label').textContent = tenantType === 'brand' ? 'Varumärke:' : 'Fabrik:';

            const banner = document.getElementById('tenant-banner');
            banner.classList.remove('brand', 'supplier');
            banner.classList.add(tenantType);

            // Show add relation section only for brands
            if (tenantType === 'brand') {
                document.getElementById('add-relation-section').style.display = 'block';
                loadAvailable();
            }

            loadRelations();
        };
    </script>
</body>
</html>
