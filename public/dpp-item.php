<?php
require_once __DIR__ . '/../src/Config/Auth.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Auth;
use App\Config\Database;

Auth::requireLogin();

$pdo = Database::getInstance()->getConnection();

// Hämta produkter som har items (via batches → items)
$productsStmt = $pdo->query("
    SELECT DISTINCT p.id, p.product_name, br.brand_name
    FROM products p
    JOIN brands br ON p.brand_id = br.id
    JOIN batches b ON b.product_id = p.id
    JOIN items i ON i.batch_id = b.id
    WHERE p._is_active = TRUE
    ORDER BY br.brand_name, p.product_name
");
$products = $productsStmt->fetchAll();

// Hämta valt item
$selectedItemId = isset($_GET['item_id']) ? (int)$_GET['item_id'] : null;
$itemData = null;
$careInfo = null;
$complianceInfo = null;
$certifications = [];
$circularityInfo = null;
$sustainabilityInfo = null;
$batchMaterials = [];

if ($selectedItemId) {
    // Huvudquery: Item + Batch + Product + Brand (+ optional Variant)
    $mainStmt = $pdo->prepare("
        SELECT
            i.id as item_id,
            i.tid,
            i.sgtin,
            i.created_at as item_created,

            b.id as batch_id,
            b.batch_number,
            b.po_number,
            b.production_date,
            b.quantity as batch_quantity,
            b._status as batch_status,

            pv.id as variant_id,
            pv.item_number,
            pv.gtin as variant_gtin,
            pv.size,
            pv.size_country_code,
            pv.color_brand,
            pv.color_general,

            p.id as product_id,
            p.gtin,
            p.product_name,
            p.description,
            p.photo_url,
            p.article_number,
            p.commodity_code_system,
            p.commodity_code_number,
            p.category,
            p.product_group,
            p.type_item,
            p.gender,
            p.market_segment,
            p.net_weight,
            p.data_carrier_type,
            p.data_carrier_material,
            p.data_carrier_location,

            br.brand_name,
            br.logo_url,
            br.sub_brand,
            br.parent_company,
            br.trader,
            br.trader_location

        FROM items i
        JOIN batches b ON i.batch_id = b.id
        JOIN products p ON b.product_id = p.id
        JOIN brands br ON p.brand_id = br.id
        LEFT JOIN product_variants pv ON i.product_variant_id = pv.id
        WHERE i.id = ?
    ");
    $mainStmt->execute([$selectedItemId]);
    $itemData = $mainStmt->fetch();

    if ($itemData) {
        $productId = $itemData['product_id'];
        $batchId = $itemData['batch_id'];

        // Care Information
        $careStmt = $pdo->prepare("SELECT * FROM care_information WHERE product_id = ?");
        $careStmt->execute([$productId]);
        $careInfo = $careStmt->fetch();

        // Compliance Information
        $complianceStmt = $pdo->prepare("SELECT * FROM compliance_info WHERE product_id = ?");
        $complianceStmt->execute([$productId]);
        $complianceInfo = $complianceStmt->fetch();

        // Product Certifications
        $certStmt = $pdo->prepare("SELECT * FROM product_certifications WHERE product_id = ?");
        $certStmt->execute([$productId]);
        $certifications = $certStmt->fetchAll();

        // Circularity
        $circStmt = $pdo->prepare("SELECT * FROM circularity_info WHERE product_id = ?");
        $circStmt->execute([$productId]);
        $circularityInfo = $circStmt->fetch();

        // Sustainability
        $sustStmt = $pdo->prepare("SELECT * FROM sustainability_info WHERE product_id = ?");
        $sustStmt->execute([$productId]);
        $sustainabilityInfo = $sustStmt->fetch();

        // Batch Materials med Factory Material details
        $matStmt = $pdo->prepare("
            SELECT
                bm.id as batch_material_id,
                bm.component,
                fm.id as factory_material_id,
                fm.material_name,
                fm.material_type,
                fm.description as material_description
            FROM batch_materials bm
            JOIN factory_materials fm ON bm.factory_material_id = fm.id
            WHERE bm.batch_id = ?
        ");
        $matStmt->execute([$batchId]);
        $batchMaterials = $matStmt->fetchAll();

        // Hämta compositions, certifications och supply chain för varje material
        foreach ($batchMaterials as &$material) {
            $fmId = $material['factory_material_id'];

            // Compositions
            $compStmt = $pdo->prepare("
                SELECT content_name, content_value, content_source, recycled, recycled_percentage, recycled_input_source
                FROM factory_material_compositions WHERE factory_material_id = ?
            ");
            $compStmt->execute([$fmId]);
            $material['compositions'] = $compStmt->fetchAll();

            // Material Certifications
            $mcertStmt = $pdo->prepare("
                SELECT certification, certification_id, valid_until
                FROM factory_material_certifications WHERE factory_material_id = ?
            ");
            $mcertStmt->execute([$fmId]);
            $material['certifications'] = $mcertStmt->fetchAll();

            // Supply Chain
            $scStmt = $pdo->prepare("
                SELECT process_step, facility_name, country
                FROM factory_material_supply_chain WHERE factory_material_id = ? ORDER BY sequence
            ");
            $scStmt->execute([$fmId]);
            $material['supply_chain'] = $scStmt->fetchAll();
        }
        unset($material);
    }
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DPP Item Viewer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .card { @apply bg-white rounded-lg shadow-md p-4 mb-4; }
        .card-title { @apply text-lg font-bold mb-3 flex items-center gap-2; }
        .label { @apply text-gray-500 text-sm; }
        .value { @apply font-medium; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="bg-indigo-900 text-white p-4">
        <div class="max-w-4xl mx-auto flex justify-between items-center">
            <div>
                <a href="test.php" class="text-indigo-200 hover:text-white text-sm">&larr; Tillbaka till Test Panel</a>
                <h1 class="text-2xl font-bold">DPP Item Viewer</h1>
                <p class="text-indigo-200 text-sm">Fas 2 - Komplett DPP-data på artikelnivå</p>
            </div>
            <a href="logout.php" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded text-sm">Logga ut</a>
        </div>
    </div>

    <div class="max-w-4xl mx-auto p-4">
        <!-- Item Selector - Cascading Dropdowns -->
        <div class="card">
            <div class="card-title text-indigo-700">Välj artikel</div>
            <form method="GET" id="item-form">
                <input type="hidden" name="item_id" id="item_id_hidden" value="<?= $selectedItemId ?>">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Product -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Produkt</label>
                        <select id="product-select" class="w-full border rounded-lg px-3 py-2">
                            <option value="">-- Välj produkt --</option>
                            <?php foreach ($products as $product): ?>
                            <option value="<?= $product['id'] ?>" <?= ($itemData && $itemData['product_id'] == $product['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($product['brand_name'] . ' - ' . $product['product_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Batch -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Batch</label>
                        <select id="batch-select" class="w-full border rounded-lg px-3 py-2" disabled>
                            <option value="">-- Välj batch --</option>
                        </select>
                    </div>

                    <!-- Item/TID -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">SGTIN (RFID)</label>
                        <select id="tid-select" class="w-full border rounded-lg px-3 py-2 font-mono text-sm" disabled>
                            <option value="">-- Välj artikel --</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>

        <?php if ($itemData): ?>

        <!-- ARTIKEL-ID -->
        <div class="card border-l-4 border-indigo-500">
            <div class="card-title text-indigo-700">📡 ARTIKEL-ID</div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <div class="label">SGTIN (GTIN + serienr)</div>
                    <div class="value text-lg font-mono"><?= htmlspecialchars($itemData['sgtin']) ?></div>
                </div>
                <div>
                    <div class="label">TID (RFID chip-ID)</div>
                    <div class="value font-mono text-sm"><?= htmlspecialchars($itemData['tid'] ?? '-') ?></div>
                </div>
                <div>
                    <div class="label">Skapad</div>
                    <div class="value"><?= $itemData['item_created'] ?></div>
                </div>
            </div>
        </div>

        <!-- VARUMÄRKE (100-serien) -->
        <div class="card border-l-4 border-green-500">
            <div class="card-title text-green-700">🏢 VARUMÄRKE <span class="text-xs font-normal text-gray-400">(100-serien)</span></div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <div class="label">Varumärke</div>
                    <div class="value text-lg"><?= htmlspecialchars($itemData['brand_name']) ?></div>
                </div>
                <div>
                    <div class="label">Sub-brand</div>
                    <div class="value"><?= htmlspecialchars($itemData['sub_brand'] ?? '-') ?></div>
                </div>
                <div>
                    <div class="label">Moderbolag</div>
                    <div class="value"><?= htmlspecialchars($itemData['parent_company'] ?? '-') ?></div>
                </div>
                <div>
                    <div class="label">Handlare</div>
                    <div class="value"><?= htmlspecialchars($itemData['trader'] ?? '-') ?></div>
                </div>
                <div class="col-span-2">
                    <div class="label">Handlaradress</div>
                    <div class="value"><?= htmlspecialchars($itemData['trader_location'] ?? '-') ?></div>
                </div>
            </div>
        </div>

        <!-- LEVERANTÖRSKEDJA (200-serien) -->
        <div class="card border-l-4 border-amber-500">
            <div class="card-title text-amber-700">🏭 LEVERANTÖRSKEDJA <span class="text-xs font-normal text-gray-400">(200-serien)</span></div>

            <?php foreach ($batchMaterials as $mat): ?>
            <?php if (!empty($mat['supply_chain'])): ?>
            <div class="mb-3">
                <div class="label mb-1">Leverantörskedja för <?= htmlspecialchars($mat['material_name']) ?>:</div>
                <div class="flex flex-wrap gap-2 text-sm">
                    <?php foreach ($mat['supply_chain'] as $i => $sc): ?>
                    <div class="bg-gray-100 rounded px-2 py-1">
                        <span class="text-gray-500"><?= ucfirst($sc['process_step']) ?>:</span>
                        <?= htmlspecialchars($sc['facility_name'] ?? 'Okänd') ?>
                        <span class="text-xs text-gray-400">(<?= $sc['country'] ?>)</span>
                    </div>
                    <?php if ($i < count($mat['supply_chain']) - 1): ?>
                    <span class="text-gray-400">&rarr;</span>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php endforeach; ?>

            <?php if (!$batchMaterials): ?>
            <p class="text-gray-500 italic">Ingen leverantörskedjedata tillgänglig.</p>
            <?php endif; ?>
        </div>

        <!-- PRODUKT (300-serien) -->
        <div class="card border-l-4 border-purple-500">
            <div class="card-title text-purple-700">👕 PRODUKT <span class="text-xs font-normal text-gray-400">(300-serien)</span></div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <div class="label">Produktnamn</div>
                    <div class="value text-lg"><?= htmlspecialchars($itemData['product_name']) ?></div>
                </div>
                <div>
                    <div class="label">Variant GTIN</div>
                    <div class="value font-mono"><?= htmlspecialchars($itemData['variant_gtin'] ?? '-') ?></div>
                </div>
                <div>
                    <div class="label">Artikelnummer</div>
                    <div class="value font-mono text-sm"><?= htmlspecialchars($itemData['item_number'] ?? '-') ?></div>
                </div>
                <div>
                    <div class="label">Produkt artikelnummer</div>
                    <div class="value"><?= htmlspecialchars($itemData['article_number'] ?? '-') ?></div>
                </div>
                <div>
                    <div class="label">Storlek</div>
                    <div class="value"><?= htmlspecialchars($itemData['size'] ?? '-') ?> <?= $itemData['size_country_code'] ? '(' . $itemData['size_country_code'] . ')' : '' ?></div>
                </div>
                <div>
                    <div class="label">Färg</div>
                    <div class="value"><?= htmlspecialchars($itemData['color_brand'] ?? '-') ?></div>
                </div>
                <div>
                    <div class="label">Kategori</div>
                    <div class="value"><?= ucfirst($itemData['category'] ?? '-') ?></div>
                </div>
                <div>
                    <div class="label">Produktgrupp</div>
                    <div class="value"><?= htmlspecialchars($itemData['product_group'] ?? '-') ?></div>
                </div>
                <div>
                    <div class="label">Plaggtyp</div>
                    <div class="value"><?= htmlspecialchars($itemData['type_item'] ?? '-') ?></div>
                </div>
                <div>
                    <div class="label">Vikt</div>
                    <div class="value"><?= $itemData['net_weight'] ? $itemData['net_weight'] . ' kg' : '-' ?></div>
                </div>
                <div class="col-span-2">
                    <div class="label">Batch</div>
                    <div class="value">
                        <?= htmlspecialchars($itemData['batch_number']) ?>
                        <span class="text-sm text-gray-500">
                            (PO: <?= htmlspecialchars($itemData['po_number'] ?? '-') ?>,
                            Datum: <?= $itemData['production_date'] ?>,
                            Status: <?= $itemData['batch_status'] ?>)
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- MATERIAL (350-serien) -->
        <div class="card border-l-4 border-cyan-500">
            <div class="card-title text-cyan-700">🧵 MATERIAL <span class="text-xs font-normal text-gray-400">(350-serien)</span></div>

            <?php foreach ($batchMaterials as $mat): ?>
            <div class="bg-cyan-50 rounded-lg p-3 mb-3">
                <div class="font-medium text-cyan-800">
                    <?= htmlspecialchars($mat['material_name']) ?>
                    <?php if ($mat['component']): ?>
                    <span class="text-sm font-normal text-gray-500">(<?= $mat['component'] ?>)</span>
                    <?php endif; ?>
                </div>
                <div class="text-sm text-gray-600">
                    Typ: <?= $mat['material_type'] ?>
                </div>

                <?php if (!empty($mat['compositions'])): ?>
                <div class="mt-2">
                    <div class="text-xs text-gray-500 mb-1">Fibersammansättning:</div>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($mat['compositions'] as $comp): ?>
                        <span class="bg-white rounded px-2 py-1 text-sm">
                            <?= htmlspecialchars($comp['content_name']) ?> <?= $comp['content_value'] ?>%
                            <?php if ($comp['recycled']): ?>
                            <span class="text-green-600">&#9851;</span>
                            <?php endif; ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($mat['certifications'])): ?>
                <div class="mt-2">
                    <div class="text-xs text-gray-500 mb-1">Materialcertifieringar:</div>
                    <?php foreach ($mat['certifications'] as $cert): ?>
                    <span class="inline-block bg-green-100 text-green-800 rounded px-2 py-1 text-xs mr-1">
                        <?= $cert['certification'] ?>
                    </span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>

            <?php if (!$batchMaterials): ?>
            <p class="text-gray-500 italic">Ingen materialdata tillgänglig.</p>
            <?php endif; ?>
        </div>

        <!-- DATABÄRARE (370-serien) -->
        <div class="card border-l-4 border-slate-500">
            <div class="card-title text-slate-700">📡 DATABÄRARE <span class="text-xs font-normal text-gray-400">(370-serien)</span></div>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <div class="label">Typ</div>
                    <div class="value"><?= htmlspecialchars($itemData['data_carrier_type'] ?? '-') ?></div>
                </div>
                <div>
                    <div class="label">Material</div>
                    <div class="value"><?= htmlspecialchars($itemData['data_carrier_material'] ?? '-') ?></div>
                </div>
                <div>
                    <div class="label">Placering</div>
                    <div class="value"><?= htmlspecialchars($itemData['data_carrier_location'] ?? '-') ?></div>
                </div>
            </div>
        </div>

        <!-- SKÖTSEL (400-serien) -->
        <?php if ($careInfo): ?>
        <div class="card border-l-4 border-blue-500">
            <div class="card-title text-blue-700">🧼 SKÖTSEL <span class="text-xs font-normal text-gray-400">(400-serien)</span></div>
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <div class="label">Skötselinstruktion</div>
                    <div class="value"><?= htmlspecialchars($careInfo['care_text'] ?? '-') ?></div>
                </div>
                <?php if (!empty($careInfo['safety_information'])): ?>
                <div>
                    <div class="label">Säkerhetsinformation</div>
                    <div class="value text-orange-600"><?= htmlspecialchars($careInfo['safety_information']) ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- COMPLIANCE (500-serien) -->
        <div class="card border-l-4 border-emerald-500">
            <div class="card-title text-emerald-700">✅ COMPLIANCE <span class="text-xs font-normal text-gray-400">(500-serien)</span></div>

            <?php if ($complianceInfo): ?>
            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <div class="label">Innehåller skadliga ämnen</div>
                    <div class="value"><?= ($complianceInfo['harmful_substances'] ?? '') === 'Yes' ? '&#9888; Ja' : '&#10003; Nej' ?></div>
                </div>
                <div>
                    <div class="label">Avger mikrofiber</div>
                    <div class="value"><?= ($complianceInfo['microfibers'] ?? '') === 'Yes' ? '&#9888; Ja' : '&#10003; Nej' ?></div>
                </div>
                <?php if (!empty($complianceInfo['traceability_provider'])): ?>
                <div>
                    <div class="label">Spårbarhetsleverantör</div>
                    <div class="value"><?= htmlspecialchars($complianceInfo['traceability_provider']) ?></div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($certifications): ?>
            <div class="mb-3">
                <div class="label mb-2">Produktcertifieringar</div>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($certifications as $cert): ?>
                    <span class="bg-emerald-100 text-emerald-800 rounded px-3 py-1 text-sm">
                        <?= $cert['certification_name'] ?>
                        <?php if (!empty($cert['certification_other'])): ?>
                        (<?= htmlspecialchars($cert['certification_other']) ?>)
                        <?php endif; ?>
                        <?php if (!empty($cert['valid_until'])): ?>
                        <span class="text-xs text-gray-500">&rarr; <?= $cert['valid_until'] ?></span>
                        <?php endif; ?>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- CIRKULARITET (600-serien) -->
        <?php if ($circularityInfo): ?>
        <div class="card border-l-4 border-lime-500">
            <div class="card-title text-lime-700">♻️ CIRKULARITET <span class="text-xs font-normal text-gray-400">(600-serien)</span></div>
            <div class="grid grid-cols-1 gap-4">
                <?php if (!empty($circularityInfo['recyclability'])): ?>
                <div>
                    <div class="label">Återvinningsbarhet</div>
                    <div class="value"><?= htmlspecialchars($circularityInfo['recyclability']) ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($circularityInfo['take_back_instructions'])): ?>
                <div>
                    <div class="label">Returinstruktioner</div>
                    <div class="value"><?= htmlspecialchars($circularityInfo['take_back_instructions']) ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($circularityInfo['recycling_instructions'])): ?>
                <div>
                    <div class="label">Återvinningsinstruktioner</div>
                    <div class="value"><?= htmlspecialchars($circularityInfo['recycling_instructions']) ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($circularityInfo['repair_instructions'])): ?>
                <div>
                    <div class="label">Reparationsinstruktioner</div>
                    <div class="value"><?= htmlspecialchars($circularityInfo['repair_instructions']) ?></div>
                </div>
                <?php endif; ?>
                <?php if (!empty($circularityInfo['circular_design_strategy'])): ?>
                <div>
                    <div class="label">Cirkulär designstrategi</div>
                    <div class="value"><?= ucfirst(str_replace('_', ' ', $circularityInfo['circular_design_strategy'])) ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- HÅLLBARHET (650-serien) -->
        <?php if ($sustainabilityInfo): ?>
        <div class="card border-l-4 border-teal-500">
            <div class="card-title text-teal-700">🌱 HÅLLBARHET <span class="text-xs font-normal text-gray-400">(650-serien)</span></div>

            <?php if (!empty($sustainabilityInfo['brand_statement'])): ?>
            <div class="mb-4">
                <div class="label">Varumärkesdeklaration</div>
                <div class="value italic">"<?= htmlspecialchars($sustainabilityInfo['brand_statement']) ?>"</div>
            </div>
            <?php endif; ?>

            <?php if (!empty($sustainabilityInfo['environmental_footprint'])): ?>
            <?php $footprint = json_decode($sustainabilityInfo['environmental_footprint'], true); ?>
            <?php if ($footprint): ?>
            <div>
                <div class="label mb-2">Miljöavtryck</div>
                <div class="grid grid-cols-4 gap-3">
                    <?php if (isset($footprint['co2_kg'])): ?>
                    <div class="bg-teal-50 rounded p-3 text-center">
                        <div class="text-2xl font-bold text-teal-700"><?= $footprint['co2_kg'] ?></div>
                        <div class="text-xs text-gray-500">kg CO2</div>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($footprint['water_liters'])): ?>
                    <div class="bg-blue-50 rounded p-3 text-center">
                        <div class="text-2xl font-bold text-blue-700"><?= number_format($footprint['water_liters']) ?></div>
                        <div class="text-xs text-gray-500">liter vatten</div>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($footprint['energy_mj'])): ?>
                    <div class="bg-amber-50 rounded p-3 text-center">
                        <div class="text-2xl font-bold text-amber-700"><?= $footprint['energy_mj'] ?></div>
                        <div class="text-xs text-gray-500">MJ energi</div>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($footprint['wash_cycles'])): ?>
                    <div class="bg-purple-50 rounded p-3 text-center">
                        <div class="text-2xl font-bold text-purple-700"><?= $footprint['wash_cycles'] ?></div>
                        <div class="text-xs text-gray-500">tvättcykler</div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php elseif ($selectedItemId): ?>
        <div class="card bg-red-50 border-l-4 border-red-500">
            <p class="text-red-700">Item med ID <?= $selectedItemId ?> hittades inte.</p>
        </div>
        <?php else: ?>
        <div class="card bg-blue-50 border-l-4 border-blue-500">
            <p class="text-blue-700">Välj en produkt, batch och artikel ovan för att visa komplett DPP-data.</p>
        </div>
        <?php endif; ?>

    </div>

    <div class="bg-gray-200 text-center py-4 text-sm text-gray-600">
        DPP Item Viewer - Fas 2 RFID-nivå | <a href="docs/dataflow.html" class="text-indigo-600 hover:underline">Dataflödesdokumentation</a>
    </div>

    <script>
    const productSelect = document.getElementById('product-select');
    const batchSelect = document.getElementById('batch-select');
    const tidSelect = document.getElementById('tid-select');
    const itemIdHidden = document.getElementById('item_id_hidden');

    // Pre-fill dropdowns if item is already selected
    const preselectedData = <?php if ($itemData): ?>{
        productId: <?= $itemData['product_id'] ?>,
        batchId: <?= $itemData['batch_id'] ?>,
        itemId: <?= $itemData['item_id'] ?>
    }<?php else: ?>null<?php endif; ?>;

    // Product change -> load batches
    productSelect.addEventListener('change', async function() {
        batchSelect.innerHTML = '<option value="">-- Välj batch --</option>';
        batchSelect.disabled = true;
        tidSelect.innerHTML = '<option value="">-- Välj artikel --</option>';
        tidSelect.disabled = true;

        const productId = this.value;
        if (!productId) return;

        try {
            const response = await fetch(`/api/products/${productId}/batches`, {
                headers: { 'X-API-Key': localStorage.getItem('dpp_api_key') || '' }
            });
            const data = await response.json();
            if (data.data && data.data.length > 0) {
                data.data.forEach(b => {
                    const option = document.createElement('option');
                    option.value = b.id;
                    option.textContent = `${b.batch_number} (${b.production_date || 'inget datum'})`;
                    batchSelect.appendChild(option);
                });
                batchSelect.disabled = false;

                // Pre-select if we have preselected data
                if (preselectedData && preselectedData.productId == productId) {
                    batchSelect.value = preselectedData.batchId;
                    batchSelect.dispatchEvent(new Event('change'));
                }
            }
        } catch (e) {
            console.error('Error loading batches:', e);
        }
    });

    // Batch change -> load items
    batchSelect.addEventListener('change', async function() {
        tidSelect.innerHTML = '<option value="">-- Välj artikel --</option>';
        tidSelect.disabled = true;

        const batchId = this.value;
        if (!batchId) return;

        try {
            const response = await fetch(`/api/batches/${batchId}/items`, {
                headers: { 'X-API-Key': localStorage.getItem('dpp_api_key') || '' }
            });
            const data = await response.json();
            if (data.data && data.data.length > 0) {
                data.data.forEach(item => {
                    const option = document.createElement('option');
                    option.value = item.id;
                    option.textContent = item.sgtin || item.tid || `Item #${item.id}`;
                    tidSelect.appendChild(option);
                });
                tidSelect.disabled = false;

                // Pre-select if we have preselected data
                if (preselectedData && preselectedData.batchId == batchId) {
                    tidSelect.value = preselectedData.itemId;
                }
            }
        } catch (e) {
            console.error('Error loading items:', e);
        }
    });

    // Item change -> submit form to load DPP data
    tidSelect.addEventListener('change', function() {
        const itemId = this.value;
        if (itemId) {
            itemIdHidden.value = itemId;
            document.getElementById('item-form').submit();
        }
    });

    // Initialize: if product is already selected, trigger change event
    if (productSelect.value) {
        productSelect.dispatchEvent(new Event('change'));
    }
    </script>
</body>
</html>
