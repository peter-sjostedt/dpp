<?php
use FastRoute\RouteCollector;

return function (RouteCollector $r) {
    $r->addRoute('GET', '/api/dashboard/summary', ['App\Controllers\DashboardController', 'summary']);
};
