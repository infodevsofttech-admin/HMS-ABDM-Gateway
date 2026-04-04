<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

/*
 * HMS-ABDM Gateway API Routes
 * All sync endpoints accept JSON POST bodies.
 */
$routes->group('sync', ['filter' => 'cors'], static function (RouteCollection $routes): void {
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

