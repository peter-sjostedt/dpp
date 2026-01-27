<?php
require_once __DIR__ . '/../src/Config/Auth.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Auth;
use App\Config\Database;

Auth::requireLogin();

$db = Database::getInstance()->getConnection();
$message = '';
$messageType = '';

// Generate API key
function generateApiKey(string $prefix): string {
    return $prefix . '_' . bin2hex(random_bytes(16));
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'create_brand':
                $name = trim($_POST['brand_name'] ?? '');
                if (empty($name)) {
                    throw new Exception('Varumärkesnamn krävs');
                }
                $lei = trim($_POST['lei'] ?? '');
                $gs1 = trim($_POST['gs1_company_prefix'] ?? '');

                // Validate LEI if provided
                if ($lei && !preg_match('/^[A-Z0-9]{20}$/', $lei)) {
                    throw new Exception('Ogiltigt LEI-format. Måste vara exakt 20 alfanumeriska tecken (A-Z, 0-9).');
                }
                // Validate GS1 if provided
                if ($gs1 && !preg_match('/^[0-9]{6,12}$/', $gs1)) {
                    throw new Exception('Ogiltigt GS1 Company Prefix. Måste vara 6-12 siffror.');
                }

                $apiKey = generateApiKey('brand');
                $stmt = $db->prepare(
                    'INSERT INTO brands (brand_name, sub_brand, parent_company, trader_name, trader_address, lei, gs1_company_prefix, api_key, _is_active)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, TRUE)'
                );
                $stmt->execute([
                    $name,
                    $_POST['sub_brand'] ?: null,
                    $_POST['parent_company'] ?: null,
                    $_POST['trader_name'] ?: null,
                    $_POST['trader_address'] ?: null,
                    $lei ?: null,
                    $gs1 ?: null,
                    $apiKey
                ]);
                $message = "Brand '$name' skapad med API-nyckel: $apiKey";
                $messageType = 'success';
                break;

            case 'create_supplier':
                $name = trim($_POST['supplier_name'] ?? '');
                if (empty($name)) {
                    throw new Exception('Fabriksnamn krävs');
                }
                $lei = trim($_POST['lei'] ?? '');
                $gs1 = trim($_POST['gs1_company_prefix'] ?? '');

                // Validate LEI if provided
                if ($lei && !preg_match('/^[A-Z0-9]{20}$/', $lei)) {
                    throw new Exception('Ogiltigt LEI-format. Måste vara exakt 20 alfanumeriska tecken (A-Z, 0-9).');
                }
                // Validate GS1 if provided
                if ($gs1 && !preg_match('/^[0-9]{6,12}$/', $gs1)) {
                    throw new Exception('Ogiltigt GS1 Company Prefix. Måste vara 6-12 siffror.');
                }

                $apiKey = generateApiKey('supplier');
                $stmt = $db->prepare(
                    'INSERT INTO suppliers (supplier_name, supplier_location, facility_registry, facility_identifier, lei, gs1_company_prefix, api_key, _is_active)
                     VALUES (?, ?, ?, ?, ?, ?, ?, TRUE)'
                );
                $stmt->execute([
                    $name,
                    $_POST['supplier_location'] ?: null,
                    $_POST['facility_registry'] ?: null,
                    $_POST['facility_identifier'] ?: null,
                    $lei ?: null,
                    $gs1 ?: null,
                    $apiKey
                ]);
                $message = "Supplier '$name' skapad med API-nyckel: $apiKey";
                $messageType = 'success';
                break;

            case 'create_relation':
                $brandId = (int)$_POST['brand_id'];
                $supplierId = (int)$_POST['supplier_id'];
                if (!$brandId || !$supplierId) {
                    throw new Exception('Välj både brand och supplier');
                }
                $stmt = $db->prepare(
                    'INSERT IGNORE INTO brand_suppliers (brand_id, supplier_id, _is_active) VALUES (?, ?, TRUE)'
                );
                $stmt->execute([$brandId, $supplierId]);
                if ($stmt->rowCount() > 0) {
                    $message = "Relation skapad";
                    $messageType = 'success';
                } else {
                    $message = "Relationen finns redan";
                    $messageType = 'warning';
                }
                break;

            case 'toggle_relation':
                $relationId = (int)$_POST['relation_id'];
                $stmt = $db->prepare('UPDATE brand_suppliers SET _is_active = NOT _is_active WHERE id = ?');
                $stmt->execute([$relationId]);
                $message = "Relation uppdaterad";
                $messageType = 'success';
                break;

            case 'delete_relation':
                $relationId = (int)$_POST['relation_id'];
                $stmt = $db->prepare('DELETE FROM brand_suppliers WHERE id = ?');
                $stmt->execute([$relationId]);
                $message = "Relation borttagen";
                $messageType = 'success';
                break;

            case 'regenerate_key':
                $type = $_POST['tenant_type'];
                $id = (int)$_POST['tenant_id'];
                $prefix = $type === 'brand' ? 'brand' : 'supplier';
                $table = $type === 'brand' ? 'brands' : 'suppliers';
                $apiKey = generateApiKey($prefix);
                $stmt = $db->prepare("UPDATE $table SET api_key = ? WHERE id = ?");
                $stmt->execute([$apiKey, $id]);
                $message = "Ny API-nyckel genererad: $apiKey";
                $messageType = 'success';
                break;
        }
    } catch (Exception $e) {
        $message = 'Fel: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Fetch data
$brands = $db->query('SELECT * FROM brands ORDER BY brand_name')->fetchAll();
$suppliers = $db->query('SELECT * FROM suppliers ORDER BY supplier_name')->fetchAll();
$relations = $db->query(
    'SELECT bs.*, b.brand_name, s.supplier_name
     FROM brand_suppliers bs
     JOIN brands b ON bs.brand_id = b.id
     JOIN suppliers s ON bs.supplier_id = s.id
     ORDER BY b.brand_name, s.supplier_name'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Tenants - DPP</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .header { background: #37474f; color: white; padding: 20px; margin: -20px -20px 20px; }
        .header a { color: white; }
        .container { max-width: 1200px; margin: 0 auto; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 20px; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .card h2 { margin-top: 0; color: #37474f; border-bottom: 2px solid #37474f; padding-bottom: 10px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; color: white; margin-right: 5px; }
        .btn-primary { background: #1976d2; }
        .btn-success { background: #388e3c; }
        .btn-warning { background: #f57c00; }
        .btn-danger { background: #d32f2f; }
        .btn-small { padding: 5px 10px; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f5f5f5; }
        .api-key { font-family: monospace; font-size: 11px; background: #f5f5f5; padding: 2px 6px; border-radius: 3px; word-break: break-all; }
        .status-active { color: #388e3c; }
        .status-inactive { color: #d32f2f; }
        .message { padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .message.success { background: #e8f5e9; border: 1px solid #4caf50; color: #2e7d32; }
        .message.error { background: #ffebee; border: 1px solid #f44336; color: #c62828; }
        .message.warning { background: #fff3e0; border: 1px solid #ff9800; color: #e65100; }
        .copy-btn { background: #607d8b; padding: 2px 8px; font-size: 11px; cursor: pointer; border: none; color: white; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="header">
        <a href="test.php">&larr; Tillbaka till testpanelen</a>
        <h1>Admin - Hantera Tenants</h1>
        <p style="margin: 5px 0 0; opacity: 0.8;">Skapa och hantera brands, suppliers och relationer</p>
    </div>

    <div class="container">
        <?php if ($message): ?>
        <div class="message <?= $messageType ?>">
            <?= htmlspecialchars($message) ?>
            <?php if (strpos($message, 'API-nyckel:') !== false): ?>
            <button class="copy-btn" onclick="copyKey(this)" style="margin-left: 10px;">Kopiera nyckel</button>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="grid">
            <!-- Create Brand -->
            <div class="card">
                <h2>Skapa nytt Brand</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="create_brand">
                    <div class="form-group">
                        <label>Varumärkesnamn *</label>
                        <input type="text" name="brand_name" required>
                    </div>
                    <div class="form-group">
                        <label>Sub-brand</label>
                        <input type="text" name="sub_brand">
                    </div>
                    <div class="form-group">
                        <label>Parent Company</label>
                        <input type="text" name="parent_company">
                    </div>
                    <div class="form-group">
                        <label>Trader Name</label>
                        <input type="text" name="trader_name">
                    </div>
                    <div class="form-group">
                        <label>Trader Address</label>
                        <input type="text" name="trader_address">
                    </div>
                    <div class="form-group">
                        <label>LEI (Legal Entity Identifier)</label>
                        <input type="text" name="lei" pattern="[A-Z0-9]{20}" maxlength="20" placeholder="20 tecken, A-Z, 0-9">
                    </div>
                    <div class="form-group">
                        <label>GS1 Company Prefix</label>
                        <input type="text" name="gs1_company_prefix" pattern="[0-9]{6,12}" placeholder="6-12 siffror">
                    </div>
                    <button type="submit" class="btn-primary">Skapa Brand</button>
                </form>
            </div>

            <!-- Create Supplier -->
            <div class="card">
                <h2>Skapa ny Supplier</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="create_supplier">
                    <div class="form-group">
                        <label>Fabriksnamn *</label>
                        <input type="text" name="supplier_name" required>
                    </div>
                    <div class="form-group">
                        <label>Plats/Adress</label>
                        <input type="text" name="supplier_location">
                    </div>
                    <div class="form-group">
                        <label>Facility Registry</label>
                        <select name="facility_registry">
                            <option value="">-- Välj --</option>
                            <option value="GLN">GLN</option>
                            <option value="OSH">OSH</option>
                            <option value="OTHER">OTHER</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Facility Identifier</label>
                        <input type="text" name="facility_identifier">
                    </div>
                    <div class="form-group">
                        <label>LEI (Legal Entity Identifier)</label>
                        <input type="text" name="lei" pattern="[A-Z0-9]{20}" maxlength="20" placeholder="20 tecken, A-Z, 0-9">
                    </div>
                    <div class="form-group">
                        <label>GS1 Company Prefix</label>
                        <input type="text" name="gs1_company_prefix" pattern="[0-9]{6,12}" placeholder="6-12 siffror">
                    </div>
                    <button type="submit" class="btn-primary">Skapa Supplier</button>
                </form>
            </div>

            <!-- Create Relation -->
            <div class="card">
                <h2>Skapa Brand-Supplier Relation</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="create_relation">
                    <div class="form-group">
                        <label>Brand</label>
                        <select name="brand_id" required>
                            <option value="">-- Välj brand --</option>
                            <?php foreach ($brands as $b): ?>
                            <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['brand_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Supplier</label>
                        <select name="supplier_id" required>
                            <option value="">-- Välj supplier --</option>
                            <?php foreach ($suppliers as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['supplier_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-success">Skapa Relation</button>
                </form>
            </div>
        </div>

        <!-- Existing Brands -->
        <div class="card" style="margin-top: 20px;">
            <h2>Brands (<?= count($brands) ?>)</h2>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Namn</th>
                    <th>LEI</th>
                    <th>GS1 Prefix</th>
                    <th>API-nyckel</th>
                    <th>Status</th>
                    <th>Åtgärder</th>
                </tr>
                <?php foreach ($brands as $b): ?>
                <tr>
                    <td><?= $b['id'] ?></td>
                    <td><strong><?= htmlspecialchars($b['brand_name']) ?></strong></td>
                    <td><span class="api-key"><?= htmlspecialchars($b['lei'] ?? '-') ?></span></td>
                    <td><?= htmlspecialchars($b['gs1_company_prefix'] ?? '-') ?></td>
                    <td><span class="api-key"><?= htmlspecialchars($b['api_key']) ?></span></td>
                    <td class="<?= $b['_is_active'] ? 'status-active' : 'status-inactive' ?>">
                        <?= $b['_is_active'] ? 'Aktiv' : 'Inaktiv' ?>
                    </td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="regenerate_key">
                            <input type="hidden" name="tenant_type" value="brand">
                            <input type="hidden" name="tenant_id" value="<?= $b['id'] ?>">
                            <button type="submit" class="btn-warning btn-small" onclick="return confirm('Generera ny API-nyckel? Den gamla slutar fungera!')">Ny nyckel</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- Existing Suppliers -->
        <div class="card" style="margin-top: 20px;">
            <h2>Suppliers (<?= count($suppliers) ?>)</h2>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Namn</th>
                    <th>LEI</th>
                    <th>GS1 Prefix</th>
                    <th>API-nyckel</th>
                    <th>Status</th>
                    <th>Åtgärder</th>
                </tr>
                <?php foreach ($suppliers as $s): ?>
                <tr>
                    <td><?= $s['id'] ?></td>
                    <td><strong><?= htmlspecialchars($s['supplier_name']) ?></strong><br><small style="color:#666"><?= htmlspecialchars($s['supplier_location'] ?? '-') ?></small></td>
                    <td><span class="api-key"><?= htmlspecialchars($s['lei'] ?? '-') ?></span></td>
                    <td><?= htmlspecialchars($s['gs1_company_prefix'] ?? '-') ?></td>
                    <td><span class="api-key"><?= htmlspecialchars($s['api_key']) ?></span></td>
                    <td class="<?= $s['_is_active'] ? 'status-active' : 'status-inactive' ?>">
                        <?= $s['_is_active'] ? 'Aktiv' : 'Inaktiv' ?>
                    </td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="regenerate_key">
                            <input type="hidden" name="tenant_type" value="supplier">
                            <input type="hidden" name="tenant_id" value="<?= $s['id'] ?>">
                            <button type="submit" class="btn-warning btn-small" onclick="return confirm('Generera ny API-nyckel? Den gamla slutar fungera!')">Ny nyckel</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- Existing Relations -->
        <div class="card" style="margin-top: 20px;">
            <h2>Brand-Supplier Relationer (<?= count($relations) ?>)</h2>
            <table>
                <tr>
                    <th>Brand</th>
                    <th>Supplier</th>
                    <th>Status</th>
                    <th>Skapad</th>
                    <th>Åtgärder</th>
                </tr>
                <?php foreach ($relations as $r): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($r['brand_name']) ?></strong></td>
                    <td><?= htmlspecialchars($r['supplier_name']) ?></td>
                    <td class="<?= $r['_is_active'] ? 'status-active' : 'status-inactive' ?>">
                        <?= $r['_is_active'] ? 'Aktiv' : 'Inaktiv' ?>
                    </td>
                    <td><?= $r['created_at'] ?></td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="toggle_relation">
                            <input type="hidden" name="relation_id" value="<?= $r['id'] ?>">
                            <button type="submit" class="btn-warning btn-small">
                                <?= $r['_is_active'] ? 'Inaktivera' : 'Aktivera' ?>
                            </button>
                        </form>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="delete_relation">
                            <input type="hidden" name="relation_id" value="<?= $r['id'] ?>">
                            <button type="submit" class="btn-danger btn-small" onclick="return confirm('Ta bort relationen?')">Ta bort</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

    <script>
        function copyKey(btn) {
            const text = btn.parentElement.textContent;
            const match = text.match(/API-nyckel: ([^\s]+)/);
            if (match) {
                navigator.clipboard.writeText(match[1].trim());
                btn.textContent = 'Kopierad!';
                setTimeout(() => btn.textContent = 'Kopiera nyckel', 2000);
            }
        }
    </script>
</body>
</html>
