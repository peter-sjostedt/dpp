<?php // Suppliers Test ?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <title>Suppliers - DPP Test</title>
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
        <h1>Suppliers</h1>
    </div>

    <div class="container">
        <div class="form-section">
            <button class="btn-refresh" onclick="loadAll()">Ladda om data</button>
            <button class="btn-get" onclick="api('GET', '/api/suppliers')" style="margin-left:10px">Hämta alla</button>

            <div class="section" id="sec-create">
                <div class="section-header" onclick="toggle('sec-create')"><h2>Skapa ny</h2></div>
                <div class="section-content">
                    <label>Company:</label>
                    <select id="company_id"></select>
                    <label>Leverantörsnamn:</label>
                    <input type="text" id="supplier_name" placeholder="Leverantörsnamn">
                    <label>Plats/Land:</label>
                    <input type="text" id="supplier_location" placeholder="t.ex. Sweden">
                    <label>Facility Registry:</label>
                    <input type="text" id="facility_registry" placeholder="https://...">
                    <label>Facility Identifier:</label>
                    <input type="text" id="facility_identifier" placeholder="FAC-12345">
                    <label>Operator Registry:</label>
                    <input type="text" id="operator_registry" placeholder="https://...">
                    <label>Operator Identifier:</label>
                    <input type="text" id="operator_identifier" placeholder="OP-12345">
                    <button class="btn-post" onclick="create()">Skapa</button>
                </div>
            </div>

            <div class="section" id="sec-get">
                <div class="section-header" onclick="toggle('sec-get')"><h2>Hämta/Uppdatera/Ta bort</h2></div>
                <div class="section-content">
                    <label>Supplier:</label>
                    <select id="supplier_id_select"></select>
                    <button class="btn-get" onclick="api('GET', '/api/suppliers/' + getSupplierId())">Hämta</button>
                    <button class="btn-put" onclick="update()">Uppdatera</button>
                    <button class="btn-delete" onclick="api('DELETE', '/api/suppliers/' + getSupplierId())">Ta bort</button>
                </div>
            </div>
        </div>

        <div class="response-section" id="response">Klicka på en knapp för att se resultat...</div>
    </div>

    <script>
        function toggle(id) {
            document.getElementById(id).classList.toggle('open');
        }

        function getSupplierId() {
            return document.getElementById('supplier_id_select').value;
        }

        async function api(method, endpoint, data = null) {
            const opts = { method, headers: { 'Content-Type': 'application/json' } };
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
            const res = await fetch('/api/companies');
            const json = await res.json();
            const select = document.getElementById('company_id');
            select.innerHTML = '<option value="">-- Välj company --</option>';
            if (json.data) {
                json.data.forEach(c => {
                    select.innerHTML += `<option value="${c.id}">${c.id}: ${c.name}</option>`;
                });
            }
        }

        async function loadSuppliers() {
            const res = await fetch('/api/suppliers');
            const json = await res.json();
            const select = document.getElementById('supplier_id_select');
            select.innerHTML = '<option value="">-- Välj supplier --</option>';
            if (json.data) {
                json.data.forEach(s => {
                    select.innerHTML += `<option value="${s.id}">${s.id}: ${s.supplier_name}</option>`;
                });
            }
        }

        function loadAll() {
            loadCompanies();
            loadSuppliers();
        }

        loadAll();

        function create() {
            api('POST', '/api/suppliers', {
                company_id: parseInt(document.getElementById('company_id').value),
                supplier_name: document.getElementById('supplier_name').value,
                supplier_location: document.getElementById('supplier_location').value || null,
                facility_registry: document.getElementById('facility_registry').value || null,
                facility_identifier: document.getElementById('facility_identifier').value || null,
                operator_registry: document.getElementById('operator_registry').value || null,
                operator_identifier: document.getElementById('operator_identifier').value || null
            }).then(() => loadSuppliers());
        }

        function update() {
            api('PUT', '/api/suppliers/' + document.getElementById('supplier_id_select').value, {
                supplier_name: document.getElementById('supplier_name').value,
                supplier_location: document.getElementById('supplier_location').value || null,
                facility_registry: document.getElementById('facility_registry').value || null,
                facility_identifier: document.getElementById('facility_identifier').value || null,
                operator_registry: document.getElementById('operator_registry').value || null,
                operator_identifier: document.getElementById('operator_identifier').value || null
            }).then(() => loadSuppliers());
        }
    </script>
</body>
</html>
