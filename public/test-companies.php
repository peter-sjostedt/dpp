<?php
require_once __DIR__ . '/../src/Config/Auth.php';
use App\Config\Auth;
Auth::requireLogin();
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <title>Companies - DPP Test</title>
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
        <h1>Companies</h1>
        <p style="margin: 5px 0 0; opacity: 0.8; font-size: 14px;">♻️ Registrera en gång</p>
        <div id="company_banner" style="margin-top: 10px; padding: 8px 15px; background: rgba(255,255,255,0.2); border-radius: 4px; display: inline-block; font-size: 13px;"></div>
    </div>

    <div class="container">
        <div class="form-section">
            <button class="btn-refresh" onclick="loadCompanies()">Ladda om data</button>
            <button class="btn-get" onclick="api('GET', '/api/companies')" style="margin-left:10px">Hämta alla</button>

            <div class="section" id="sec-create">
                <div class="section-header" onclick="toggle('sec-create')"><h2>Skapa ny</h2></div>
                <div class="section-content">
                    <label>Namn:</label>
                    <input type="text" id="name" placeholder="Företagsnamn">
                    <label>Org.nummer:</label>
                    <input type="text" id="org_number" placeholder="556123-4567">
                    <button class="btn-post" onclick="create()">Skapa</button>
                </div>
            </div>

            <div class="section" id="sec-get">
                <div class="section-header" onclick="toggle('sec-get')"><h2>Hämta/Uppdatera/Ta bort</h2></div>
                <div class="section-content">
                    <label>Company:</label>
                    <select id="company_id_select"></select>
                    <button class="btn-get" onclick="api('GET', '/api/companies/' + getCompanyId())">Hämta</button>
                    <button class="btn-put" onclick="update()">Uppdatera</button>
                    <button class="btn-delete" onclick="api('DELETE', '/api/companies/' + getCompanyId())">Ta bort</button>
                </div>
            </div>
        </div>

        <div class="response-section" id="response">Klicka på en knapp för att se resultat...</div>
    </div>

    <script>
        function toggle(id) {
            document.getElementById(id).classList.toggle('open');
        }

        function getCompanyId() {
            return document.getElementById('company_id_select').value;
        }

        function getApiKey() {
            const apiKey = localStorage.getItem('dpp_api_key');
            if (!apiKey) {
                document.getElementById('response').textContent = 'Välj ett företag på huvudsidan först!';
                document.getElementById('response').className = 'response-section error';
                return null;
            }
            return apiKey;
        }

        function showCompanyBanner() {
            const companyName = localStorage.getItem('dpp_company_name');
            const banner = document.getElementById('company_banner');
            if (companyName) {
                banner.textContent = 'Testar som: ' + companyName;
                banner.style.display = 'inline-block';
            } else {
                banner.textContent = 'Inget företag valt - välj på huvudsidan';
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

        async function loadCompanies() {
            const apiKey = getApiKey();
            if (!apiKey) return;

            const res = await fetch('/api/companies', {
                headers: { 'X-API-Key': apiKey }
            });
            const json = await res.json();
            const select = document.getElementById('company_id_select');
            select.innerHTML = '<option value="">-- Välj company --</option>';
            if (json.data) {
                json.data.forEach(c => {
                    select.innerHTML += `<option value="${c.id}">${c.id}: ${c.name}</option>`;
                });
            }
        }

        showCompanyBanner();
        loadCompanies();

        function create() {
            api('POST', '/api/companies', {
                name: document.getElementById('name').value,
                org_number: document.getElementById('org_number').value
            }).then(() => loadCompanies());
        }

        function update() {
            api('PUT', '/api/companies/' + document.getElementById('company_id_select').value, {
                name: document.getElementById('name').value,
                org_number: document.getElementById('org_number').value
            }).then(() => loadCompanies());
        }
    </script>
</body>
</html>
