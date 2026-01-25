<?php // Test panel - huvudmeny ?>
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
        a { display: block; padding: 30px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 8px; font-size: 18px; }
        a:hover { background: #45a049; }
    </style>
</head>
<body>
    <h1>DPP API Test Panel</h1>
    <p>Välj en tabell att testa</p>

    <div class="grid">
        <a href="test-companies.php">Companies</a>
        <a href="test-brands.php">Brands</a>
        <a href="test-products.php">Products</a>
        <a href="test-variants.php">Variants</a>
        <a href="test-batches.php">Batches</a>
        <a href="test-items.php">Items</a>
        <a href="test-suppliers.php">Suppliers</a>
        <a href="test-materials.php">Materials</a>
        <a href="docs/dataflow.html" style="background: #2196F3;">Dataflöde</a>
    </div>
</body>
</html>
