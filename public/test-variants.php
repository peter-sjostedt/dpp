<?php // Variants Test ?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <title>Variants - DPP Test</title>
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
        <h1>Product Variants</h1>
    </div>

    <div class="container">
        <div class="form-section">
            <button class="btn-refresh" onclick="loadAll()">Ladda om data</button>

            <div class="section" id="sec-select">
                <div class="section-header" onclick="toggle('sec-select')"><h2>Välj produkt</h2></div>
                <div class="section-content">
                    <label>Brand:</label>
                    <select id="brand_id"></select>
                    <label>Product:</label>
                    <select id="product_id"></select>
                    <button class="btn-get" onclick="api('GET', '/api/products/' + getProductId() + '/variants')">Hämta varianter</button>
                </div>
            </div>

            <div class="section" id="sec-create">
                <div class="section-header" onclick="toggle('sec-create')"><h2>Skapa ny</h2></div>
                <div class="section-content">
                    <label>Item Number:</label>
                    <input type="text" id="item_number" placeholder="SKU-12345">
                    <label>GTIN:</label>
                    <input type="text" id="gtin" placeholder="1234567890123">
                    <label>Storlek:</label>
                    <input type="text" id="size" placeholder="M">
                    <label>Färg (varumärke):</label>
                    <input type="text" id="color_brand" placeholder="Navy Blue">
                    <label>Färg (generell):</label>
                    <input type="text" id="color_general" placeholder="Blue">
                    <button class="btn-post" onclick="create()">Skapa</button>
                </div>
            </div>

            <div class="section" id="sec-get">
                <div class="section-header" onclick="toggle('sec-get')"><h2>Hämta/Ta bort</h2></div>
                <div class="section-content">
                    <label>Variant:</label>
                    <select id="variant_id_select"></select>
                    <button class="btn-get" onclick="api('GET', '/api/variants/' + getVariantId())">Hämta</button>
                    <button class="btn-delete" onclick="api('DELETE', '/api/variants/' + getVariantId())">Ta bort</button>
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
            return document.getElementById('product_id').value;
        }

        function getVariantId() {
            return document.getElementById('variant_id_select').value;
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
            document.getElementById('variant_id_select').innerHTML = '<option value="">-- Välj variant --</option>';

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
            const select = document.getElementById('variant_id_select');
            select.innerHTML = '<option value="">-- Välj variant --</option>';

            if (productId) {
                const res = await fetch('/api/products/' + productId + '/variants');
                const json = await res.json();
                if (json.data) {
                    json.data.forEach(v => {
                        select.innerHTML += `<option value="${v.id}">${v.id}: ${v.item_number} (${v.size || '-'} / ${v.color_brand || '-'})</option>`;
                    });
                }
            }
        }

        function loadAll() {
            loadBrands();
            document.getElementById('product_id').innerHTML = '<option value="">-- Välj produkt --</option>';
            document.getElementById('variant_id_select').innerHTML = '<option value="">-- Välj variant --</option>';
        }

        document.getElementById('brand_id').addEventListener('change', loadProducts);
        document.getElementById('product_id').addEventListener('change', loadVariants);

        loadAll();

        function create() {
            api('POST', '/api/products/' + document.getElementById('product_id').value + '/variants', {
                item_number: document.getElementById('item_number').value,
                gtin: document.getElementById('gtin').value || null,
                size: document.getElementById('size').value || null,
                color_brand: document.getElementById('color_brand').value || null,
                color_general: document.getElementById('color_general').value || null
            }).then(() => loadVariants());
        }
    </script>
</body>
</html>
