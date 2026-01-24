<?php
use FastRoute\RouteCollector;

return function (RouteCollector $r) {
    $r->addRoute('GET', '/api/companies', ['App\Controllers\CompanyController', 'index']);
    $r->addRoute('POST', '/api/companies', ['App\Controllers\CompanyController', 'create']);
    $r->addRoute('GET', '/api/companies/{id:\d+}', ['App\Controllers\CompanyController', 'show']);
    $r->addRoute('PUT', '/api/companies/{id:\d+}', ['App\Controllers\CompanyController', 'update']);
    $r->addRoute('DELETE', '/api/companies/{id:\d+}', ['App\Controllers\CompanyController', 'delete']);
};
