<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use FastRoute\RouteCollector;
use App\Helpers\Response;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Load routes from separate files
$dispatcher = FastRoute\simpleDispatcher(function(RouteCollector $r) {
    // Homepage
    $r->addRoute('GET', '/', function() {
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>DPP API</title>
        <style>body{font-family:Arial;padding:40px;background:#1a237e;color:white;text-align:center}
        a{display:inline-block;margin:20px;padding:15px 30px;background:#4CAF50;color:white;text-decoration:none;border-radius:8px}
        a:hover{background:#45a049}</style></head>
        <body><h1>DPP Platform API</h1><p>Digital Product Passport</p>
        <a href="/testpanel/test.php">Testpanel</a><a href="/api/companies">API Status</a></body></html>';
        exit;
    });

    // Load all route files from routes/api/
    $routesDir = realpath(__DIR__ . '/../routes/api');
    if ($routesDir) {
        $routeFiles = glob($routesDir . DIRECTORY_SEPARATOR . '*.php');
        foreach ($routeFiles as $routeFile) {
            $routeDefinition = require $routeFile;
            if (is_callable($routeDefinition)) {
                $routeDefinition($r);
            }
        }
    }
});

// Dispatch - supports both ?route= and REQUEST_URI
if (isset($_GET['route'])) {
    $uri = $_GET['route'];
} else {
    $uri = $_SERVER['REQUEST_URI'];
    if (false !== $pos = strpos($uri, '?')) {
        $uri = substr($uri, 0, $pos);
    }
    $uri = rawurldecode($uri);
}

$routeInfo = $dispatcher->dispatch($_SERVER['REQUEST_METHOD'], $uri);

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        Response::json(['error' => 'Not found'], 404);
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        Response::json(['error' => 'Method not allowed'], 405);
        break;
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];

        try {
            if (is_callable($handler)) {
                $handler($vars);
            } else {
                [$class, $method] = $handler;
                $controller = new $class();
                $controller->$method($vars);
            }
        } catch (\PDOException $e) {
            Response::json(['error' => 'Database error: ' . $e->getMessage()], 500);
        } catch (\Throwable $e) {
            Response::json(['error' => $e->getMessage()], 500);
        }
        break;
}
