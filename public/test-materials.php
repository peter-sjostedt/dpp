<?php // Materials Test ?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <title>Materials - DPP Test</title>
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
        .subsection { background: #f9f9f9; padding: 15px; border-radius: 4px; margin: 10px 0; }
        h3 { color: #FF9800; margin-top: 15px; }

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
        <h1>Factory Materials</h1>
    </div>

    <div class="container">
        <div class="form-section">
            <button class="btn-refresh" onclick="loadAll()">Ladda om data</button>
            <button class="btn-get" onclick="api('GET', '/api/materials')" style="margin-left:10px">Hämta alla material</button>

            <div class="section" id="sec-supplier">
                <div class="section-header" onclick="toggle('sec-supplier')"><h2>Lista per supplier</h2></div>
                <div class="section-content">
                    <label>Supplier:</label>
                    <select id="supplier_id"></select>
                    <button class="btn-get" onclick="api('GET', '/api/suppliers/' + getSupplierId() + '/materials')">Hämta material</button>
                </div>
            </div>

            <div class="section" id="sec-create">
                <div class="section-header" onclick="toggle('sec-create')"><h2>Skapa material</h2></div>
                <div class="section-content">
                    <label>Materialnamn:</label>
                    <input type="text" id="material_name" placeholder="GOTS Organic Cotton">
                    <label>Typ:</label>
                    <select id="material_type">
                        <option value="textile">Textile</option>
                        <option value="leather">Leather</option>
                        <option value="trim">Trim</option>
                        <option value="other">Other</option>
                    </select>
                    <label>Internal Code:</label>
                    <input type="text" id="internal_code" placeholder="MAT-001">
                    <label>Vikt per meter (g):</label>
                    <input type="number" step="0.0001" id="net_weight_per_meter" placeholder="150">
                    <label>Bredd (cm):</label>
                    <input type="number" id="width_cm" placeholder="150">
                    <button class="btn-post" onclick="createMaterial()">Skapa</button>
                </div>
            </div>

            <div class="section" id="sec-get">
                <div class="section-header" onclick="toggle('sec-get')"><h2>Hämta/Ta bort material</h2></div>
                <div class="section-content">
                    <label>Material:</label>
                    <select id="material_id_select"></select>
                    <button class="btn-get" onclick="api('GET', '/api/materials/' + getMaterialId())">Hämta</button>
                    <button class="btn-delete" onclick="api('DELETE', '/api/materials/' + getMaterialId())">Ta bort</button>
                </div>
            </div>

            <div class="section" id="sec-composition">
                <div class="section-header" onclick="toggle('sec-composition')"><h2>Fibersammansättning (Compositions)</h2></div>
                <div class="section-content">
                    <button class="btn-get" onclick="api('GET', '/api/materials/' + getMaterialId() + '/compositions')">Lista compositions</button>
                    <h3>Lägg till fiber</h3>
                    <label>Fibertyp (t.ex. Cotton, Polyester):</label>
                    <input type="text" id="fiber_type" placeholder="Cotton">
                    <label>Procent:</label>
                    <input type="number" step="0.01" id="percentage" placeholder="100">
                    <label>Fiberkälla:</label>
                    <input type="text" id="fiber_source" placeholder="India">
                    <label>Varumärke:</label>
                    <input type="text" id="material_trademark" placeholder="Supima">
                    <label>Återvunnen:</label>
                    <select id="is_recycled">
                        <option value="0">Nej</option>
                        <option value="1">Ja</option>
                    </select>
                    <label>Återvunnen procent:</label>
                    <input type="number" step="0.01" id="recycled_percentage" placeholder="0">
                    <label>Återvinningskälla:</label>
                    <select id="recycled_source">
                        <option value="">-- Välj --</option>
                        <option value="pre_consumer">Pre-consumer</option>
                        <option value="post_consumer">Post-consumer</option>
                        <option value="post_consumer_packaging">Post-consumer packaging</option>
                        <option value="other">Other</option>
                    </select>
                    <button class="btn-post" onclick="addComposition()">Lägg till</button>
                </div>
            </div>

            <div class="section" id="sec-cert">
                <div class="section-header" onclick="toggle('sec-cert')"><h2>Material-certifieringar</h2></div>
                <div class="section-content">
                    <button class="btn-get" onclick="api('GET', '/api/materials/' + getMaterialId() + '/certifications')">Lista certifieringar</button>
                    <h3>Lägg till certifiering</h3>
                    <label>Typ:</label>
                    <select id="cert_type">
                        <option value="GOTS">GOTS</option>
                        <option value="GRS">GRS</option>
                        <option value="RCS">RCS</option>
                        <option value="RWS">RWS</option>
                        <option value="Oeko_Tex">Oeko-Tex</option>
                        <option value="BSCI">BSCI</option>
                        <option value="FSC">FSC</option>
                        <option value="other">Other</option>
                    </select>
                    <label>Annan typ (om "other"):</label>
                    <input type="text" id="cert_other" placeholder="Custom certification">
                    <label>Certifikatnummer:</label>
                    <input type="text" id="cert_number" placeholder="GOTS-2024-12345">
                    <label>Giltig från:</label>
                    <input type="date" id="cert_valid_from">
                    <label>Giltig till:</label>
                    <input type="date" id="cert_valid_until">
                    <label>Scope:</label>
                    <select id="cert_scope">
                        <option value="material">Material</option>
                        <option value="product">Product</option>
                        <option value="facility">Facility</option>
                    </select>
                    <label>Dokument-URL:</label>
                    <input type="text" id="cert_document_url" placeholder="https://...">
                    <button class="btn-post" onclick="addCertification()">Lägg till</button>
                </div>
            </div>

            <div class="section" id="sec-supply">
                <div class="section-header" onclick="toggle('sec-supply')"><h2>Supply Chain</h2></div>
                <div class="section-content">
                    <button class="btn-get" onclick="api('GET', '/api/materials/' + getMaterialId() + '/supply-chain')">Lista supply chain</button>
                    <h3>Lägg till supply chain-steg</h3>
                    <label>Process-steg:</label>
                    <select id="process_stage">
                        <option value="fiber">Fiber</option>
                        <option value="spinning">Spinning</option>
                        <option value="weaving_knitting">Weaving/Knitting</option>
                        <option value="dyeing">Dyeing</option>
                        <option value="finishing">Finishing</option>
                    </select>
                    <label>Leverantörsnamn:</label>
                    <input type="text" id="sc_supplier_name" placeholder="ABC Spinning Mill">
                    <label>Land (2 bokstäver):</label>
                    <input type="text" id="sc_country" placeholder="IN" maxlength="2">
                    <label>Facility ID:</label>
                    <input type="text" id="sc_facility_id" placeholder="FAC-12345">
                    <button class="btn-post" onclick="addSupplyChain()">Lägg till</button>
                </div>
            </div>

            <div class="section" id="sec-delete">
                <div class="section-header" onclick="toggle('sec-delete')"><h2>Ta bort underdata</h2></div>
                <div class="section-content">
                    <label>Composition ID:</label>
                    <input type="number" id="delete_composition_id" placeholder="ID">
                    <button class="btn-delete" onclick="api('DELETE', '/api/compositions/' + document.getElementById('delete_composition_id').value)">Ta bort</button>

                    <label>Certification ID:</label>
                    <input type="number" id="delete_cert_id" placeholder="ID">
                    <button class="btn-delete" onclick="api('DELETE', '/api/material-certifications/' + document.getElementById('delete_cert_id').value)">Ta bort</button>

                    <label>Supply Chain ID:</label>
                    <input type="number" id="delete_sc_id" placeholder="ID">
                    <button class="btn-delete" onclick="api('DELETE', '/api/supply-chain/' + document.getElementById('delete_sc_id').value)">Ta bort</button>
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
            return document.getElementById('supplier_id').value;
        }

        function getMaterialId() {
            return document.getElementById('material_id_select').value;
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

        async function loadSuppliers() {
            const res = await fetch('/api/suppliers');
            const json = await res.json();
            const select = document.getElementById('supplier_id');
            select.innerHTML = '<option value="">-- Välj supplier --</option>';
            if (json.data) {
                json.data.forEach(s => {
                    select.innerHTML += `<option value="${s.id}">${s.id}: ${s.supplier_name}</option>`;
                });
            }
        }

        async function loadMaterials() {
            const res = await fetch('/api/materials');
            const json = await res.json();
            const select = document.getElementById('material_id_select');
            select.innerHTML = '<option value="">-- Välj material --</option>';
            if (json.data) {
                json.data.forEach(m => {
                    select.innerHTML += `<option value="${m.id}">${m.id}: ${m.material_name}</option>`;
                });
            }
        }

        function loadAll() {
            loadSuppliers();
            loadMaterials();
        }

        loadAll();

        function createMaterial() {
            api('POST', '/api/suppliers/' + document.getElementById('supplier_id').value + '/materials', {
                material_name: document.getElementById('material_name').value,
                material_type: document.getElementById('material_type').value,
                internal_code: document.getElementById('internal_code').value || null,
                net_weight_per_meter: parseFloat(document.getElementById('net_weight_per_meter').value) || null,
                width_cm: parseInt(document.getElementById('width_cm').value) || null
            }).then(() => loadMaterials());
        }

        function addComposition() {
            api('POST', '/api/materials/' + getMaterialId() + '/compositions', {
                fiber_type: document.getElementById('fiber_type').value,
                percentage: parseFloat(document.getElementById('percentage').value),
                fiber_source: document.getElementById('fiber_source').value || null,
                material_trademark: document.getElementById('material_trademark').value || null,
                is_recycled: document.getElementById('is_recycled').value === '1',
                recycled_percentage: parseFloat(document.getElementById('recycled_percentage').value) || null,
                recycled_source: document.getElementById('recycled_source').value || null
            });
        }

        function addCertification() {
            api('POST', '/api/materials/' + getMaterialId() + '/certifications', {
                certification_type: document.getElementById('cert_type').value,
                certification_other: document.getElementById('cert_other').value || null,
                scope: document.getElementById('cert_scope').value,
                certificate_number: document.getElementById('cert_number').value || null,
                valid_from: document.getElementById('cert_valid_from').value || null,
                valid_until: document.getElementById('cert_valid_until').value || null,
                document_url: document.getElementById('cert_document_url').value || null
            });
        }

        function addSupplyChain() {
            api('POST', '/api/materials/' + getMaterialId() + '/supply-chain', {
                process_stage: document.getElementById('process_stage').value,
                supplier_name: document.getElementById('sc_supplier_name').value || null,
                country: document.getElementById('sc_country').value || null,
                facility_id: document.getElementById('sc_facility_id').value || null
            });
        }
    </script>
</body>
</html>
