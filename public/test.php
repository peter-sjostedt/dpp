<?php
require_once __DIR__ . '/../src/Config/Auth.php';
use App\Config\Auth;
Auth::requireLogin();
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DPP API Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 40px; background: #1a237e; color: white; text-align: center; }
        h1 { margin-bottom: 10px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; max-width: 1000px; margin: 40px auto; }
        a { display: block; padding: 20px 20px 15px; background: #4CAF50; color: white; text-decoration: none; border-radius: 8px; font-size: 18px; }
        a:hover { background: #45a049; }
        a small { display: block; font-size: 11px; opacity: 0.85; margin-top: 5px; }
        .tenant-selector { background: rgba(255,255,255,0.15); padding: 20px; border-radius: 8px; max-width: 600px; margin: 20px auto; }
        .tenant-selector select { padding: 10px 15px; font-size: 16px; border-radius: 4px; border: none; width: 100%; max-width: 400px; }
        .tenant-selector label { display: block; margin-bottom: 10px; font-size: 14px; opacity: 0.9; }
        .api-key-display { font-family: monospace; font-size: 12px; margin-top: 10px; background: rgba(0,0,0,0.3); padding: 8px; border-radius: 4px; word-break: break-all; }
        .toggle-buttons { display: flex; gap: 10px; justify-content: center; margin-bottom: 15px; }
        .toggle-buttons button {
            padding: 10px 25px;
            font-size: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.2s;
            background: rgba(255,255,255,0.1);
            color: white;
        }
        .toggle-buttons button:hover { background: rgba(255,255,255,0.2); }
        .toggle-buttons button.active-brand {
            background: #7B1FA2;
            border-color: #7B1FA2;
            color: white;
        }
        .toggle-buttons button.active-supplier {
            background: #1565C0;
            border-color: #1565C0;
            color: white;
        }
        .tenant-type-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 11px;
            margin-left: 8px;
            vertical-align: middle;
        }
        .badge-brand { background: #7B1FA2; }
        .badge-supplier { background: #1565C0; }
    </style>
</head>
<body>
    <div style="position: absolute; top: 20px; right: 20px;">
        <a href="logout.php" style="background: #ef4444; padding: 10px 20px; font-size: 14px;">Logga ut</a>
    </div>
    <h1>DPP API Test Panel</h1>
    <p>Välj tenant-typ och konto för att testa API:et</p>

    <!-- Tenant Selector -->
    <div class="tenant-selector">
        <div class="toggle-buttons">
            <button id="btn-brand" onclick="setTenantType('brand')">
                Varumärke (Brand)
            </button>
            <button id="btn-supplier" onclick="setTenantType('supplier')">
                Fabrik (Supplier)
            </button>
        </div>

        <label id="tenant-label">Välj varumärke:</label>
        <select id="tenant_select" onchange="saveTenant()">
            <option value="">-- Laddar... --</option>
        </select>
        <div class="api-key-display" id="api_key_display">API-nyckel: (välj tenant)</div>
    </div>

    <!-- Master Data (Brand-focused) -->
    <p style="margin-top: 30px; opacity: 0.7; font-size: 14px;">MASTER DATA</p>
    <div class="grid">
        <a href="test-brands.php" style="background: #7B1FA2;">1. Brands<small>Som brand: egen data | Som supplier: relaterade</small></a>
        <a href="test-suppliers.php" style="background: #1565C0;">2. Suppliers<small>Som supplier: egen data | Som brand: relaterade</small></a>
        <a href="test-materials.php" style="background: #1565C0;">3. Materials<small>Som supplier: CRUD | Som brand: läs relaterade</small></a>
    </div>

    <!-- Products (Brand only) -->
    <p style="margin-top: 30px; opacity: 0.7; font-size: 14px;">PRODUKTER <span class="tenant-type-badge badge-brand">Brand</span></p>
    <div class="grid">
        <a href="test-products.php" style="background: #7B1FA2;">4. Products<small>Produktstamdata + skötsel + compliance</small></a>
        <a href="test-variants.php" style="background: #7B1FA2;">5. Variants<small>Storlek/färg-kombinationer</small></a>
    </div>

    <!-- Production (Brand only) -->
    <p style="margin-top: 30px; opacity: 0.7; font-size: 14px;">PRODUKTION <span class="tenant-type-badge badge-brand">Brand</span></p>
    <div class="grid">
        <a href="test-batches.php" style="background: #E65100;">6. Batches<small>Per produktionsorder</small></a>
        <a href="test-items.php" style="background: #C62828;">7. Items (Fas 2)<small>Per plagg</small></a>
    </div>

    <!-- Relations -->
    <p style="margin-top: 30px; opacity: 0.7; font-size: 14px;">RELATIONER</p>
    <div class="grid">
        <a href="test-brand-suppliers.php" style="background: #00838F;">Brand-Supplier<small>Hantera affärsrelationer</small></a>
    </div>

    <!-- DPP Output -->
    <p style="margin-top: 30px; opacity: 0.7; font-size: 14px;">DPP OUTPUT <span class="tenant-type-badge badge-brand">Brand</span></p>
    <div class="grid">
        <a href="dpp-product.php" style="background: #00838F;">DPP Product (Fas 1)<small>QR/GTIN - produktnivå</small></a>
        <a href="dpp-item.php" style="background: #00695C;">DPP Item (Fas 2)<small>RFID - artikelnivå</small></a>
    </div>

    <!-- Docs -->
    <p style="margin-top: 30px; opacity: 0.7; font-size: 14px;">DOKUMENTATION</p>
    <div class="grid">
        <a href="docs/dataflow.html" style="background: #2196F3;">Dataflöde</a>
        <a href="docs/referensdokument.html" style="background: #9C27B0;">Referensdokument</a>
    </div>

    <!-- Admin -->
    <p style="margin-top: 30px; opacity: 0.7; font-size: 14px;">ADMINISTRATION</p>
    <div class="grid">
        <a href="admin-tenants.php" style="background: #37474f;">Hantera Tenants<small>Skapa brands, suppliers och relationer</small></a>
        <a href="reset-database.php" style="background: #b71c1c;">Återställ testdata<small>Radera allt och ladda om testdata</small></a>
    </div>

    <script>
        let currentTenantType = 'brand';

        function setTenantType(type) {
            currentTenantType = type;

            // Update button states
            const brandBtn = document.getElementById('btn-brand');
            const supplierBtn = document.getElementById('btn-supplier');

            brandBtn.classList.remove('active-brand', 'active-supplier');
            supplierBtn.classList.remove('active-brand', 'active-supplier');

            if (type === 'brand') {
                brandBtn.classList.add('active-brand');
            } else {
                supplierBtn.classList.add('active-supplier');
            }

            // Update label
            document.getElementById('tenant-label').textContent =
                type === 'brand' ? 'Välj varumärke:' : 'Välj fabrik:';

            // Save and reload tenants
            localStorage.setItem('dpp_tenant_type', type);
            loadTenants();
        }

        async function loadTenants() {
            const endpoint = currentTenantType === 'brand'
                ? '/api/tenants/brands'
                : '/api/tenants/suppliers';

            const select = document.getElementById('tenant_select');
            select.innerHTML = '<option value="">-- Laddar... --</option>';

            try {
                const res = await fetch(endpoint);
                const json = await res.json();

                select.innerHTML = '<option value="">-- Välj ' +
                    (currentTenantType === 'brand' ? 'varumärke' : 'fabrik') + ' --</option>';

                if (json.data && json.data.length > 0) {
                    json.data.forEach(t => {
                        const name = currentTenantType === 'brand' ? t.brand_name : t.supplier_name;
                        const option = document.createElement('option');
                        option.value = t.api_key;
                        option.dataset.id = t.id;
                        option.dataset.name = name;
                        option.textContent = name;
                        select.appendChild(option);
                    });

                    // Restore saved selection if same tenant type
                    const savedType = localStorage.getItem('dpp_tenant_type');
                    const savedKey = localStorage.getItem('dpp_api_key');
                    if (savedType === currentTenantType && savedKey) {
                        for (let i = 0; i < select.options.length; i++) {
                            if (select.options[i].value === savedKey) {
                                select.selectedIndex = i;
                                document.getElementById('api_key_display').textContent = 'API-nyckel: ' + savedKey;
                                break;
                            }
                        }
                    }
                } else {
                    select.innerHTML = '<option value="">-- Inga ' +
                        (currentTenantType === 'brand' ? 'varumärken' : 'fabriker') + ' hittades --</option>';
                }
            } catch (err) {
                console.error('Failed to load tenants:', err);
                select.innerHTML = '<option value="">-- Fel vid laddning --</option>';
            }
        }

        function saveTenant() {
            const select = document.getElementById('tenant_select');
            const option = select.options[select.selectedIndex];
            const apiKey = select.value;

            if (apiKey) {
                localStorage.setItem('dpp_api_key', apiKey);
                localStorage.setItem('dpp_tenant_type', currentTenantType);
                localStorage.setItem('dpp_tenant_id', option.dataset.id);
                localStorage.setItem('dpp_tenant_name', option.dataset.name);
                document.getElementById('api_key_display').textContent = 'API-nyckel: ' + apiKey;
            } else {
                localStorage.removeItem('dpp_api_key');
                localStorage.removeItem('dpp_tenant_id');
                localStorage.removeItem('dpp_tenant_name');
                document.getElementById('api_key_display').textContent = 'API-nyckel: (välj tenant)';
            }
        }

        // Initialize on page load
        window.onload = function() {
            // Restore saved tenant type or default to 'brand'
            const savedType = localStorage.getItem('dpp_tenant_type') || 'brand';
            setTenantType(savedType);
        };
    </script>
</body>
</html>
