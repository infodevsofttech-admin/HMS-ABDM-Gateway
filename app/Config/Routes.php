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

// Public API documentation (no auth required)
$routes->get('api-docs', 'Hospital::apiDocsPublic');

// Hospital portal (protected)
$routes->get('dashboard',              'Hospital::dashboard',            ['filter' => 'auth']);
$routes->get('portal/abha-tools',      'Hospital::abhaTools',             ['filter' => 'auth']);
$routes->post('portal/abha/validate',  'Hospital::abhaValidatePost',      ['filter' => 'auth']);
$routes->post('portal/abha/otp-gen',   'Hospital::abhaOtpGeneratePost',   ['filter' => 'auth']);
$routes->post('portal/abha/otp-verify','Hospital::abhaOtpVerifyPost',     ['filter' => 'auth']);
$routes->get('portal/opd-queue',       'Hospital::opdQueue',              ['filter' => 'auth']);
$routes->post('portal/opd-queue/add',  'Hospital::opdQueueCreatePost',    ['filter' => 'auth']);
$routes->post('portal/opd-queue/status','Hospital::opdQueueUpdateStatusPost',['filter' => 'auth']);
$routes->get('portal/patients',        'Hospital::patients',              ['filter' => 'auth']);
$routes->get('portal/patient-card',    'Hospital::patientAbhaCard',       ['filter' => 'auth']);
$routes->get('portal/facility-qr',    'Hospital::facilityQr',            ['filter' => 'auth']);
$routes->post('portal/facility-qr/upload', 'Hospital::facilityQrUpload',  ['filter' => 'auth']);
$routes->get('portal/reports',         'Hospital::reports',               ['filter' => 'auth']);
$routes->get('portal/api-docs',         'Hospital::apiDocs',               ['filter' => 'auth']);
$routes->get('portal/profile',         'Hospital::profile',               ['filter' => 'auth']);
$routes->post('portal/profile/change-password', 'Hospital::changePasswordPost', ['filter' => 'auth']);
$routes->get('portal/tickets',          'Hospital::tickets',               ['filter' => 'auth']);
$routes->get('portal/tickets/new',      'Hospital::ticketNew',             ['filter' => 'auth']);
$routes->post('portal/tickets/new',     'Hospital::ticketNewPost',         ['filter' => 'auth']);
$routes->get('portal/tickets/(:num)',   'Hospital::ticketView/$1',         ['filter' => 'auth']);
$routes->post('portal/tickets/(:num)/reply','Hospital::ticketReplyPost/$1',['filter' => 'auth']);
$routes->post('portal/tickets/(:num)/close','Hospital::ticketClosePost/$1',  ['filter' => 'auth']);
$routes->get('portal/tickets/attachment/(:num)', 'Hospital::ticketAttachmentDownload/$1', ['filter' => 'auth']);
$routes->get('portal/logout',          'Hospital::logout');

