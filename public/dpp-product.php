<?php
require_once __DIR__ . '/../src/Config/Auth.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Auth;
use App\Config\Database;

Auth::requireLogin();

$pdo = Database::getInstance()->getConnection();

// Hämta alla produkter för dropdown (endast GTIN - simulerar QR-skanning)
$productsStmt = $pdo->query("
    SELECT gtin
    FROM products
    WHERE _is_active = TRUE
    ORDER BY gtin
");
$products = $productsStmt->fetchAll();

// GS1 Digital Link bas-URL
$gs1BaseUrl = 'https://id.gs1.org/01/';

// Hämta vald produkt via GS1 Digital Link eller direkt GTIN
$selectedGtin = null;
$selectedGs1Link = $_GET['gs1_link'] ?? null;

// Extrahera GTIN från GS1 Digital Link
if ($selectedGs1Link) {
    // Format: https://id.gs1.org/01/7350012345001
    if (preg_match('/\/01\/(\d{13,14})/', $selectedGs1Link, $matches)) {
        $selectedGtin = $matches[1];
    }
}

// Alternativt: direkt GTIN-parameter (för bakåtkompatibilitet)
if (!$selectedGtin && isset($_GET['gtin'])) {
    $selectedGtin = $_GET['gtin'];
}

$productData = null;
$variants = [];
$careInfo = null;
$complianceInfo = null;
$certifications = [];
$chemicalCompliance = [];
$circularityInfo = null;
$sustainabilityInfo = null;
$latestBatch = null;
$batchMaterials = [];
$batchSuppliers = [];

if ($selectedGtin) {
    // Huvudquery: Product + Brand
    $mainStmt = $pdo->prepare("
        SELECT
            p.id as product_id,
            p.gtin,
            p.product_name,
            p.description,
            p.photo_url,
            p.article_number,
            p.commodity_code_system,
            p.commodity_code_number,
            p.year_of_sale,
            p.season_of_sale,
            p.price_currency,
            p.msrp,
            p.category,
            p.product_group,
            p.line,
            p.garment_type,
            p.age_group,
            p.gender,
            p.market_segment,
            p.water_properties,
            p.weight_kg,
            p.data_carrier_type,
            p.data_carrier_material,
            p.data_carrier_location,

            br.brand_name,
            br.logo_url,
            br.sub_brand,
            br.parent_company,
            br.trader_name,
            br.trader_address

        FROM products p
        JOIN brands br ON p.brand_id = br.id
        WHERE p.gtin = ?
    ");
    $mainStmt->execute([$selectedGtin]);
    $productData = $mainStmt->fetch();

    if ($productData) {
        $productId = $productData['product_id'];

        // Hämta varianter
        $varStmt = $pdo->prepare("
            SELECT id, sku, size, size_system, color_name, color_code
            FROM product_variants
            WHERE product_id = ? AND _is_active = TRUE
            ORDER BY color_name, size
        ");
        $varStmt->execute([$productId]);
        $variants = $varStmt->fetchAll();

        // Hämta senaste batch
        $batchStmt = $pdo->prepare("
            SELECT
                b.id as batch_id,
                b.batch_number,
                b.po_number,
                b.production_date,
                b.quantity,
                b._status,
                pv.sku,
                pv.size,
                pv.color_name
            FROM batches b
            JOIN product_variants pv ON b.product_variant_id = pv.id
            WHERE pv.product_id = ?
            ORDER BY b.production_date DESC
            LIMIT 1
        ");
        $batchStmt->execute([$productId]);
        $latestBatch = $batchStmt->fetch();

        // Care Information
        $careStmt = $pdo->prepare("SELECT * FROM care_information WHERE product_id = ?");
        $careStmt->execute([$productId]);
        $careInfo = $careStmt->fetch();

        // Compliance Information
        $complianceStmt = $pdo->prepare("SELECT * FROM compliance_information WHERE product_id = ?");
        $complianceStmt->execute([$productId]);
        $complianceInfo = $complianceStmt->fetch();

        // Certifications
        $certStmt = $pdo->prepare("SELECT * FROM certifications WHERE product_id = ?");
        $certStmt->execute([$productId]);
        $certifications = $certStmt->fetchAll();

        // Chemical Compliance
        $chemStmt = $pdo->prepare("SELECT * FROM chemical_compliance WHERE product_id = ?");
        $chemStmt->execute([$productId]);
        $chemicalCompliance = $chemStmt->fetchAll();

        // Circularity
        $circStmt = $pdo->prepare("SELECT * FROM circularity_information WHERE product_id = ?");
        $circStmt->execute([$productId]);
        $circularityInfo = $circStmt->fetch();

        // Sustainability
        $sustStmt = $pdo->prepare("SELECT * FROM sustainability_information WHERE product_id = ?");
        $sustStmt->execute([$productId]);
        $sustainabilityInfo = $sustStmt->fetch();

        // Om vi har en batch, hämta material och leverantörer
        if ($latestBatch) {
            $batchId = $latestBatch['batch_id'];

            // Batch Materials
            $matStmt = $pdo->prepare("
                SELECT
                    bm.component_type,
                    bm.quantity_meters,
                    fm.id as factory_material_id,
                    fm.material_name,
                    fm.material_type,
                    fm._internal_code
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
                    SELECT fiber_type, percentage, fiber_source, material_trademark, is_recycled, recycled_percentage, recycled_source
                    FROM factory_material_compositions WHERE factory_material_id = ?
                ");
                $compStmt->execute([$fmId]);
                $material['compositions'] = $compStmt->fetchAll();

                // Material Certifications
                $mcertStmt = $pdo->prepare("
                    SELECT certification_type, certification_other, certificate_number, valid_until
                    FROM factory_material_certifications WHERE factory_material_id = ?
                ");
                $mcertStmt->execute([$fmId]);
                $material['certifications'] = $mcertStmt->fetchAll();

                // Supply Chain
                $scStmt = $pdo->prepare("
                    SELECT process_stage, supplier_name, country
                    FROM factory_material_supply_chain WHERE factory_material_id = ? ORDER BY sequence
                ");
                $scStmt->execute([$fmId]);
                $material['supply_chain'] = $scStmt->fetchAll();
            }
            unset($material);

            // Batch Suppliers
            $bsStmt = $pdo->prepare("
                SELECT
                    s.supplier_name,
                    s.supplier_location,
                    s.facility_registry,
                    s.facility_identifier,
                    bs.production_stage,
                    bs.country_of_origin
                FROM batch_suppliers bs
                JOIN suppliers s ON bs.supplier_id = s.id
                WHERE bs.batch_id = ?
            ");
            $bsStmt->execute([$batchId]);
            $batchSuppliers = $bsStmt->fetchAll();
        }
    }
}

// Gruppera varianter per färg
$variantsByColor = [];
foreach ($variants as $v) {
    $color = $v['color_name'];
    if (!isset($variantsByColor[$color])) {
        $variantsByColor[$color] = [];
    }
    $variantsByColor[$color][] = $v['size'];
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DPP Product Viewer (Fas 1 / QR)</title>
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
                <h1 class="text-2xl font-bold">DPP Product Viewer</h1>
                <p class="text-indigo-200 text-sm">Fas 1 - QR/GTIN-nivå (alla plagg av samma modell)</p>
            </div>
            <a href="logout.php" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded text-sm">Logga ut</a>
        </div>
    </div>

    <div class="max-w-4xl mx-auto p-4">
        <!-- Product Selector -->
        <div class="card">
            <form method="GET" class="flex gap-4 items-end">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">GS1 Digital Link <span class="font-normal text-gray-500">(simulerar QR-skanning)</span>:</label>
                    <select name="gs1_link" onchange="this.form.submit()" class="w-full border rounded-lg px-3 py-2 text-sm font-mono">
                        <option value="">-- Skanna QR-kod --</option>
                        <?php foreach ($products as $product): ?>
                        <?php $gs1Link = $gs1BaseUrl . $product['gtin']; ?>
                        <option value="<?= htmlspecialchars($gs1Link) ?>" <?= $selectedGtin == $product['gtin'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($gs1Link) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
            <p class="text-sm text-gray-500 mt-2">
                QR-koden innehåller GS1 Digital Link: <code class="bg-gray-100 px-2 py-1 rounded">https://id.gs1.org/01/{GTIN}</code>
            </p>
        </div>

        <?php if ($productData): ?>

        <!-- PRODUKT-ID (QR) -->
        <div class="card border-l-4 border-indigo-500">
            <div class="card-title text-indigo-700">📱 PRODUKT-ID (QR)</div>
            <div class="mb-4">
                <div class="label">GS1 Digital Link (QR-innehåll)</div>
                <div class="value text-lg font-mono bg-gray-50 p-2 rounded mt-1">
                    <a href="<?= htmlspecialchars($gs1BaseUrl . $productData['gtin']) ?>" target="_blank" class="text-indigo-600 hover:underline">
                        <?= htmlspecialchars($gs1BaseUrl . $productData['gtin']) ?>
                    </a>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <div class="label">GTIN (extraherat)</div>
                    <div class="value font-mono"><?= htmlspecialchars($productData['gtin']) ?></div>
                </div>
                <div>
                    <div class="label">DPP-nivå</div>
                    <div class="value">Modell <span class="text-sm text-gray-500">(samma data för alla plagg av denna typ)</span></div>
                </div>
            </div>
            <!-- QR-kod med GS1 Digital Link -->
            <div class="mt-4 text-center">
                <img src="https://chart.googleapis.com/chart?chs=150x150&cht=qr&chl=<?= urlencode($gs1BaseUrl . $productData['gtin']) ?>"
                     alt="QR-kod" class="inline-block border rounded">
                <p class="text-xs text-gray-500 mt-1">QR → GS1 Digital Link → GTIN → DPP-data</p>
            </div>
        </div>

        <!-- VARUMÄRKE (100-serien) -->
        <div class="card border-l-4 border-green-500">
            <div class="card-title text-green-700">🏢 VARUMÄRKE <span class="text-xs font-normal text-gray-400">(100-serien)</span></div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <div class="label">Varumärke</div>
                    <div class="value text-lg"><?= htmlspecialchars($productData['brand_name']) ?></div>
                </div>
                <div>
                    <div class="label">Sub-brand</div>
                    <div class="value"><?= htmlspecialchars($productData['sub_brand'] ?? '-') ?></div>
                </div>
                <div>
                    <div class="label">Moderbolag</div>
                    <div class="value"><?= htmlspecialchars($productData['parent_company'] ?? '-') ?></div>
                </div>
                <div>
                    <div class="label">Handlare</div>
                    <div class="value"><?= htmlspecialchars($productData['trader_name'] ?? '-') ?></div>
                </div>
                <div class="col-span-2">
                    <div class="label">Handlaradress</div>
                    <div class="value"><?= htmlspecialchars($productData['trader_address'] ?? '-') ?></div>
                </div>
            </div>
        </div>

        <!-- LEVERANTÖRSKEDJA (200-serien) -->
        <div class="card border-l-4 border-amber-500">
            <div class="card-title text-amber-700">🏭 LEVERANTÖRSKEDJA <span class="text-xs font-normal text-gray-400">(200-serien)</span></div>

            <?php if ($latestBatch): ?>
            <p class="text-sm text-gray-500 mb-3 italic">Baserat på senaste batch: <?= htmlspecialchars($latestBatch['batch_number']) ?></p>
            <?php endif; ?>

            <?php if ($batchSuppliers): ?>
            <div class="mb-4">
                <div class="label mb-2">Produktionsleverantörer</div>
                <?php foreach ($batchSuppliers as $bs): ?>
                <div class="bg-amber-50 rounded p-2 mb-2">
                    <div class="font-medium"><?= htmlspecialchars($bs['supplier_name']) ?></div>
                    <div class="text-sm text-gray-600">
                        <?= htmlspecialchars($bs['production_stage']) ?> |
                        Land: <?= htmlspecialchars($bs['country_of_origin']) ?>
                        <?php if ($bs['facility_identifier']): ?>
                        | <?= $bs['facility_registry'] ?>: <?= htmlspecialchars($bs['facility_identifier']) ?>
                        <?php endif; ?>
                    </div>
                    <?php if ($bs['supplier_location']): ?>
                    <div class="text-xs text-gray-500"><?= htmlspecialchars($bs['supplier_location']) ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php foreach ($batchMaterials as $mat): ?>
            <?php if (!empty($mat['supply_chain'])): ?>
            <div class="mb-3">
                <div class="label mb-1">Leverantörskedja för <?= htmlspecialchars($mat['material_name']) ?>:</div>
                <div class="flex flex-wrap gap-2 text-sm">
                    <?php foreach ($mat['supply_chain'] as $i => $sc): ?>
                    <div class="bg-gray-100 rounded px-2 py-1">
                        <span class="text-gray-500"><?= ucfirst(str_replace('_', '/', $sc['process_stage'])) ?>:</span>
                        <?= htmlspecialchars($sc['supplier_name'] ?? 'Okänd') ?>
                        <span class="text-xs text-gray-400">(<?= $sc['country'] ?>)</span>
                    </div>
                    <?php if ($i < count($mat['supply_chain']) - 1): ?>
                    <span class="text-gray-400">→</span>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php endforeach; ?>

            <?php if (!$batchSuppliers && !$batchMaterials): ?>
            <p class="text-gray-500 italic">Ingen batch-data tillgänglig för denna produkt.</p>
            <?php endif; ?>
        </div>

        <!-- PRODUKT (300-serien) -->
        <div class="card border-l-4 border-purple-500">
            <div class="card-title text-purple-700">👕 PRODUKT <span class="text-xs font-normal text-gray-400">(300-serien)</span></div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <div class="label">Produktnamn</div>
                    <div class="value text-lg"><?= htmlspecialchars($productData['product_name']) ?></div>
                </div>
                <div>
                    <div class="label">Artikelnummer</div>
                    <div class="value"><?= htmlspecialchars($productData['article_number'] ?? '-') ?></div>
                </div>
                <div>
                    <div class="label">Kategori</div>
                    <div class="value"><?= ucfirst($productData['category'] ?? '-') ?></div>
                </div>
                <div>
                    <div class="label">Produktgrupp</div>
                    <div class="value"><?= htmlspecialchars($productData['product_group'] ?? '-') ?></div>
                </div>
                <div>
                    <div class="label">Plaggtyp</div>
                    <div class="value"><?= htmlspecialchars($productData['garment_type'] ?? '-') ?></div>
                </div>
                <div>
                    <div class="label">Kön</div>
                    <div class="value"><?= ucfirst($productData['gender'] ?? '-') ?></div>
                </div>
                <div>
                    <div class="label">Vikt</div>
                    <div class="value"><?= $productData['weight_kg'] ? $productData['weight_kg'] . ' kg' : '-' ?></div>
                </div>
                <div>
                    <div class="label">HS-kod</div>
                    <div class="value"><?= $productData['commodity_code_system'] ?>: <?= htmlspecialchars($productData['commodity_code_number'] ?? '-') ?></div>
                </div>
                <?php if ($productData['description']): ?>
                <div class="col-span-2">
                    <div class="label">Beskrivning</div>
                    <div class="value"><?= htmlspecialchars($productData['description']) ?></div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Varianter -->
            <?php if ($variantsByColor): ?>
            <div class="mt-4 pt-4 border-t">
                <div class="label mb-2">Tillgängliga varianter</div>
                <div class="space-y-1">
                    <?php foreach ($variantsByColor as $color => $sizes): ?>
                    <div class="flex items-center gap-2">
                        <span class="font-medium"><?= htmlspecialchars($color) ?>:</span>
                        <span class="text-gray-600"><?= implode(', ', $sizes) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- MATERIAL (350-serien) -->
        <div class="card border-l-4 border-cyan-500">
            <div class="card-title text-cyan-700">🧵 MATERIAL <span class="text-xs font-normal text-gray-400">(350-serien)</span></div>

            <?php if ($batchMaterials): ?>
            <?php foreach ($batchMaterials as $mat): ?>
            <div class="bg-cyan-50 rounded-lg p-3 mb-3">
                <div class="font-medium text-cyan-800">
                    <?= htmlspecialchars($mat['material_name']) ?>
                    <span class="text-sm font-normal text-gray-500">(<?= $mat['component_type'] ?>)</span>
                </div>
                <div class="text-sm text-gray-600">
                    Kod: <?= htmlspecialchars($mat['_internal_code']) ?> |
                    Typ: <?= $mat['material_type'] ?>
                </div>

                <?php if (!empty($mat['compositions'])): ?>
                <div class="mt-2">
                    <div class="text-xs text-gray-500 mb-1">Fibersammansättning:</div>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($mat['compositions'] as $comp): ?>
                        <span class="bg-white rounded px-2 py-1 text-sm">
                            <?= htmlspecialchars($comp['fiber_type']) ?> <?= $comp['percentage'] ?>%
                            <?php if ($comp['is_recycled']): ?>
                            <span class="text-green-600">♻️</span>
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
                        <?= $cert['certification_type'] ?>
                        <?php if ($cert['certification_other']): ?>
                        (<?= htmlspecialchars($cert['certification_other']) ?>)
                        <?php endif; ?>
                    </span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <p class="text-gray-500 italic">Ingen materialdata tillgänglig.</p>
            <?php endif; ?>
        </div>

        <!-- DATABÄRARE (370-serien) -->
        <div class="card border-l-4 border-slate-500">
            <div class="card-title text-slate-700">📡 DATABÄRARE <span class="text-xs font-normal text-gray-400">(370-serien)</span></div>
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <div class="label">Typ</div>
                    <div class="value"><?= htmlspecialchars($productData['data_carrier_type'] ?? '-') ?></div>
                </div>
                <div>
                    <div class="label">Material</div>
                    <div class="value"><?= htmlspecialchars($productData['data_carrier_material'] ?? '-') ?></div>
                </div>
                <div>
                    <div class="label">Placering</div>
                    <div class="value"><?= htmlspecialchars($productData['data_carrier_location'] ?? '-') ?></div>
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
                <?php if ($careInfo['safety_information']): ?>
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
                    <div class="label">Innehåller SVHC</div>
                    <div class="value"><?= $complianceInfo['contains_svhc'] ? '⚠️ Ja' : '✅ Nej' ?></div>
                </div>
                <div>
                    <div class="label">Avger mikrofiber</div>
                    <div class="value"><?= $complianceInfo['sheds_microfibers'] ? '⚠️ Ja' : '✅ Nej' ?></div>
                </div>
                <?php if ($complianceInfo['traceability_provider']): ?>
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
                        <?php if ($cert['certification_other']): ?>
                        (<?= htmlspecialchars($cert['certification_other']) ?>)
                        <?php endif; ?>
                        <?php if ($cert['valid_until']): ?>
                        <span class="text-xs text-gray-500">→ <?= $cert['valid_until'] ?></span>
                        <?php endif; ?>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($chemicalCompliance): ?>
            <div>
                <div class="label mb-2">Kemikaliecompliance</div>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($chemicalCompliance as $chem): ?>
                    <span class="bg-blue-100 text-blue-800 rounded px-3 py-1 text-sm">
                        <?= htmlspecialchars($chem['compliance_standard']) ?>
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
                <?php if ($circularityInfo['recyclability']): ?>
                <div>
                    <div class="label">Återvinningsbarhet</div>
                    <div class="value"><?= htmlspecialchars($circularityInfo['recyclability']) ?></div>
                </div>
                <?php endif; ?>
                <?php if ($circularityInfo['take_back_instructions']): ?>
                <div>
                    <div class="label">Returinstruktioner</div>
                    <div class="value"><?= htmlspecialchars($circularityInfo['take_back_instructions']) ?></div>
                </div>
                <?php endif; ?>
                <?php if ($circularityInfo['recycling_instructions']): ?>
                <div>
                    <div class="label">Återvinningsinstruktioner</div>
                    <div class="value"><?= htmlspecialchars($circularityInfo['recycling_instructions']) ?></div>
                </div>
                <?php endif; ?>
                <?php if ($circularityInfo['repair_instructions']): ?>
                <div>
                    <div class="label">Reparationsinstruktioner</div>
                    <div class="value"><?= htmlspecialchars($circularityInfo['repair_instructions']) ?></div>
                </div>
                <?php endif; ?>
                <?php if ($circularityInfo['circular_design_strategy']): ?>
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

            <?php if ($sustainabilityInfo['brand_statement']): ?>
            <div class="mb-4">
                <div class="label">Varumärkesdeklaration</div>
                <div class="value italic">"<?= htmlspecialchars($sustainabilityInfo['brand_statement']) ?>"</div>
            </div>
            <?php endif; ?>

            <?php if ($sustainabilityInfo['environmental_footprint']): ?>
            <?php $footprint = json_decode($sustainabilityInfo['environmental_footprint'], true); ?>
            <?php if ($footprint): ?>
            <div>
                <div class="label mb-2">Miljöavtryck</div>
                <div class="grid grid-cols-4 gap-3">
                    <?php if (isset($footprint['co2_kg'])): ?>
                    <div class="bg-teal-50 rounded p-3 text-center">
                        <div class="text-2xl font-bold text-teal-700"><?= $footprint['co2_kg'] ?></div>
                        <div class="text-xs text-gray-500">kg CO₂</div>
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

        <!-- Länk till Item-nivå -->
        <div class="card bg-indigo-50 border-l-4 border-indigo-500">
            <p class="text-indigo-700">
                <strong>Vill du se data för ett specifikt plagg?</strong><br>
                <a href="dpp-item.php" class="underline">Gå till DPP Item Viewer (Fas 2 / RFID)</a> för att välja ett enskilt plagg med serienummer.
            </p>
        </div>

        <?php elseif ($selectedGtin): ?>
        <div class="card bg-red-50 border-l-4 border-red-500">
            <p class="text-red-700">Produkt med GTIN <?= htmlspecialchars($selectedGtin) ?> hittades inte.</p>
        </div>
        <?php else: ?>
        <div class="card bg-blue-50 border-l-4 border-blue-500">
            <p class="text-blue-700">Välj en produkt i dropdown-menyn ovan för att visa dess DPP-data.</p>
        </div>
        <?php endif; ?>

    </div>

    <div class="bg-gray-200 text-center py-4 text-sm text-gray-600">
        DPP Product Viewer - Fas 1 QR/GTIN-nivå | <a href="docs/dataflow.html" class="text-indigo-600 hover:underline">Dataflödesdokumentation</a>
    </div>
</body>
</html>
