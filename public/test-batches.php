<?php // Batches Test ?>
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
        <h1>Batches</h1>
    </div>

    <div class="container">
        <div class="form-section">
            <button class="btn-refresh" onclick="loadAll()">Ladda om data</button>

            <div class="section" id="sec-select">
                <div class="section-header" onclick="toggle('sec-select')"><h2>Välj variant</h2></div>
                <div class="section-content">
                    <label>Brand:</label>
                    <select id="brand_id"></select>
                    <label>Product:</label>
                    <select id="product_id"></select>
                    <label>Variant:</label>
                    <select id="variant_id"></select>
                    <button class="btn-get" onclick="api('GET', '/api/variants/' + getVariantId() + '/batches')">Hämta batches</button>
                </div>
            </div>

            <div class="section" id="sec-create">
                <div class="section-header" onclick="toggle('sec-create')"><h2>Skapa ny batch</h2></div>
                <div class="section-content">
                    <label>Batch-nummer:</label>
                    <input type="text" id="batch_number" placeholder="BATCH-2024-001">
                    <label>PO-nummer:</label>
                    <input type="text" id="po_number" placeholder="PO-12345">
                    <label>Produktionsdatum:</label>
                    <input type="date" id="production_date">
                    <label>Antal:</label>
                    <input type="number" id="quantity" placeholder="100">
                    <button class="btn-post" onclick="create()">Skapa</button>
                </div>
            </div>

            <div class="section" id="sec-get">
                <div class="section-header" onclick="toggle('sec-get')"><h2>Hämta/Ta bort batch</h2></div>
                <div class="section-content">
                    <label>Batch:</label>
                    <select id="batch_id_select"></select>
                    <button class="btn-get" onclick="api('GET', '/api/batches/' + getBatchId())">Hämta</button>
                    <button class="btn-delete" onclick="api('DELETE', '/api/batches/' + getBatchId())">Ta bort</button>
                </div>
            </div>

            <div class="section" id="sec-suppliers">
                <div class="section-header" onclick="toggle('sec-suppliers')"><h2>Batch Suppliers</h2></div>
                <div class="section-content">
                    <label>Supplier:</label>
                    <select id="supplier_id"></select>
                    <label>Produktionssteg:</label>
                    <select id="production_stage">
                        <option value="confection">Confection</option>
                        <option value="dyeing_printing">Dyeing/Printing</option>
                        <option value="weaving_knitting">Weaving/Knitting</option>
                        <option value="spinning">Spinning</option>
                        <option value="other">Other</option>
                    </select>
                    <button class="btn-get" onclick="api('GET', '/api/batches/' + getBatchId() + '/suppliers')">Lista suppliers</button>
                    <button class="btn-post" onclick="addSupplier()">Lägg till supplier</button>
                </div>
            </div>

            <div class="section" id="sec-materials">
                <div class="section-header" onclick="toggle('sec-materials')"><h2>Batch Materials</h2></div>
                <div class="section-content">
                    <label>Factory Material:</label>
                    <select id="factory_material_id"></select>
                    <label>Komponenttyp:</label>
                    <select id="component_type">
                        <option value="body_fabric">Body fabric</option>
                        <option value="lining">Lining</option>
                        <option value="trim">Trim</option>
                        <option value="padding">Padding</option>
                        <option value="other">Other</option>
                    </select>
                    <label>Antal meter:</label>
                    <input type="number" step="0.01" id="quantity_meters" placeholder="10.5">
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

        function getVariantId() {
            return document.getElementById('variant_id').value;
        }

        function getBatchId() {
            return document.getElementById('batch_id_select').value;
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

        async function loadBrands() {
            const res = await fetch('/api/brands');
            const json = await res.json();
            const select = document.getElementById('brand_id');
            select.innerHTML = '<option value="">-- Välj brand --</option>';
            if (json.data) {
                json.data.forEach(b => {
                    select.innerHTML += `<option value="${b.id}">${b.id}: ${b.brand_name}</option>`;
                });
            }
        }

        async function loadProducts() {
            const brandId = document.getElementById('brand_id').value;
            const select = document.getElementById('product_id');
            select.innerHTML = '<option value="">-- Välj produkt --</option>';
            document.getElementById('variant_id').innerHTML = '<option value="">-- Välj variant --</option>';
            document.getElementById('batch_id_select').innerHTML = '<option value="">-- Välj batch --</option>';

            if (brandId) {
                const res = await fetch('/api/brands/' + brandId + '/products');
                const json = await res.json();
                if (json.data) {
                    json.data.forEach(p => {
                        select.innerHTML += `<option value="${p.id}">${p.id}: ${p.product_name}</option>`;
                    });
                }
            }
        }

        async function loadVariants() {
            const productId = document.getElementById('product_id').value;
            const select = document.getElementById('variant_id');
            select.innerHTML = '<option value="">-- Välj variant --</option>';
            document.getElementById('batch_id_select').innerHTML = '<option value="">-- Välj batch --</option>';

            if (productId) {
                const res = await fetch('/api/products/' + productId + '/variants');
                const json = await res.json();
                if (json.data) {
                    json.data.forEach(v => {
                        select.innerHTML += `<option value="${v.id}">${v.id}: ${v.sku} (${v.size || '-'})</option>`;
                    });
                }
            }
        }

        async function loadBatches() {
            const variantId = document.getElementById('variant_id').value;
            const select = document.getElementById('batch_id_select');
            select.innerHTML = '<option value="">-- Välj batch --</option>';

            if (variantId) {
                const res = await fetch('/api/variants/' + variantId + '/batches');
                const json = await res.json();
                if (json.data) {
                    json.data.forEach(b => {
                        select.innerHTML += `<option value="${b.id}">${b.id}: ${b.batch_number}</option>`;
                    });
                }
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

        async function loadFactoryMaterials() {
            const res = await fetch('/api/materials');
            const json = await res.json();
            const select = document.getElementById('factory_material_id');
            select.innerHTML = '<option value="">-- Välj material --</option>';
            if (json.data) {
                json.data.forEach(m => {
                    select.innerHTML += `<option value="${m.id}">${m.id}: ${m.material_name}</option>`;
                });
            }
        }

        function loadAll() {
            loadBrands();
            loadSuppliers();
            loadFactoryMaterials();
            document.getElementById('product_id').innerHTML = '<option value="">-- Välj produkt --</option>';
            document.getElementById('variant_id').innerHTML = '<option value="">-- Välj variant --</option>';
            document.getElementById('batch_id_select').innerHTML = '<option value="">-- Välj batch --</option>';
        }

        document.getElementById('brand_id').addEventListener('change', loadProducts);
        document.getElementById('product_id').addEventListener('change', loadVariants);
        document.getElementById('variant_id').addEventListener('change', loadBatches);

        loadAll();

        function create() {
            api('POST', '/api/variants/' + document.getElementById('variant_id').value + '/batches', {
                batch_number: document.getElementById('batch_number').value,
                po_number: document.getElementById('po_number').value || null,
                production_date: document.getElementById('production_date').value || null,
                quantity: parseInt(document.getElementById('quantity').value) || null
            }).then(() => loadBatches());
        }

        function addSupplier() {
            api('POST', '/api/batches/' + document.getElementById('batch_id_select').value + '/suppliers', {
                supplier_id: parseInt(document.getElementById('supplier_id').value),
                production_stage: document.getElementById('production_stage').value
            });
        }

        function addMaterial() {
            api('POST', '/api/batches/' + document.getElementById('batch_id_select').value + '/materials', {
                factory_material_id: parseInt(document.getElementById('factory_material_id').value),
                component_type: document.getElementById('component_type').value,
                quantity_meters: parseFloat(document.getElementById('quantity_meters').value) || null
            });
        }
    </script>
</body>
</html>
