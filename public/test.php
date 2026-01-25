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
    </style>
</head>
<body>
    <div style="position: absolute; top: 20px; right: 20px;">
        <a href="logout.php" style="background: #ef4444; padding: 10px 20px; font-size: 14px;">Logga ut</a>
    </div>
    <h1>DPP API Test Panel</h1>
    <p>Välj en tabell att testa</p>

    <!-- Master Data -->
    <p style="margin-top: 30px; opacity: 0.7; font-size: 14px;">MASTER DATA</p>
    <div class="grid">
        <a href="test-companies.php" style="background: #2E7D32;">1. Companies<small>♻️ Registrera en gång</small></a>
        <a href="test-brands.php" style="background: #2E7D32;">2. Brands<small>♻️ Registrera en gång</small></a>
        <a href="test-suppliers.php" style="background: #2E7D32;">3. Suppliers<small>♻️ Registrera en gång</small></a>
        <a href="test-materials.php" style="background: #1565C0;">4. Materials<small>♻️ Registrera en gång</small></a>
    </div>

    <!-- Products -->
    <p style="margin-top: 30px; opacity: 0.7; font-size: 14px;">PRODUKTER</p>
    <div class="grid">
        <a href="test-products.php" style="background: #7B1FA2;">5. Products<small>♻️ Registrera en gång</small></a>
        <a href="test-variants.php" style="background: #7B1FA2;">6. Variants<small>♻️ Registrera en gång</small></a>
    </div>

    <!-- Production -->
    <p style="margin-top: 30px; opacity: 0.7; font-size: 14px;">PRODUKTION</p>
    <div class="grid">
        <a href="test-batches.php" style="background: #E65100;">7. Batches<small>🆕 Per produktionsorder</small></a>
        <a href="test-items.php" style="background: #C62828;">8. Items (Fas 2)<small>🆕 Per plagg</small></a>
    </div>

    <!-- DPP Output -->
    <p style="margin-top: 30px; opacity: 0.7; font-size: 14px;">DPP OUTPUT</p>
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
        <a href="reset-database.php" style="background: #b71c1c;">Återställ testdata<small>Radera allt och ladda om testdata</small></a>
    </div>
</body>
</html>
