<?php
/**
 * DPP API Entry Point
 *
 * Alla /api/* requests går via denna fil.
 * Admin-API: X-Admin-Key header
 * Tenant-API: X-API-Key header
 */
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use FastRoute\RouteCollector;
use App\Config\TenantContext;
use App\Config\AdminAuth;
use App\Helpers\Response;

// CORS headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key, X-Admin-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// ============================================
// PARSE URI (needed for auth check)
// ============================================
if (isset($_GET['route'])) {
    $uri = $_GET['route'];
} else {
    $uri = $_SERVER['REQUEST_URI'];
    if (false !== $pos = strpos($uri, '?')) {
        $uri = substr($uri, 0, $pos);
    }
    $uri = rawurldecode($uri);
}

// ============================================
// AUTENTISERING
// ============================================
// Admin routes: require X-Admin-Key
if (preg_match('#^/api/admin/#', $uri)) {
    AdminAuth::authenticate();
}
// Tenant list endpoints: no auth required (test panel use only)
elseif (preg_match('#^/api/tenants/#', $uri)) {
    // Skip authentication
}
// All other routes: require X-API-Key (tenant auth)
else {
    TenantContext::authenticate();
}

// ============================================
// ROUTING
// ============================================
$dispatcher = FastRoute\simpleDispatcher(function(RouteCollector $r) {
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

// Dispatch request
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
