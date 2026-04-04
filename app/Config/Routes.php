<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

/*
 * HMS-ABDM Gateway API Routes
 * All sync endpoints accept JSON POST bodies.
 * Requests must carry a valid X-API-Key header (obtain from the admin panel).
 */
$routes->group('sync', ['filter' => ['cors', 'apikey']], static function (RouteCollection $routes): void {
    // Hospital registration in HFR
    $routes->post('hospital', 'Hospital::register');

    // Doctor registration in HPR
    $routes->post('doctor', 'Doctor::register');

    // Patient – create ABHA ID
    $routes->post('patient', 'Patient::createAbha');

    // Health records
    $routes->group('records', static function (RouteCollection $routes): void {
        $routes->post('opd',       'Records::pushOpd');
        $routes->post('ipd',       'Records::pushIpd');
        $routes->post('lab',       'Records::pushLab');
        $routes->post('radiology', 'Records::pushRadiology');
        $routes->post('pharmacy',  'Records::pushPharmacy');
    });
});

/*
 * Admin Panel Routes
 * Protected by session-based login (GATEWAY_ADMIN_TOKEN in .env).
 */
$routes->group('admin', ['filter' => ['adminauth', 'csrf']], static function (RouteCollection $routes): void {
    $routes->get('/',              'Admin::index');
    $routes->get('register',       'Admin::showRegisterForm');
    $routes->post('register',      'Admin::registerHospital');
    $routes->get('regenerate/(:num)',  'Admin::regenerateKey/$1');
    $routes->get('toggle/(:num)',      'Admin::toggleActive/$1');
    $routes->get('delete/(:num)',      'Admin::delete/$1');
});

// Admin login / logout (no auth filter)
$routes->get('admin/login',  'Admin::login');
$routes->post('admin/login', 'Admin::doLogin');
$routes->get('admin/logout', 'Admin::logout');

