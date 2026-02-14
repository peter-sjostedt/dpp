<?php
require_once __DIR__ . '/../../src/Config/Auth.php';
use App\Config\Auth;
Auth::requireLogin();
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <title>Materials - DPP Test</title>
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
        input:disabled, select:disabled { background: #f5f5f5; color: #666; }
        label { font-weight: bold; color: #333; display: block; margin-bottom: 5px; }
        label span { font-weight: normal; color: #666; font-size: 12px; margin-left: 8px; }
        button { padding: 12px 24px; margin: 5px 5px 5px 0; border: none; border-radius: 4px; cursor: pointer; color: white; }
        .btn-get { background: #4CAF50; }
        .btn-post { background: #2196F3; }
        .btn-delete { background: #f44336; }
        .error { color: #f44336; }
        .readonly-notice { background: #fff3e0; border: 1px solid #ff9800; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 13px; }
        h3 { color: #FF9800; margin-top: 15px; margin-bottom: 10px; font-size: 14px; }
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
        <h1>Factory Materials</h1>
        <p style="margin: 5px 0 0; opacity: 0.8; font-size: 14px;">Material registrerade av leverantörer</p>
        <div id="tenant_banner" style="margin-top: 10px; padding: 8px 15px; background: rgba(255,255,255,0.2); border-radius: 4px; display: inline-block; font-size: 13px;"></div>
    </div>

    <div class="container">
        <div class="form-section">
            <div id="readonly-notice" class="readonly-notice" style="display: none;">
                Du är inloggad som <strong>Brand</strong> - endast läsåtkomst till leverantörers material.
            </div>

            <select id="material_id_select" onchange="onMaterialSelect()" style="display: none;"></select>

            <div class="section open" id="sec-material">
                <div class="section-header" onclick="toggle('sec-material')"><h2>Material</h2></div>
                <div class="section-content">
                    <label>Materialnamn <span>Beskrivande namn på materialet</span></label>
                    <input type="text" id="material_name">

                    <label>Typ <span>Materialets kategori</span></label>
                    <select id="material_type">
                        <option value="textile">Textile</option>
                        <option value="leather">Leather</option>
                        <option value="trim">Trim</option>
                        <option value="other">Other</option>
                    </select>

                    <label>Beskrivning <span>Beskrivning av materialet (valfritt)</span></label>
                    <input type="text" id="description">

                    <div id="create-buttons">
                        <button class="btn-post" onclick="createMaterial()">Skapa nytt material</button>
                        <button class="btn-delete" onclick="deleteMaterial()" style="margin-left: 10px;">Ta bort</button>
                    </div>
                </div>
            </div>

            <div class="section" id="sec-composition">
                <div class="section-header" onclick="toggle('sec-composition')"><h2>Fibersammansättning</h2></div>
                <div class="section-content">
                    <button class="btn-get" onclick="loadCompositions()">Visa sammansättning</button>

                    <div id="composition-form">
                        <h3>Lägg till fiber</h3>
                        <label>Fibertyp <span>T.ex. Cotton, Polyester</span></label>
                        <input type="text" id="content_name">

                        <label>Procent <span>Andel av totalen</span></label>
                        <input type="number" step="0.01" id="content_value">

                        <label>Fiberkälla <span>Ursprungsland (valfritt)</span></label>
                        <input type="text" id="content_source">

                        <label>Återvunnen <span>Är fibern återvunnen?</span></label>
                        <select id="recycled">
                            <option value="0">Nej</option>
                            <option value="1">Ja</option>
                        </select>

                        <label>Återvunnen procent <span>Andel återvunnet material</span></label>
                        <input type="number" step="0.01" id="recycled_percentage">

                        <label>Återvinningskälla <span>Typ av återvunnet material</span></label>
                        <select id="recycled_input_source">
                            <option value="">-- Välj --</option>
                            <option value="pre_consumer">Pre-consumer</option>
                            <option value="post_consumer">Post-consumer</option>
                            <option value="post_consumer_packaging">Post-consumer packaging</option>
                            <option value="other">Other</option>
                        </select>

                        <button class="btn-post" onclick="addComposition()">Lägg till fiber</button>
                    </div>
                </div>
            </div>

            <div class="section" id="sec-cert">
                <div class="section-header" onclick="toggle('sec-cert')"><h2>Certifieringar</h2></div>
                <div class="section-content">
                    <button class="btn-get" onclick="loadCertifications()">Visa certifieringar</button>

                    <div id="cert-form">
                        <h3>Lägg till certifiering</h3>
                        <label>Certifiering <span>T.ex. GOTS, GRS, Oeko-Tex</span></label>
                        <input type="text" id="certification">

                        <label>Certifikat-ID <span>Unikt ID för certifikatet</span></label>
                        <input type="text" id="certification_id">

                        <label>Giltig till</label>
                        <input type="date" id="cert_valid_until">

                        <button class="btn-post" onclick="addCertification()">Lägg till certifiering</button>
                    </div>
                </div>
            </div>

            <div class="section" id="sec-supply">
                <div class="section-header" onclick="toggle('sec-supply')"><h2>Supply Chain</h2></div>
                <div class="section-content">
                    <button class="btn-get" onclick="loadSupplyChain()">Visa supply chain</button>

                    <div id="supply-form">
                        <h3>Lägg till steg</h3>
                        <label>Process-steg <span>Produktionssteg</span></label>
                        <select id="process_step">
                            <option value="fiber">Fiber</option>
                            <option value="spinning">Spinning</option>
                            <option value="weaving_knitting">Weaving/Knitting</option>
                            <option value="dyeing">Dyeing</option>
                            <option value="finishing">Finishing</option>
                        </select>

                        <label>Land <span>2 bokstäver (ISO)</span></label>
                        <input type="text" id="sc_country" maxlength="2">

                        <label>Facility Name <span>Namn på anläggning</span></label>
                        <input type="text" id="facility_name">

                        <label>Facility Identifier <span>Anläggnings-ID</span></label>
                        <input type="text" id="facility_identifier">

                        <button class="btn-post" onclick="addSupplyChain()">Lägg till steg</button>
                    </div>
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

        let currentSupplierId = null;

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
            document.getElementById('create-buttons').style.display = readonly ? 'none' : 'block';

            // Hide write forms for brands
            const forms = ['composition-form', 'cert-form', 'supply-form'];
            forms.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.style.display = readonly ? 'none' : 'block';
            });

            // Disable inputs for brands
            if (readonly) {
                document.querySelectorAll('input, select').forEach(el => {
                    if (el.id !== 'material_id_select') el.disabled = true;
                });
            }
        }

        async function api(method, endpoint, data = null) {
            if (endpoint.includes('/null') || endpoint.endsWith('/')) return;
            const apiKey = getApiKey();
            if (!apiKey) return;

            const opts = {
                method,
                headers: { 'Content-Type': 'application/json', 'X-API-Key': apiKey }
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

        async function loadMaterials() {
            const apiKey = getApiKey();
            if (!apiKey) return;

            try {
                const res = await fetch('/api/materials', { headers: { 'X-API-Key': apiKey } });
                const json = await res.json();
                document.getElementById('response').textContent = JSON.stringify(json, null, 2);
                document.getElementById('response').className = 'response-section' + (json.error ? ' error' : '');

                const select = document.getElementById('material_id_select');
                select.innerHTML = '';
                if (json.data && json.data.length > 0) {
                    json.data.forEach(m => {
                        select.innerHTML += `<option value="${m.id}" data-supplier="${m.supplier_id}">${m.material_name}</option>`;
                    });
                    select.style.display = json.data.length > 1 ? 'block' : 'none';
                    await loadMaterialData(json.data[0].id);
                    currentSupplierId = json.data[0].supplier_id;
                } else {
                    select.style.display = 'none';
                    clearForm();
                }
            } catch (e) {
                console.error('Error loading materials:', e);
                document.getElementById('response').textContent = 'Error: ' + e.message;
                document.getElementById('response').className = 'response-section error';
            }
        }

        async function onMaterialSelect() {
            const select = document.getElementById('material_id_select');
            const id = select.value;
            if (id) {
                const option = select.options[select.selectedIndex];
                currentSupplierId = option.dataset.supplier;
                await loadMaterialData(id);
            }
        }

        async function loadMaterialData(id) {
            const json = await api('GET', '/api/materials/' + id);
            if (json && json.data) {
                const m = json.data;
                document.getElementById('material_name').value = m.material_name || '';
                document.getElementById('material_type').value = m.material_type || 'textile';
                document.getElementById('description').value = m.description || '';
                currentSupplierId = m.supplier_id;
            }
        }

        function clearForm() {
            document.getElementById('material_name').value = '';
            document.getElementById('material_type').value = 'textile';
            document.getElementById('description').value = '';
        }

        function getMaterialId() {
            return document.getElementById('material_id_select').value;
        }

        async function createMaterial() {
            // For suppliers, create under their own supplier_id
            const json = await api('POST', '/api/materials', {
                material_name: document.getElementById('material_name').value,
                material_type: document.getElementById('material_type').value,
                description: document.getElementById('description').value || null
            });
            if (json && !json.error) loadMaterials();
        }

        async function deleteMaterial() {
            const id = getMaterialId();
            if (!id) return;
            if (confirm('Vill du verkligen ta bort detta material?')) {
                const json = await api('DELETE', '/api/materials/' + id);
                if (json && !json.error) loadMaterials();
            }
        }

        async function loadCompositions() {
            const id = getMaterialId();
            if (id) await api('GET', '/api/materials/' + id + '/compositions');
        }

        async function loadCertifications() {
            const id = getMaterialId();
            if (id) await api('GET', '/api/materials/' + id + '/certifications');
        }

        async function loadSupplyChain() {
            const id = getMaterialId();
            if (id) await api('GET', '/api/materials/' + id + '/supply-chain');
        }

        function addComposition() {
            const id = getMaterialId();
            if (!id) return;
            api('POST', '/api/materials/' + id + '/compositions', {
                content_name: document.getElementById('content_name').value,
                content_value: parseFloat(document.getElementById('content_value').value),
                content_source: document.getElementById('content_source').value || null,
                recycled: document.getElementById('recycled').value === '1',
                recycled_percentage: parseFloat(document.getElementById('recycled_percentage').value) || null,
                recycled_input_source: document.getElementById('recycled_input_source').value || null
            });
        }

        function addCertification() {
            const id = getMaterialId();
            if (!id) return;
            api('POST', '/api/materials/' + id + '/certifications', {
                certification: document.getElementById('certification').value,
                certification_id: document.getElementById('certification_id').value || null,
                valid_until: document.getElementById('cert_valid_until').value || null
            });
        }

        function addSupplyChain() {
            const id = getMaterialId();
            if (!id) return;
            api('POST', '/api/materials/' + id + '/supply-chain', {
                process_step: document.getElementById('process_step').value,
                country: document.getElementById('sc_country').value || null,
                facility_name: document.getElementById('facility_name').value || null,
                facility_identifier: document.getElementById('facility_identifier').value || null
            });
        }

        // Initialize
        showTenantBanner();
        loadMaterials();
    </script>
</body>
</html>