// HPR Professionals (hospital portal)
$routes->get('portal/hpr-professionals',          'Hospital::hprProfessionals',          ['filter' => 'auth']);
$routes->post('portal/hpr-professionals/create',  'Hospital::hprProfessionalCreate',     ['filter' => 'auth']);
$routes->post('portal/hpr-professionals/(:num)/delete', 'Hospital::hprProfessionalDelete/$1', ['filter' => 'auth']);

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

    // ABHA Card Download
    $routes->get('abha/card', 'AbdmGateway::abhaCard');
    
    // Consent Operations
    $routes->post('consent/request', 'AbdmGateway::consentRequest');
    
    // Bundle Operations
    $routes->post('bundle/push', 'AbdmGateway::bundlePush');
    
    // SNOMED Search
    $routes->get('snomed/search', 'AbdmGateway::snomedSearch');
    
    // Gateway Status
    $routes->get('gateway/status', 'AbdmGateway::gatewayStatus');

    // OPD Queue API (for HMS integration)
    $routes->get('opd/queue',            'AbdmGateway::opdQueueList');
    $routes->post('opd/token',           'AbdmGateway::opdTokenCreate');
    $routes->patch('opd/token/(:num)',   'AbdmGateway::opdTokenUpdateStatus/$1');

    // Scan and Share — ABDM calls this when patient scans facility QR
    $routes->post('hip/patient/share', 'AbdmGateway::hipPatientShare');

    // Running Token Status — query current OPD token being served at a HIP
    $routes->get('opd/running-token-status', 'AbdmGateway::opdRunningTokenStatus');

    // Face Auth — ABHA creation and login via biometric face recognition
    // Enrollment flow (new ABHA via face):
    $routes->post('abha/face/enrol/init',       'AbdmGateway::abhaFaceEnrolInit');
    $routes->post('abha/face/capture-pid',      'AbdmGateway::abhaFaceCapturePid');
    $routes->post('abha/face/enrol/submit',     'AbdmGateway::abhaFaceEnrolSubmit');
    // QR login flow (existing ABHA via face):
    $routes->post('abha/face/login/search',     'AbdmGateway::abhaFaceLoginSearch');
    $routes->post('abha/face/login/request',    'AbdmGateway::abhaFaceLoginRequest');
    $routes->post('abha/face/login/verify',     'AbdmGateway::abhaFaceLoginVerify');

    // Biometric (Fingerprint / Iris) — ABHA enrollment and login via biometric device
    // Enrollment flow (new ABHA via fingerprint/iris):
    $routes->post('abha/bio/enrol/init',        'AbdmGateway::abhaBioEnrolInit');
    $routes->post('abha/bio/capture-pid',       'AbdmGateway::abhaFaceCapturePid');   // shared capturePID handler
    $routes->post('abha/bio/enrol/submit',      'AbdmGateway::abhaBioEnrolSubmit');
    // Login flow (existing ABHA via fingerprint/iris):
    $routes->post('abha/bio/login/request',     'AbdmGateway::abhaBioLoginRequest');
    $routes->post('abha/bio/login/verify',      'AbdmGateway::abhaBioLoginVerify');

    // Scan & Pay — HMS-initiated payment order flow (proxied to ABDM scan-gateway)
    $routes->post('scan-pay/open-order',          'AbdmGateway::scanPayOpenOrder');
    $routes->post('scan-pay/on-share-open-order', 'AbdmGateway::scanPayOnShareOpenOrder');
    $routes->post('scan-pay/selection',           'AbdmGateway::scanPaySelection');
    $routes->post('scan-pay/on-selection',        'AbdmGateway::scanPayOnSelection');
    $routes->post('scan-pay/notify',              'AbdmGateway::scanPayNotify');
    $routes->post('scan-pay/order-status',        'AbdmGateway::scanPayOrderStatus');
    $routes->get('scan-pay/details',              'AbdmGateway::scanPayDetails');
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
        $routes->post('m1/otp-address-set', 'Admin::m1OtpAddressSetPost');
        $routes->get('m1/abha-profiles', 'Admin::m1AbhaProfiles');
        $routes->head('m1/abha-profiles', 'Admin::m1AbhaProfiles');
        $routes->get('m1/verify-flow', 'Admin::m1VerifyFlow');
        $routes->post('m1/verify-otp-request', 'Admin::m1VerifyOtpRequestPost');
        $routes->post('m1/verify-otp-confirm', 'Admin::m1VerifyOtpConfirmPost');
        $routes->post('m1/verify-user-select', 'Admin::m1VerifyUserSelectPost');
        $routes->get('m1/abha-card', 'Admin::m1AbhaCard');
        $routes->get('m1/patient-card', 'Admin::m1PatientAbhaCard');
        $routes->get('m1/fetch-token', 'Admin::fetchAbdmToken');
        $routes->get('m1/scan-share', 'Admin::m1ScanShare');
        $routes->get('m1/scan-share-setup', 'Admin::m1ScanShareSetup');
        $routes->post('m1/scan-share-setup', 'Admin::m1ScanShareSetupPost');
    $routes->get('facility-qr', 'Admin::facilityQr');
    $routes->post('facility-qr/upload', 'Admin::facilityQrUpload');
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
    $routes->post('hms-credential/(:num)/regenerate-key', 'Admin::regenerateHmsKey/$1');
    $routes->post('hms-credential/(:num)/send-key-email', 'Admin::sendHmsKeyEmail/$1');
    $routes->post('hms-credential/(:num)/delete', 'Admin::deleteHmsCredential/$1');

    // Support Tickets
    $routes->get('support', 'Admin::supportTickets');
    $routes->post('support/close-stale', 'Admin::supportCloseStale');
    $routes->get('support/(:num)', 'Admin::supportTicketView/$1');
    $routes->post('support/(:num)/reply', 'Admin::supportTicketReplyPost/$1');
    $routes->post('support/(:num)/close', 'Admin::supportTicketClose/$1');
    $routes->get('support/attachment/(:num)', 'Admin::supportAttachmentDownload/$1');

    // Hospital Registrations
    $routes->get('registrations', 'Admin::hospitalRegistrations');
    $routes->post('registrations/(:num)/approve', 'Admin::hospitalRegistrationApprove/$1');
    $routes->post('registrations/(:num)/reject', 'Admin::hospitalRegistrationReject/$1');

    // HPR Professionals
    $routes->get('hpr-professionals', 'Admin::hprProfessionals');
    $routes->post('hpr-professionals/create', 'Admin::hprProfessionalCreate');
    $routes->post('hpr-professionals/(:num)/toggle', 'Admin::hprProfessionalToggle/$1');
    $routes->post('hpr-professionals/(:num)/delete', 'Admin::hprProfessionalDelete/$1');

    // SMTP / App Settings
    $routes->get('settings/smtp', 'Admin::smtpSettings');
    $routes->post('settings/smtp', 'Admin::smtpSettingsSave');
    $routes->post('settings/smtp/test', 'Admin::smtpSettingsTest');
});

// ==================== Catch-all ====================

// Use default CI4 404 handler; custom Home::notFound is not present in gateway app.
