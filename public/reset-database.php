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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm']) && $_POST['confirm'] === 'RESET') {
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

        // Inaktivera foreign key checks för att kunna droppa tabeller
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

        // Schema-filer i rätt ordning
        $schemaFiles = [
            '01_foundation.sql',
            '02_products_batches.sql',
            '03_care_compliance_export.sql',
            '04_testdata.sql',
            '05_testdata_continued.sql',
            '06_testdata_items.sql'
        ];

        $schemaDir = __DIR__ . '/../database/schema/';

        foreach ($schemaFiles as $file) {
            $filePath = $schemaDir . $file;
            if (file_exists($filePath)) {
                $sql = file_get_contents($filePath);

                // Ta bort USE-statement (vi är redan anslutna till rätt databas)
                $sql = preg_replace('/^USE\s+\w+;\s*/mi', '', $sql);

                // Ta bort SET FOREIGN_KEY_CHECKS (vi hanterar detta i reset-scriptet)
                $sql = preg_replace('/SET\s+FOREIGN_KEY_CHECKS\s*=\s*[01]\s*;/i', '', $sql);

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
                        // Använd query() för alla statements och konsumera alltid resultatet
                        $result = $pdo->query($stmt);
                        if ($result !== false) {
                            $result->closeCursor();
                        }
                    }
                }
            }
        }

        // Återaktivera foreign key checks
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

        $success = true;
        $message = 'Databasen har återställts med all testdata!';

        // Hämta statistik
        $stats = [];
        $tables = ['companies', 'brands', 'suppliers', 'factory_materials', 'products', 'product_variants', 'batches', 'items'];
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
        $message = 'Fel vid återställning: ' . $e->getMessage();
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
        .header { background: #c62828; color: white; padding: 20px; margin: -20px -20px 20px; }
        .header a { color: white; }
        .container { max-width: 600px; margin: 0 auto; }
        .card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .warning { background: #fff3e0; border: 2px solid #ff9800; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .warning h3 { color: #e65100; margin-top: 0; }
        .success { background: #e8f5e9; border: 2px solid #4caf50; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .success h3 { color: #2e7d32; margin-top: 0; }
        .error { background: #ffebee; border: 2px solid #f44336; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .error h3 { color: #c62828; margin-top: 0; }
        input[type="text"] { padding: 12px; font-size: 18px; width: 200px; border: 2px solid #ddd; border-radius: 4px; text-align: center; }
        button { padding: 12px 30px; font-size: 16px; background: #c62828; color: white; border: none; border-radius: 4px; cursor: pointer; margin-top: 10px; }
        button:hover { background: #b71c1c; }
        .stats { margin-top: 20px; }
        .stats table { width: 100%; border-collapse: collapse; }
        .stats td, .stats th { padding: 8px 12px; text-align: left; border-bottom: 1px solid #eee; }
        .stats th { background: #f5f5f5; }
        .stats td:last-child { text-align: right; font-weight: bold; }
        .back-link { display: inline-block; margin-top: 20px; color: #1976d2; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="header">
        <a href="test.php">&larr; Tillbaka</a>
        <h1>Återställ Databas</h1>
        <p style="margin: 5px 0 0; opacity: 0.8;">Återställ alla tabeller med testdata</p>
    </div>

    <div class="container">
        <div class="card">
            <?php if ($success): ?>
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

            <?php elseif ($message): ?>
                <div class="error">
                    <h3>Fel!</h3>
                    <p><?= htmlspecialchars($message) ?></p>
                </div>
                <a href="reset-database.php" class="back-link">Försök igen</a>

            <?php else: ?>
                <div class="warning">
                    <h3>Varning!</h3>
                    <p>Detta kommer att <strong>radera all befintlig data</strong> och ersätta den med testdata.</p>
                    <p>Alla tabeller kommer att återskapas från schema-filerna.</p>
                </div>

                <form method="POST">
                    <p>Skriv <strong>RESET</strong> för att bekräfta:</p>
                    <input type="text" name="confirm" placeholder="RESET" autocomplete="off" required>
                    <br>
                    <button type="submit">Återställ databas</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
