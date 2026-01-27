<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../src/Config/Auth.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Auth;
use App\Config\Database;

Auth::requireLogin();

$message = '';
$success = false;
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    try {
        // Skapa egen PDO-anslutning med buffrade queries för reset-scriptet
        $pdo = new PDO(
            'mysql:host=localhost;dbname=petersjo_dpp;charset=utf8mb4',
            'petersjo_hospitex',
            'k)6ZPqh%8jZNdPx+',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            ]
        );

        // Inaktivera foreign key checks
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

        $sqlFiles = [];

        if ($action === 'reset_schema' && $_POST['confirm'] === 'RESET') {
            // Cleanup: Drop legacy/orphan tables first
            $legacyTables = [
                'compliance_information',
                'circularity_information',
                'sustainability_information',
                'certifications',
                'chemical_compliance',
                'component_materials',
                'users',
                'companies'
            ];
            foreach ($legacyTables as $table) {
                $pdo->exec("DROP TABLE IF EXISTS $table");
            }

            // Schema files only
            $sqlFiles = [
                __DIR__ . '/../database/schema/01_foundation.sql',
                __DIR__ . '/../database/schema/02_products_batches.sql',
                __DIR__ . '/../database/schema/03_care_compliance.sql',
            ];
            $successMessage = 'Schemat har återställts! Alla tabeller är tomma.';

        } elseif ($action === 'load_testdata' && $_POST['confirm'] === 'LOAD') {
            // Testdata only
            $sqlFiles = [
                __DIR__ . '/../database/testdata/healthcare_textiles.sql',
            ];
            $successMessage = 'Testdata har laddats!';

        } elseif ($action === 'reset_all' && $_POST['confirm'] === 'RESET') {
            // Cleanup: Drop legacy/orphan tables first
            $legacyTables = [
                'compliance_information',
                'circularity_information',
                'sustainability_information',
                'certifications',
                'chemical_compliance',
                'component_materials',
                'users',
                'companies'
            ];
            foreach ($legacyTables as $table) {
                $pdo->exec("DROP TABLE IF EXISTS $table");
            }

            // Both schema and testdata
            $sqlFiles = [
                __DIR__ . '/../database/schema/01_foundation.sql',
                __DIR__ . '/../database/schema/02_products_batches.sql',
                __DIR__ . '/../database/schema/03_care_compliance.sql',
                __DIR__ . '/../database/testdata/healthcare_textiles.sql',
            ];
            $successMessage = 'Databasen har återställts med all testdata!';

        } else {
            throw new Exception('Ogiltig åtgärd eller felaktig bekräftelse.');
        }

        foreach ($sqlFiles as $sqlFile) {
            if (!file_exists($sqlFile)) {
                throw new Exception('SQL-fil saknas: ' . $sqlFile);
            }

            $sql = file_get_contents($sqlFile);

            // Ta bort USE-statement (vi är redan anslutna till rätt databas)
            $sql = preg_replace('/^USE\s+\w+;\s*/mi', '', $sql);

            // Ta bort SOURCE-kommandon (hanteras via PHP)
            $sql = preg_replace('/^SOURCE\s+.*$/mi', '', $sql);

            // Kör varje statement separat
            $statements = array_filter(
                array_map('trim', explode(';', $sql)),
                function($s) {
                    if (empty($s)) return false;
                    // Ta bort kommentarsrader för att kolla om det finns riktig SQL
                    $withoutComments = preg_replace('/^--.*$/m', '', $s);
                    $withoutComments = trim($withoutComments);
                    return !empty($withoutComments);
                }
            );

            foreach ($statements as $statement) {
                $stmt = trim($statement);
                if (!empty($stmt)) {
                    $result = $pdo->query($stmt);
                    if ($result !== false) {
                        $result->closeCursor();
                    }
                }
            }
        }

        // Återaktivera foreign key checks
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

        $success = true;
        $message = $successMessage;

        // Hämta statistik
        $stats = [];
        $tables = ['brands', 'suppliers', 'brand_suppliers', 'factory_materials', 'products', 'product_variants', 'batches', 'items'];
        foreach ($tables as $table) {
            $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            $stats[$table] = $count;
        }

    } catch (Exception $e) {
        // Återaktivera foreign key checks även vid fel
        try {
            $cleanupPdo = new PDO(
                'mysql:host=localhost;dbname=petersjo_dpp;charset=utf8mb4',
                'petersjo_hospitex',
                'k)6ZPqh%8jZNdPx+',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $cleanupPdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        } catch (Exception $ignored) {
            // Ignorera fel vid cleanup
        }
        $message = 'Fel vid åtgärd: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Återställ Databas - DPP</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .header { background: #37474f; color: white; padding: 20px; margin: -20px -20px 20px; }
        .header a { color: white; }
        .container { max-width: 800px; margin: 0 auto; }
        .card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card h3 { margin-top: 0; }
        .warning { background: #fff3e0; border-left: 4px solid #ff9800; padding: 15px; margin-bottom: 15px; }
        .info { background: #e3f2fd; border-left: 4px solid #2196f3; padding: 15px; margin-bottom: 15px; }
        .success { background: #e8f5e9; border: 2px solid #4caf50; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .success h3 { color: #2e7d32; margin-top: 0; }
        .error { background: #ffebee; border: 2px solid #f44336; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .error h3 { color: #c62828; margin-top: 0; }
        input[type="text"] { padding: 10px; font-size: 16px; width: 120px; border: 2px solid #ddd; border-radius: 4px; text-align: center; }
        button { padding: 12px 25px; font-size: 15px; color: white; border: none; border-radius: 4px; cursor: pointer; margin-top: 10px; }
        button:hover { opacity: 0.9; }
        .btn-danger { background: #c62828; }
        .btn-warning { background: #ff9800; }
        .btn-primary { background: #1976d2; }
        .stats { margin-top: 20px; }
        .stats table { width: 100%; border-collapse: collapse; }
        .stats td, .stats th { padding: 8px 12px; text-align: left; border-bottom: 1px solid #eee; }
        .stats th { background: #f5f5f5; }
        .stats td:last-child { text-align: right; font-weight: bold; }
        .back-link { display: inline-block; margin-top: 20px; color: #1976d2; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
        .actions { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 600px) { .actions { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="header">
        <a href="test.php">&larr; Tillbaka</a>
        <h1>Databashantering</h1>
        <p style="margin: 5px 0 0; opacity: 0.8;">Återställ schema och/eller ladda testdata</p>
    </div>

    <div class="container">
        <?php if ($success): ?>
            <div class="card">
                <div class="success">
                    <h3>Klart!</h3>
                    <p><?= htmlspecialchars($message) ?></p>
                </div>

                <div class="stats">
                    <h4>Databasstatistik:</h4>
                    <table>
                        <tr><th>Tabell</th><th>Antal rader</th></tr>
                        <?php foreach ($stats as $table => $count): ?>
                        <tr><td><?= $table ?></td><td><?= $count ?></td></tr>
                        <?php endforeach; ?>
                    </table>
                </div>

                <a href="test.php" class="back-link">&larr; Tillbaka till testpanelen</a>
            </div>

        <?php elseif ($message): ?>
            <div class="card">
                <div class="error">
                    <h3>Fel!</h3>
                    <p><?= htmlspecialchars($message) ?></p>
                </div>
                <a href="reset-database.php" class="back-link">Försök igen</a>
            </div>

        <?php else: ?>
            <div class="actions">
                <!-- Reset Schema Only -->
                <div class="card">
                    <h3>1. Återställ Schema</h3>
                    <div class="warning">
                        <strong>Raderar all data!</strong><br>
                        Droppar alla tabeller (inkl. gamla/oanvända) och skapar tomma tabeller.
                    </div>
                    <form method="POST">
                        <input type="hidden" name="action" value="reset_schema">
                        <p>Skriv <strong>RESET</strong> för att bekräfta:</p>
                        <input type="text" name="confirm" placeholder="RESET" autocomplete="off" required>
                        <br>
                        <button type="submit" class="btn-danger">Återställ Schema</button>
                    </form>
                </div>

                <!-- Load Testdata Only -->
                <div class="card">
                    <h3>2. Ladda Testdata</h3>
                    <div class="info">
                        <strong>Lägger till data</strong><br>
                        Laddar healthcare textiles testdata i befintliga tabeller.
                    </div>
                    <form method="POST">
                        <input type="hidden" name="action" value="load_testdata">
                        <p>Skriv <strong>LOAD</strong> för att bekräfta:</p>
                        <input type="text" name="confirm" placeholder="LOAD" autocomplete="off" required>
                        <br>
                        <button type="submit" class="btn-primary">Ladda Testdata</button>
                    </form>
                </div>
            </div>

            <!-- Reset All -->
            <div class="card" style="margin-top: 20px;">
                <h3>Komplett Återställning</h3>
                <div class="warning">
                    <strong>Gör båda stegen ovan i ett!</strong><br>
                    Återställer schemat och laddar testdata direkt.
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="reset_all">
                    <p>Skriv <strong>RESET</strong> för att bekräfta:</p>
                    <input type="text" name="confirm" placeholder="RESET" autocomplete="off" required>
                    <br>
                    <button type="submit" class="btn-warning">Återställ Allt</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
