<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

/*
 * --------------------------------------------------------------------
 * HMS-ABDM Gateway API Routes
 * --------------------------------------------------------------------
 *
 * All API routes are prefixed with /api/v1.
 * Routes under /sync/* and /claims/* require the 'apiauth' filter.
 */

// Authentication – no token required
$routes->group('api/v1', static function ($routes) {
    $routes->post('auth/login', 'AuthController::login');

    // Sync endpoints – require valid API token
    $routes->group('sync', ['filter' => 'apiauth'], static function ($routes) {
        $routes->post('hospital',  'SyncController::hospital');
        $routes->post('doctor',    'SyncController::doctor');
        $routes->post('patient',   'SyncController::patient');
        $routes->post('opd',       'SyncController::opd');
        $routes->post('ipd',       'SyncController::ipd');
        $routes->post('pathlab',   'SyncController::pathlab');
        $routes->post('radiology', 'SyncController::radiology');
        $routes->post('pharmacy',  'SyncController::pharmacy');
    });

    // Insurance claim endpoints – require valid API token
    $routes->group('claims', ['filter' => 'apiauth'], static function ($routes) {
        $routes->post('submit', 'ClaimController::submit');
        $routes->get('status/(:num)', 'ClaimController::status/$1');
    });
});
