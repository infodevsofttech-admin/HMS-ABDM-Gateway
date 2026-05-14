<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Public entry points
$routes->get('/', 'Auth::hospitalLogin');
$routes->post('/', 'Auth::hospitalLogin');
$routes->get('admin', 'Auth::adminLogin');
$routes->post('admin', 'Auth::adminLogin');

// Bridge ingress endpoint for HMS queue dispatch
$routes->post('api/v1/bridge', 'AbdmGateway::bridgeDispatch');

// ==================== ABDM Gateway API Routes ====================

$routes->group('api/v3', static function($routes) {
    
    // Health Check (Public)
    $routes->get('health', 'AbdmGateway::health');
    
    // ABHA Validation
    $routes->post('abha/validate', 'AbdmGateway::abhaValidate');

    // ABDM M1 ABHA OTP Flows
    $routes->post('abha/aadhaar/generate-otp', 'AbdmGateway::abhaAadhaarGenerateOtp');
    $routes->post('abha/aadhaar/verify-otp', 'AbdmGateway::abhaAadhaarVerifyOtp');
    $routes->post('abha/mobile/generate-otp', 'AbdmGateway::abhaMobileGenerateOtp');
    $routes->post('abha/mobile/verify-otp', 'AbdmGateway::abhaMobileVerifyOtp');
    
    // Consent Operations
    $routes->post('consent/request', 'AbdmGateway::consentRequest');
    
    // Bundle Operations
    $routes->post('bundle/push', 'AbdmGateway::bundlePush');
    
    // SNOMED Search
    $routes->get('snomed/search', 'AbdmGateway::snomedSearch');
    
    // Gateway Status
    $routes->get('gateway/status', 'AbdmGateway::gatewayStatus');
});

// ==================== Authentication Routes ====================

$routes->group('auth', static function($routes) {
    $routes->get('login', 'Auth::login');
    $routes->post('login', 'Auth::login');
    $routes->get('register', 'Auth::register');
    $routes->post('register', 'Auth::register');
    $routes->get('logout', 'Auth::logout');
});

// ==================== Admin Routes (Protected) ====================

$routes->group('admin', ['filter' => 'auth'], static function($routes) {
        // M1 Suite UI
        $routes->get('m1', 'Admin::m1Index');
        $routes->head('m1', 'Admin::m1Index');
        $routes->get('m1/abha-validate', 'Admin::m1AbhaValidate');
        $routes->head('m1/abha-validate', 'Admin::m1AbhaValidate');
        $routes->post('m1/abha-validate', 'Admin::m1AbhaValidatePost');
        $routes->post('m1/abha-validate-otp', 'Admin::m1AbhaValidateOtp');
        $routes->get('m1/otp-flow', 'Admin::m1OtpFlow');
        $routes->post('m1/otp-generate', 'Admin::m1OtpGeneratePost');
        $routes->post('m1/otp-verify', 'Admin::m1OtpVerifyPost');
        $routes->get('m1/abha-profiles', 'Admin::m1AbhaProfiles');
        $routes->head('m1/abha-profiles', 'Admin::m1AbhaProfiles');
        $routes->get('m1/fetch-token', 'Admin::fetchAbdmToken');
    $routes->get('dashboard', 'Admin::dashboard');
    $routes->get('hospitals', 'Admin::hospitals');
    $routes->post('hospitals/create', 'Admin::createHospital');
    $routes->post('hospitals/(:num)/mode', 'Admin::updateHospitalMode/$1');
    $routes->get('users', 'Admin::users');
    $routes->post('users/create', 'Admin::createUser');
    $routes->get('m1-module', 'Admin::m1Module');
    $routes->post('m1-module/test', 'Admin::runM1Test');
    $routes->get('m1-module/export', 'Admin::exportM1Logs');
    $routes->get('test-logs', 'Admin::testSubmissionLogs');
    $routes->get('logs', 'Admin::requestLogs');
    $routes->get('audit', 'Admin::auditTrail');
    $routes->get('bundles', 'Admin::bundles');
    
    // HMS Access Management
    $routes->get('hms-access', 'Admin::hmsAccess');
    $routes->post('hms-credential/create', 'Admin::createHmsCredential');
    $routes->get('hms-credential/(:num)', 'Admin::hmsCredentialDetail/$1');
    $routes->post('hms-credential/(:num)/update', 'Admin::updateHmsCredential/$1');
    $routes->post('hms-credential/(:num)/test', 'Admin::testHmsCredential/$1');
    $routes->post('hms-credential/(:num)/delete', 'Admin::deleteHmsCredential/$1');
});

// ==================== Catch-all ====================

// Use default CI4 404 handler; custom Home::notFound is not present in gateway app.
