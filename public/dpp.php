<?php
/**
 * DPP Public Endpoint - Fas 1 QR/GTIN
 * Ren endpoint för QR-skanning - ingen inloggning krävs
 *
 * Accepterar:
 * - GS1 Digital Link: /dpp.php?link=https://id.gs1.org/01/7350012345001
 * - Direkt GTIN: /dpp.php?gtin=7350012345001
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;

// GS1 Digital Link bas-URL
$gs1BaseUrl = 'https://id.gs1.org/01/';

// Extrahera GTIN från GS1 Digital Link eller direkt parameter
$gtin = null;
$gs1Link = $_GET['link'] ?? null;

if ($gs1Link) {
    // Format: https://id.gs1.org/01/7350012345001
    if (preg_match('/\/01\/(\d{13,14})/', $gs1Link, $matches)) {
        $gtin = $matches[1];
    }
}

// Fallback till direkt GTIN-parameter
if (!$gtin) {
    $gtin = $_GET['gtin'] ?? null;
}

if (!$gtin) {
    http_response_code(400);
    showError('Ingen produkt angiven', 'Ange GS1 Digital Link: /dpp.php?link=https://id.gs1.org/01/7350012345001');
    exit;
}

if (!preg_match('/^\d{13,14}$/', $gtin)) {
    http_response_code(400);
    showError('Ogiltig GTIN', 'GTIN måste vara 13-14 siffror.');
    exit;
}

// Skapa GS1 Digital Link för visning
$gs1DigitalLink = $gs1BaseUrl . $gtin;

$pdo = Database::getInstance()->getConnection();

// Hämta produkt
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
        p.category,
        p.product_group,
        p.garment_type,
        p.gender,
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
$mainStmt->execute([$gtin]);
$productData = $mainStmt->fetch();

if (!$productData) {
    http_response_code(404);
    showError('Produkt hittades inte', "Ingen produkt med GTIN $gtin finns registrerad.");
    exit;
}

$productId = $productData['product_id'];

// Hämta all relaterad data
$varStmt = $pdo->prepare("SELECT size, color_name FROM product_variants WHERE product_id = ? AND _is_active = TRUE ORDER BY color_name, size");
$varStmt->execute([$productId]);
$variants = $varStmt->fetchAll();

$careStmt = $pdo->prepare("SELECT care_text, safety_information FROM care_information WHERE product_id = ?");
$careStmt->execute([$productId]);
$careInfo = $careStmt->fetch();

$complianceStmt = $pdo->prepare("SELECT contains_svhc, sheds_microfibers, traceability_provider FROM compliance_information WHERE product_id = ?");
$complianceStmt->execute([$productId]);
$complianceInfo = $complianceStmt->fetch();

$certStmt = $pdo->prepare("SELECT certification_name, certification_other, valid_until FROM certifications WHERE product_id = ?");
$certStmt->execute([$productId]);
$certifications = $certStmt->fetchAll();

$chemStmt = $pdo->prepare("SELECT compliance_standard FROM chemical_compliance WHERE product_id = ?");
$chemStmt->execute([$productId]);
$chemicalCompliance = $chemStmt->fetchAll();

$circStmt = $pdo->prepare("SELECT recyclability, take_back_instructions, repair_instructions, circular_design_strategy FROM circularity_information WHERE product_id = ?");
$circStmt->execute([$productId]);
$circularityInfo = $circStmt->fetch();

$sustStmt = $pdo->prepare("SELECT brand_statement, environmental_footprint FROM sustainability_information WHERE product_id = ?");
$sustStmt->execute([$productId]);
$sustainabilityInfo = $sustStmt->fetch();

// Senaste batch + material
$batchStmt = $pdo->prepare("
    SELECT b.id as batch_id, b.batch_number, b.production_date
    FROM batches b
    JOIN product_variants pv ON b.product_variant_id = pv.id
    WHERE pv.product_id = ?
    ORDER BY b.production_date DESC LIMIT 1
");
$batchStmt->execute([$productId]);
$latestBatch = $batchStmt->fetch();

$batchMaterials = [];
$batchSuppliers = [];

if ($latestBatch) {
    $batchId = $latestBatch['batch_id'];

    $matStmt = $pdo->prepare("
        SELECT fm.material_name, fm._internal_code, bm.component_type
        FROM batch_materials bm
        JOIN factory_materials fm ON bm.factory_material_id = fm.id
        WHERE bm.batch_id = ?
    ");
    $matStmt->execute([$batchId]);
    $batchMaterials = $matStmt->fetchAll();

    foreach ($batchMaterials as &$mat) {
        $compStmt = $pdo->prepare("
            SELECT fiber_type, percentage, is_recycled
            FROM factory_material_compositions fmc
            JOIN factory_materials fm ON fmc.factory_material_id = fm.id
            WHERE fm.material_name = ?
        ");
        $compStmt->execute([$mat['material_name']]);
        $mat['compositions'] = $compStmt->fetchAll();
    }
    unset($mat);

    $bsStmt = $pdo->prepare("
        SELECT s.supplier_name, bs.production_stage, bs.country_of_origin
        FROM batch_suppliers bs
        JOIN suppliers s ON bs.supplier_id = s.id
        WHERE bs.batch_id = ?
    ");
    $bsStmt->execute([$batchId]);
    $batchSuppliers = $bsStmt->fetchAll();
}

// Gruppera varianter
$variantsByColor = [];
foreach ($variants as $v) {
    $color = $v['color_name'];
    if (!isset($variantsByColor[$color])) $variantsByColor[$color] = [];
    $variantsByColor[$color][] = $v['size'];
}

// Parse environmental footprint
$footprint = $sustainabilityInfo && $sustainabilityInfo['environmental_footprint']
    ? json_decode($sustainabilityInfo['environmental_footprint'], true)
    : null;

function showError($title, $message) {
    ?>
    <!DOCTYPE html>
    <html lang="sv">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>DPP - Fel</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-lg p-6 max-w-md text-center">
            <div class="text-red-500 text-5xl mb-4">⚠️</div>
            <h1 class="text-xl font-bold text-gray-800 mb-2"><?= htmlspecialchars($title) ?></h1>
            <p class="text-gray-600"><?= htmlspecialchars($message) ?></p>
        </div>
    </body>
    </html>
    <?php
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DPP - <?= htmlspecialchars($productData['product_name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .section { @apply bg-white rounded-lg shadow-md p-4 mb-4; }
        .section-title { @apply text-base font-bold mb-2 flex items-center gap-2; }
        .label { @apply text-gray-400 text-xs uppercase tracking-wide; }
        .value { @apply text-gray-800; }
    </style>
</head>
<body class="bg-gradient-to-b from-indigo-900 to-indigo-700 min-h-screen">
    <!-- Header -->
    <div class="bg-white/10 backdrop-blur text-white p-4 sticky top-0 z-10">
        <div class="max-w-lg mx-auto flex items-center gap-3">
            <?php if ($productData['logo_url']): ?>
            <img src="<?= htmlspecialchars($productData['logo_url']) ?>" alt="Logo" class="h-8 bg-white rounded p-1">
            <?php endif; ?>
            <div>
                <div class="font-bold"><?= htmlspecialchars($productData['brand_name']) ?></div>
                <div class="text-sm opacity-80">Digital Product Passport</div>
            </div>
        </div>
    </div>

    <div class="max-w-lg mx-auto p-4">
        <!-- Produkt Hero -->
        <div class="section text-center">
            <h1 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($productData['product_name']) ?></h1>
            <p class="text-xs text-gray-400 font-mono break-all"><?= htmlspecialchars($gs1DigitalLink) ?></p>
            <?php if ($productData['description']): ?>
            <p class="text-sm text-gray-600 mt-2"><?= htmlspecialchars($productData['description']) ?></p>
            <?php endif; ?>
        </div>

        <!-- Varianter -->
        <?php if ($variantsByColor): ?>
        <div class="section">
            <div class="section-title">👕 Tillgängliga varianter</div>
            <?php foreach ($variantsByColor as $color => $sizes): ?>
            <div class="flex items-center gap-2 text-sm py-1">
                <span class="font-medium text-gray-700"><?= htmlspecialchars($color) ?>:</span>
                <span class="text-gray-600"><?= implode(', ', $sizes) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Material -->
        <?php if ($batchMaterials): ?>
        <div class="section">
            <div class="section-title">🧵 Material</div>
            <?php foreach ($batchMaterials as $mat): ?>
            <div class="bg-gray-50 rounded p-2 mb-2">
                <div class="font-medium text-gray-800"><?= htmlspecialchars($mat['material_name']) ?></div>
                <?php if (!empty($mat['compositions'])): ?>
                <div class="text-sm text-gray-600">
                    <?php
                    $compStrings = array_map(function($c) {
                        $str = $c['fiber_type'] . ' ' . $c['percentage'] . '%';
                        if ($c['is_recycled']) $str .= ' ♻️';
                        return $str;
                    }, $mat['compositions']);
                    echo implode(', ', $compStrings);
                    ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Leverantörskedja -->
        <?php if ($batchSuppliers): ?>
        <div class="section">
            <div class="section-title">🏭 Tillverkad av</div>
            <?php foreach ($batchSuppliers as $bs): ?>
            <div class="flex items-center gap-2 text-sm py-1">
                <span class="font-medium"><?= htmlspecialchars($bs['supplier_name']) ?></span>
                <span class="text-gray-500">(<?= $bs['country_of_origin'] ?>)</span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Skötsel -->
        <?php if ($careInfo): ?>
        <div class="section">
            <div class="section-title">🧼 Skötsel</div>
            <p class="text-sm text-gray-700"><?= htmlspecialchars($careInfo['care_text']) ?></p>
            <?php if ($careInfo['safety_information']): ?>
            <p class="text-sm text-orange-600 mt-2">⚠️ <?= htmlspecialchars($careInfo['safety_information']) ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Certifieringar -->
        <?php if ($certifications): ?>
        <div class="section">
            <div class="section-title">✅ Certifieringar</div>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($certifications as $cert): ?>
                <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded">
                    <?= $cert['certification_name'] ?>
                    <?= $cert['certification_other'] ? ': ' . htmlspecialchars($cert['certification_other']) : '' ?>
                </span>
                <?php endforeach; ?>
            </div>
            <?php if ($chemicalCompliance): ?>
            <div class="mt-2 flex flex-wrap gap-2">
                <?php foreach ($chemicalCompliance as $chem): ?>
                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">
                    <?= htmlspecialchars($chem['compliance_standard']) ?>
                </span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Compliance -->
        <?php if ($complianceInfo): ?>
        <div class="section">
            <div class="section-title">📋 Substanser</div>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <div class="label">SVHC-substanser</div>
                    <div class="value"><?= $complianceInfo['contains_svhc'] ? '⚠️ Ja' : '✅ Nej' ?></div>
                </div>
                <div>
                    <div class="label">Mikrofiber</div>
                    <div class="value"><?= $complianceInfo['sheds_microfibers'] ? '⚠️ Avger' : '✅ Avger ej' ?></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Cirkularitet -->
        <?php if ($circularityInfo): ?>
        <div class="section">
            <div class="section-title">♻️ Återvinning & Retur</div>
            <?php if ($circularityInfo['recyclability']): ?>
            <p class="text-sm text-gray-700 mb-2"><?= htmlspecialchars($circularityInfo['recyclability']) ?></p>
            <?php endif; ?>
            <?php if ($circularityInfo['take_back_instructions']): ?>
            <p class="text-sm text-gray-600"><strong>Retur:</strong> <?= htmlspecialchars($circularityInfo['take_back_instructions']) ?></p>
            <?php endif; ?>
            <?php if ($circularityInfo['repair_instructions']): ?>
            <p class="text-sm text-gray-600"><strong>Reparation:</strong> <?= htmlspecialchars($circularityInfo['repair_instructions']) ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Miljöavtryck -->
        <?php if ($footprint): ?>
        <div class="section">
            <div class="section-title">🌱 Miljöavtryck</div>
            <div class="grid grid-cols-4 gap-2 text-center">
                <?php if (isset($footprint['co2_kg'])): ?>
                <div class="bg-teal-50 rounded p-2">
                    <div class="text-lg font-bold text-teal-700"><?= $footprint['co2_kg'] ?></div>
                    <div class="text-xs text-gray-500">kg CO₂</div>
                </div>
                <?php endif; ?>
                <?php if (isset($footprint['water_liters'])): ?>
                <div class="bg-blue-50 rounded p-2">
                    <div class="text-lg font-bold text-blue-700"><?= number_format($footprint['water_liters']) ?></div>
                    <div class="text-xs text-gray-500">L vatten</div>
                </div>
                <?php endif; ?>
                <?php if (isset($footprint['energy_mj'])): ?>
                <div class="bg-amber-50 rounded p-2">
                    <div class="text-lg font-bold text-amber-700"><?= $footprint['energy_mj'] ?></div>
                    <div class="text-xs text-gray-500">MJ</div>
                </div>
                <?php endif; ?>
                <?php if (isset($footprint['wash_cycles'])): ?>
                <div class="bg-purple-50 rounded p-2">
                    <div class="text-lg font-bold text-purple-700"><?= $footprint['wash_cycles'] ?></div>
                    <div class="text-xs text-gray-500">tvättar</div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Varumärke -->
        <div class="section">
            <div class="section-title">🏢 Om <?= htmlspecialchars($productData['brand_name']) ?></div>
            <?php if ($sustainabilityInfo && $sustainabilityInfo['brand_statement']): ?>
            <p class="text-sm text-gray-600 italic mb-2">"<?= htmlspecialchars($sustainabilityInfo['brand_statement']) ?>"</p>
            <?php endif; ?>
            <div class="text-xs text-gray-500">
                <p><strong>Handlare:</strong> <?= htmlspecialchars($productData['trader_name'] ?? $productData['brand_name']) ?></p>
                <?php if ($productData['trader_address']): ?>
                <p><?= htmlspecialchars($productData['trader_address']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center text-white/60 text-xs py-4">
            Digital Product Passport enligt EU ESPR<br>
            <span class="font-mono"><?= htmlspecialchars($gs1DigitalLink) ?></span>
        </div>
    </div>
</body>
</html>
