<?php

namespace App\Controllers;

use App\Models\AbdmRequestLog;
use App\Models\AbdmAuditTrail;
use App\Models\AbdmBundle;
use App\Models\AbdmHospitalUser;
use App\Models\AbdmTestSubmissionLog;
use App\Models\AbdmTokenQueue;

class AbdmGateway extends BaseController
{
    protected $requestLog;
    protected $auditTrail;
    protected $bundleLog;
    protected $hospitalUser;
    protected $testSubmissionLog;
    protected ?string $cachedAbdmToken = null;
    protected ?int $authHospitalId = null;
    protected ?int $authUserId = null;
    protected ?string $authHospitalMode = null;
    protected string $authPrincipal = 'system';


    protected function bootRepositories(): void
    {
        if ($this->isTestMode()) {
            return;
        }

        if ($this->requestLog === null) {
            try {
                $this->requestLog = new AbdmRequestLog();
            } catch (\Throwable $e) {
                $this->requestLog = null;
                log_message('error', 'ABDM request logger unavailable: ' . $e->getMessage());
            }
        }

        if ($this->auditTrail === null) {
            try {
                $this->auditTrail = new AbdmAuditTrail();
            } catch (\Throwable $e) {
                $this->auditTrail = null;
                log_message('error', 'ABDM audit trail unavailable: ' . $e->getMessage());
            }
        }

        if ($this->bundleLog === null) {
            try {
                $this->bundleLog = new AbdmBundle();
            } catch (\Throwable $e) {
                $this->bundleLog = null;
                log_message('error', 'ABDM bundle logger unavailable: ' . $e->getMessage());
            }
        }
    }

    protected function bootAuthRepository(): void
    {
        if ($this->hospitalUser === null) {
            $this->hospitalUser = new AbdmHospitalUser();
        }
    }

    protected function bootTestLogger(): void
    {
        if ($this->testSubmissionLog === null) {
            $this->testSubmissionLog = new AbdmTestSubmissionLog();
        }
    }

    /**
     * Health Check Endpoint
     * GET /api/v3/health
     */
    public function health()
    {
        return $this->response->setJSON([
            'status' => 'ok',
            'timestamp' => date('c'),
            'service' => 'abdm-bridge-gateway',
            'version' => '1.0.0',
            'mode' => $this->isTestMode() ? 'test' : 'live',
            'uptime' => (int)(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']),
        ]);
    }

    /**
     * ABHA Validation Endpoint
     * POST /api/v3/abha/validate
     * Proxies to ABDM M3 API
     */
    public function abhaValidate()
    {
        $requestId = $this->generateRequestId();
        $authStatus = $this->validateBearer();
        if ($authStatus !== 'valid') {
            $this->logRequest($requestId, 'POST', '/api/v3/abha/validate', 403,
                             $authStatus, 'Invalid or missing bearer token');
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => 0,
                'error' => 'Invalid authorization token'
            ]);
        }

        if ($this->isTestMode()) {
            $body = (array) ($this->request->getJSON(true) ?? []);
            $abha = $body['abha_id'] ?? ($body['abha_address'] ?? '00-0000-0000-0000');

            $response = [
                'ok' => 1,
                'mode' => 'test',
                'request_id' => $requestId,
                'data' => [
                    'abha' => $abha,
                    'status' => 'VALID',
                    'message' => 'Mock response in test mode',
                ],
            ];

            $this->logTestSubmission(
                $requestId,
                '/api/v3/abha/validate',
                $body,
                $response,
                200,
                'abdm.abha.validate'
            );

            return $this->response->setJSON($response);
        }

        $this->bootRepositories();
        $startTime = microtime(true);

        $body = $this->request->getJSON();
        $abhaId = $body->abha_id ?? null;
        $abhaAddress = $body->abha_address ?? null;

        // Validate input
        if (!$abhaId && !$abhaAddress) {
            $this->logRequest($requestId, 'POST', '/api/v3/abha/validate', 400, 
                             'valid', 'Missing abha_id or abha_address');
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => 0,
                'error' => 'Either abha_id or abha_address required'
            ]);
        }

        try {
            // Call ABDM M3 API
            $abdmUrl = config('AbdmGateway')->m3Url . '/abha/validate';
            $abdmToken = $this->getAbdmAccessToken();
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $abdmUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($body),
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $abdmToken,
                    'Content-Type: application/json',
                    'X-Client-ID: ' . config('AbdmGateway')->sourceCode,
                ],
                CURLOPT_SSL_VERIFYPEER => false,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                throw new \Exception($curlError);
            }

            $responseTime = round((microtime(true) - $startTime) * 1000);
            $this->logRequest($requestId, 'POST', '/api/v3/abha/validate', $httpCode, 
                             'valid', null, $responseTime);

            $decodedResponse = json_decode((string) $response, true);
            $responseData = is_array($decodedResponse)
                ? $decodedResponse
                : ['raw_response' => trim((string) $response)];

            return $this->response->setStatusCode($httpCode)->setJSON([
                'ok' => $httpCode === 200 ? 1 : 0,
                'data' => $responseData,
                'request_id' => $requestId,
            ]);

        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000);
            $this->logRequest($requestId, 'POST', '/api/v3/abha/validate', 500, 
                             'valid', $e->getMessage(), $responseTime);
            
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => 0,
                'error' => $e->getMessage(),
                'request_id' => $requestId,
            ]);
        }
    }

    /**
     * ABDM M1: Generate Aadhaar OTP for ABHA enrolment/linking.
     * POST /api/v3/abha/aadhaar/generate-otp
     *
     * HMS sends: { "aadhaar": "574287571374" }  (plain 12-digit number)
     * Gateway encrypts with ABDM RSA public key before forwarding.
     */
    public function abhaAadhaarGenerateOtp()
    {
        $ep        = '/api/v3/abha/aadhaar/generate-otp';
        $requestId = $this->generateRequestId();

        $authStatus = $this->validateBearer();
        if ($authStatus !== 'valid') {
            $this->logRequest($requestId, 'POST', $ep, 403, $authStatus, 'Invalid or missing bearer token');
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => 0, 'error' => 'Invalid authorization token', 'request_id' => $requestId,
            ]);
        }

        $body         = json_decode((string) $this->request->getBody(), true) ?? [];
        $plainAadhaar = trim((string) ($body['aadhaar'] ?? ''));

        if (!preg_match('/^\d{12}$/', $plainAadhaar)) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => 0, 'error' => 'invalid_aadhaar',
                'message' => 'Field "aadhaar" must be a 12-digit number.',
                'request_id' => $requestId,
            ]);
        }

        if ($this->isTestMode()) {
            $mock = [
                'ok' => 1, 'mode' => 'test', 'request_id' => $requestId,
                'data' => ['txnId' => 'TEST-TXN-' . uniqid(), 'message' => 'Test mode: OTP would be sent to Aadhaar-linked mobile.'],
            ];
            $this->logTestSubmission($requestId, $ep, $body, $mock, 200, 'abdm.m1.aadhaar.generate-otp');
            return $this->response->setJSON($mock);
        }

        try {
            $encAadhaar = $this->encryptAbdmData($plainAadhaar);
        } catch (\Throwable $e) {
            $this->logRequest($requestId, 'POST', $ep, 500, 'valid', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => 0, 'error' => 'encryption_failed',
                'message' => $e->getMessage(),
                'request_id' => $requestId,
            ]);
        }

        $abdmPayload = [
            'txnId'     => '',
            'scope'     => ['abha-enrol'],
            'loginHint' => 'aadhaar',
            'loginId'   => $encAadhaar,
            'otpSystem' => 'aadhaar',
        ];

        return $this->sendM1Request($requestId, $ep, config('AbdmGateway')->m1AadhaarGenerateOtpPath, $abdmPayload);
    }

    /**
     * ABDM M1: Verify Aadhaar OTP and enrol/link ABHA.
     * POST /api/v3/abha/aadhaar/verify-otp
     *
     * HMS sends: { "txnId": "...", "otp": "123456", "mobile": "9999999999" }
     * Gateway encrypts OTP with ABDM RSA public key before forwarding.
     */
    public function abhaAadhaarVerifyOtp()
    {
        $ep        = '/api/v3/abha/aadhaar/verify-otp';
        $requestId = $this->generateRequestId();

        $authStatus = $this->validateBearer();
        if ($authStatus !== 'valid') {
            $this->logRequest($requestId, 'POST', $ep, 403, $authStatus, 'Invalid or missing bearer token');
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => 0, 'error' => 'Invalid authorization token', 'request_id' => $requestId,
            ]);
        }

        $body   = json_decode((string) $this->request->getBody(), true) ?? [];
        // Accept both 'otp' and 'otpValue' (ABDM-style) for HMS compatibility
        $txnId  = trim((string) ($body['txnId']  ?? $body['transactionId'] ?? $body['transaction_id'] ?? ''));
        $otp    = trim((string) ($body['otp']    ?? $body['otpValue']      ?? $body['otp_value']      ?? ''));
        $mobile = trim((string) ($body['mobile'] ?? $body['mobileNumber']  ?? $body['mobile_number']  ?? ''));

        if ($txnId === '' || $otp === '') {
            $this->logRequest($requestId, 'POST', $ep, 400, 'valid', 'missing_fields: txnId or otp empty');
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => 0, 'error' => 'missing_fields',
                'message' => 'Required fields: txnId (or transactionId) and otp (or otpValue).',
                'request_id' => $requestId,
            ]);
        }

        if ($this->isTestMode()) {
            $mock = [
                'ok' => 1, 'mode' => 'test', 'request_id' => $requestId,
                'data' => ['message' => 'Test mode: ABHA would be enrolled/linked.', 'ABHAProfile' => ['ABHANumber' => '14-0000-0000-0000']],
            ];
            $this->logTestSubmission($requestId, $ep, $body, $mock, 200, 'abdm.m1.aadhaar.verify-otp');
            return $this->response->setJSON($mock);
        }

        try {
            $encOtp = $this->encryptAbdmData($otp);
        } catch (\Throwable $e) {
            $this->logRequest($requestId, 'POST', $ep, 500, 'valid', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => 0, 'error' => 'encryption_failed',
                'message' => $e->getMessage(),
                'request_id' => $requestId,
            ]);
        }

        $abdmPayload = [
            'authData' => [
                'authMethods' => ['otp'],
                'otp' => [
                    'txnId'    => $txnId,
                    'otpValue' => $encOtp,
                    'mobile'   => $mobile,
                ],
            ],
            'consent' => [
                'code'    => 'abha-enrollment',
                'version' => '1.4',
            ],
        ];

        return $this->sendM1Request($requestId, $ep, config('AbdmGateway')->m1AadhaarVerifyOtpPath, $abdmPayload);
    }

    /**
     * ABDM M1: Generate mobile OTP for ABHA enrolment via mobile.
     * POST /api/v3/abha/mobile/generate-otp
     *
     * HMS sends: { "mobile": "9876543210" }
     * Gateway encrypts mobile with ABDM RSA public key before forwarding.
     */
    public function abhaMobileGenerateOtp()
    {
        $ep        = '/api/v3/abha/mobile/generate-otp';
        $requestId = $this->generateRequestId();

        $authStatus = $this->validateBearer();
        if ($authStatus !== 'valid') {
            $this->logRequest($requestId, 'POST', $ep, 403, $authStatus, 'Invalid or missing bearer token');
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => 0, 'error' => 'Invalid authorization token', 'request_id' => $requestId,
            ]);
        }

        $body        = json_decode((string) $this->request->getBody(), true) ?? [];
        $plainMobile = trim((string) ($body['mobile'] ?? $body['mobileNumber'] ?? $body['mobile_number'] ?? ''));

        if (!preg_match('/^\d{10}$/', $plainMobile)) {
            $this->logRequest($requestId, 'POST', $ep, 400, 'valid', 'invalid_mobile: must be 10 digits');
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => 0, 'error' => 'invalid_mobile',
                'message' => 'Field "mobile" must be a 10-digit mobile number.',
                'request_id' => $requestId,
            ]);
        }

        if ($this->isTestMode()) {
            $mock = [
                'ok' => 1, 'mode' => 'test', 'request_id' => $requestId,
                'data' => ['txnId' => 'TEST-TXN-' . uniqid(), 'message' => 'Test mode: OTP would be sent to mobile.'],
            ];
            $this->logTestSubmission($requestId, $ep, $body, $mock, 200, 'abdm.m1.mobile.generate-otp');
            return $this->response->setJSON($mock);
        }

        try {
            $encMobile = $this->encryptAbdmData($plainMobile);
        } catch (\Throwable $e) {
            $this->logRequest($requestId, 'POST', $ep, 500, 'valid', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => 0, 'error' => 'encryption_failed',
                'message' => $e->getMessage(),
                'request_id' => $requestId,
            ]);
        }

        $abdmPayload = [
            'scope'     => ['abha-login', 'mobile-verify'],
            'loginHint' => 'mobile',
            'loginId'   => $encMobile,
            'otpSystem' => 'abdm',
        ];

        return $this->sendM1Request($requestId, $ep, config('AbdmGateway')->m1MobileGenerateOtpPath, $abdmPayload);
    }

    /**
     * ABDM M1: Verify mobile OTP and enrol/link ABHA via mobile.
     * POST /api/v3/abha/mobile/verify-otp
     *
     * HMS sends: { "txnId": "...", "otp": "123456" }
     * Gateway encrypts OTP with ABDM RSA public key before forwarding.
     */
    public function abhaMobileVerifyOtp()
    {
        $ep        = '/api/v3/abha/mobile/verify-otp';
        $requestId = $this->generateRequestId();

        $authStatus = $this->validateBearer();
        if ($authStatus !== 'valid') {
            $this->logRequest($requestId, 'POST', $ep, 403, $authStatus, 'Invalid or missing bearer token');
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => 0, 'error' => 'Invalid authorization token', 'request_id' => $requestId,
            ]);
        }

        $body  = json_decode((string) $this->request->getBody(), true) ?? [];
        $txnId = trim((string) ($body['txnId']  ?? $body['transactionId'] ?? $body['transaction_id'] ?? ''));
        $otp   = trim((string) ($body['otp']    ?? $body['otpValue']      ?? $body['otp_value']      ?? ''));

        if ($txnId === '' || $otp === '') {
            $this->logRequest($requestId, 'POST', $ep, 400, 'valid', 'missing_fields: txnId or otp empty');
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => 0, 'error' => 'missing_fields',
                'message' => 'Required fields: txnId (or transactionId) and otp (or otpValue).',
                'request_id' => $requestId,
            ]);
        }

        if ($this->isTestMode()) {
            $mock = [
                'ok' => 1, 'mode' => 'test', 'request_id' => $requestId,
                'data' => [
                    'message' => 'Test mode: ABHA would be enrolled/linked via mobile.',
                    'enrolProfile' => ['enrolmentNumber' => '91-0000-0000-0000', 'enrolmentState' => 'PROVISIONAL'],
                ],
            ];
            $this->logTestSubmission($requestId, $ep, $body, $mock, 200, 'abdm.m1.mobile.verify-otp');
            return $this->response->setJSON($mock);
        }

        try {
            $encOtp = $this->encryptAbdmData($otp);
        } catch (\Throwable $e) {
            $this->logRequest($requestId, 'POST', $ep, 500, 'valid', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => 0, 'error' => 'encryption_failed',
                'message' => $e->getMessage(),
                'request_id' => $requestId,
            ]);
        }

        $abdmPayload = [
            'scope'    => ['abha-login', 'mobile-verify'],
            'authData' => [
                'authMethods' => ['otp'],
                'otp' => [
                    'txnId'    => $txnId,
                    'otpValue' => $encOtp,
                ],
            ],
        ];

        // Step 1: Verify OTP
        $this->bootRepositories();
        $startTime = microtime(true);
        try {
            $cfg       = config('AbdmGateway');
            $abdmToken = $this->getAbdmAccessToken();
            $verifyUrl = rtrim($cfg->m1BaseUrl, '/') . '/' . ltrim($cfg->m1MobileVerifyOtpPath, '/');

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $verifyUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => (int) $cfg->m3Timeout,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($abdmPayload),
                CURLOPT_HTTPHEADER     => [
                    'Authorization: Bearer ' . $abdmToken,
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'X-Client-ID: '   . $cfg->sourceCode,
                    'REQUEST-ID: '    . $this->generateAbdmRequestId(),
                    'TIMESTAMP: '     . gmdate('Y-m-d\TH:i:s.000\Z'),
                ],
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $rawVerify  = curl_exec($ch);
            $httpCode   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError  = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                throw new \RuntimeException($curlError);
            }

            $responseTime = round((microtime(true) - $startTime) * 1000);
            $this->logRequest($requestId, 'POST', $ep, $httpCode, 'valid', null, $responseTime, $rawVerify);

            $verifyData = json_decode((string) $rawVerify, true);
            if (!is_array($verifyData)) {
                $verifyData = ['raw_response' => trim((string) $rawVerify)];
            }

            if ($httpCode < 200 || $httpCode >= 300) {
                return $this->response->setStatusCode($httpCode)->setJSON([
                    'ok' => 0, 'data' => $verifyData, 'request_id' => $requestId,
                ]);
            }

            // Step 2: Fetch ABHA profile using the transfer token
            $xToken = $verifyData['token'] ?? null;
            if ($xToken) {
                $profileUrl = rtrim($cfg->m1BaseUrl, '/') . '/abha/api/v3/profile/account';
                $ch2 = curl_init();
                curl_setopt_array($ch2, [
                    CURLOPT_URL            => $profileUrl,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => (int) $cfg->m3Timeout,
                    CURLOPT_HTTPHEADER     => [
                        'Authorization: Bearer ' . $abdmToken,
                        'X-token: Bearer ' . $xToken,
                        'Accept: application/json',
                        'X-Client-ID: '   . $cfg->sourceCode,
                        'REQUEST-ID: '    . $this->generateAbdmRequestId(),
                        'TIMESTAMP: '     . gmdate('Y-m-d\TH:i:s.000\Z'),
                    ],
                    CURLOPT_SSL_VERIFYPEER => false,
                ]);
                $rawProfile  = curl_exec($ch2);
                $profileCode = (int) curl_getinfo($ch2, CURLINFO_HTTP_CODE);
                curl_close($ch2);

                $profileData = json_decode((string) $rawProfile, true);
                if (is_array($profileData)) {
                    $verifyData['profile'] = $profileData;
                }
            }

            return $this->response->setStatusCode(200)->setJSON([
                'ok' => 1, 'data' => $verifyData, 'request_id' => $requestId,
            ]);

        } catch (\Throwable $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000);
            $this->logRequest($requestId, 'POST', $ep, 500, 'valid', $e->getMessage(), $responseTime);
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => 0, 'error' => $e->getMessage(), 'request_id' => $requestId,
            ]);
        }
    }

    /**
     * Consent Request Endpoint
     * POST /api/v3/consent/request
     */
    public function consentRequest()
    {
        $requestId = $this->generateRequestId();
        $authStatus = $this->validateBearer();
        if ($authStatus !== 'valid') {
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => 0,
                'error' => 'Unauthorized'
            ]);
        }

        if ($this->isTestMode()) {
            $body = (array) ($this->request->getJSON(true) ?? []);

            $response = [
                'ok' => 1,
                'mode' => 'test',
                'request_id' => $requestId,
                'consent_id' => 'CONS-TEST-' . date('YmdHis'),
                'data' => [
                    'patient_abha' => $body['patient_abha'] ?? '00-0000-0000-0000',
                    'purpose' => $body['purpose'] ?? 'TREATMENT',
                    'hi_types' => $body['hi_types'] ?? ['OPConsultation'],
                    'status' => 'REQUESTED',
                    'message' => 'Mock response in test mode',
                ],
            ];

            $this->logTestSubmission(
                $requestId,
                '/api/v3/consent/request',
                $body,
                $response,
                200,
                'abdm.consent.requested'
            );

            return $this->response->setJSON($response);
        }

        $this->bootRepositories();
        $startTime = microtime(true);

        $body = $this->request->getJSON();
        $patientAbha = $body->patient_abha ?? null;
        $purpose = $body->purpose ?? 'treatment';
        $hiTypes = $body->hi_types ?? [];

        // Validate input
        if (!$patientAbha || !is_array($hiTypes)) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => 0,
                'error' => 'patient_abha and hi_types array required'
            ]);
        }

        try {
            $consentId = 'CONS-' . date('YmdHis') . '-' . substr(md5(random_bytes(16)), 0, 8);

            // Call ABDM M3 API
            $abdmUrl = config('AbdmGateway')->m3Url . '/consent/request';
            $abdmToken = $this->getAbdmAccessToken();

            $payload = [
                'consent_id' => $consentId,
                'patient_abha' => $patientAbha,
                'purpose' => $purpose,
                'hi_types' => $hiTypes,
            ];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $abdmUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $abdmToken,
                    'Content-Type: application/json',
                ],
                CURLOPT_SSL_VERIFYPEER => false,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // Log to audit trail
            $this->auditTrail->insert([
                'request_id' => $requestId,
                'action' => 'consent_request',
                'patient_abha' => $patientAbha,
                'consent_id' => $consentId,
                'hi_types' => json_encode($hiTypes),
                'action_status' => $httpCode === 200 ? 'success' : 'failed',
                'details' => json_encode($payload),
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            $responseTime = round((microtime(true) - $startTime) * 1000);
            $this->logRequest($requestId, 'POST', '/api/v3/consent/request', $httpCode, 
                             'valid', null, $responseTime);

            return $this->response->setStatusCode($httpCode)->setJSON([
                'ok' => $httpCode === 200 ? 1 : 0,
                'consent_id' => $consentId,
                'data' => json_decode($response, true),
                'request_id' => $requestId,
            ]);

        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000);
            $this->logRequest($requestId, 'POST', '/api/v3/consent/request', 500, 
                             'valid', $e->getMessage(), $responseTime);

            return $this->response->setStatusCode(500)->setJSON([
                'ok' => 0,
                'error' => $e->getMessage(),
                'request_id' => $requestId,
            ]);
        }
    }

    /**
     * Bundle Push Endpoint
     * POST /api/v3/bundle/push
     */
    public function bundlePush()
    {
        $requestId = $this->generateRequestId();
        $authStatus = $this->validateBearer();
        if ($authStatus !== 'valid') {
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => 0,
                'error' => 'Unauthorized'
            ]);
        }

        if ($this->isTestMode()) {
            $body = (array) ($this->request->getJSON(true) ?? []);

            $response = [
                'ok' => 1,
                'mode' => 'test',
                'request_id' => $requestId,
                'bundle_id' => 'BUN-TEST-' . date('YmdHis'),
                'data' => [
                    'consent_id' => $body['consent_id'] ?? 'CONS-TEST',
                    'hi_type' => $body['hi_type'] ?? 'OPConsultation',
                    'status' => 'ACCEPTED',
                    'message' => 'Mock response in test mode',
                ],
            ];

            $this->logTestSubmission(
                $requestId,
                '/api/v3/bundle/push',
                $body,
                $response,
                200,
                'abdm.fhir.share.requested'
            );

            return $this->response->setJSON($response);
        }

        $this->bootRepositories();
        $startTime = microtime(true);

        $body = $this->request->getJSON();
        $fhirBundle = $body->fhir_bundle ?? null;
        $consentId = $body->consent_id ?? null;
        $hiType = $body->hi_type ?? null;

        if (!$fhirBundle || !$consentId || !$hiType) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => 0,
                'error' => 'fhir_bundle, consent_id, and hi_type required'
            ]);
        }

        try {
            $bundleId = 'BUN-' . date('YmdHis') . '-' . substr(md5(random_bytes(16)), 0, 8);
            $bundleHash = sha1(json_encode($fhirBundle));

            // Call ABDM M3 API
            $abdmUrl = config('AbdmGateway')->m3Url . '/bundle/push';
            $abdmToken = $this->getAbdmAccessToken();

            $payload = [
                'bundle_id' => $bundleId,
                'hi_type' => $hiType,
                'consent_id' => $consentId,
                'fhir_bundle' => $fhirBundle,
            ];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $abdmUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $abdmToken,
                    'Content-Type: application/json',
                ],
                CURLOPT_SSL_VERIFYPEER => false,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // Log bundle
            $this->bundleLog->insert([
                'bundle_id' => $bundleId,
                'consent_id' => $consentId,
                'hi_type' => $hiType,
                'bundle_hash' => $bundleHash,
                'push_status' => $httpCode === 200 ? 'pushed' : 'failed',
                'response_status' => $httpCode,
                'response_body' => $response,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            $responseTime = round((microtime(true) - $startTime) * 1000);
            $this->logRequest($requestId, 'POST', '/api/v3/bundle/push', $httpCode, 
                             'valid', null, $responseTime);

            return $this->response->setStatusCode($httpCode)->setJSON([
                'ok' => $httpCode === 200 ? 1 : 0,
                'bundle_id' => $bundleId,
                'data' => json_decode($response, true),
                'request_id' => $requestId,
            ]);

        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000);
            $this->logRequest($requestId, 'POST', '/api/v3/bundle/push', 500, 
                             'valid', $e->getMessage(), $responseTime);

            return $this->response->setStatusCode(500)->setJSON([
                'ok' => 0,
                'error' => $e->getMessage(),
                'request_id' => $requestId,
            ]);
        }
    }

    /**
     * SNOMED Search Endpoint
     * GET /api/v3/snomed/search
     */
    public function snomedSearch()
    {
        $requestId = $this->generateRequestId();
        $authStatus = $this->validateBearer();
        if ($authStatus !== 'valid') {
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => 0,
                'error' => 'Unauthorized'
            ]);
        }

        if ($this->isTestMode()) {
            $term = (string) ($this->request->getGet('term') ?? 'fever');

            $response = [
                'ok' => 1,
                'mode' => 'test',
                'request_id' => $requestId,
                'data' => [
                    ['code' => '386661006', 'term' => $term],
                    ['code' => '271807003', 'term' => $term . ' symptom'],
                ],
            ];

            $this->logTestSubmission(
                $requestId,
                '/api/v3/snomed/search',
                [
                    'term' => $term,
                    'return_limit' => (string) ($this->request->getGet('return_limit') ?? '10'),
                ],
                $response,
                200,
                'abdm.scan_share.lookup'
            );

            return $this->response->setJSON($response);
        }

        $this->bootRepositories();
        $startTime = microtime(true);

        $term = $this->request->getGet('term');
        $returnLimit = $this->request->getGet('return_limit') ?? 10;

        if (!$term) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => 0,
                'error' => 'term parameter required'
            ]);
        }

        try {
            // Call CSNOtk SNOMED service
            $snomedUrl = config('AbdmGateway')->snomedUrl . '/search/suggest';
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $snomedUrl . '?term=' . urlencode($term) . '&returnlimit=' . $returnLimit,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $responseTime = round((microtime(true) - $startTime) * 1000);
            $this->logRequest($requestId, 'GET', '/api/v3/snomed/search', $httpCode, 
                             'valid', null, $responseTime);

            return $this->response->setStatusCode($httpCode)->setJSON([
                'ok' => $httpCode === 200 ? 1 : 0,
                'data' => json_decode($response, true),
                'request_id' => $requestId,
            ]);

        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000);
            $this->logRequest($requestId, 'GET', '/api/v3/snomed/search', 500, 
                             'valid', $e->getMessage(), $responseTime);

            return $this->response->setStatusCode(500)->setJSON([
                'ok' => 0,
                'error' => $e->getMessage(),
                'request_id' => $requestId,
            ]);
        }
    }

    /**
     * Gateway Status Endpoint
     * GET /api/v3/gateway/status
     */
    public function gatewayStatus()
    {
        $authStatus = $this->validateBearer();
        if ($authStatus !== 'valid') {
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => 0,
                'error' => 'Unauthorized'
            ]);
        }

        if ($this->isTestMode()) {
            return $this->response->setJSON([
                'ok' => 1,
                'mode' => 'test',
                'data' => [
                    'gateway' => 'ok',
                    'database' => 'skipped',
                    'abdm_m3' => 'skipped',
                    'snomed_service' => 'skipped',
                    'timestamp' => date('c'),
                ],
            ]);
        }

        $this->bootRepositories();

        $status = [
            'gateway' => 'ok',
            'database' => $this->checkDatabase(),
            'abdm_m3' => $this->checkAbdmM3(),
            'snomed_service' => $this->checkSnomedService(),
            'timestamp' => date('c'),
        ];

        $allOk = $status['gateway'] === 'ok' && 
                 $status['database'] === 'ok' && 
                 $status['abdm_m3'] === 'ok' && 
                 $status['snomed_service'] === 'ok';

        return $this->response->setJSON([
            'ok' => $allOk ? 1 : 0,
            'data' => $status,
        ]);
    }

    /**
     * Scan and Share callback — ABDM calls this when a patient scans the
     * health facility QR code in their ABHA app.
     * POST /api/v3/hip/patient/share
     */
    public function hipPatientShare()
    {
        // Accept raw JSON from ABDM (Authorization header is from ABDM gateway JWT)
        $body = (array) ($this->request->getJSON(true) ?? []);

        if (empty($body)) {
            log_message('warning', 'hipPatientShare: empty body received');
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Empty request body']);
        }

        $incomingRequestId = (string) ($this->request->getHeaderLine('REQUEST-ID') ?: $this->generateRequestId());

        $meta    = is_array($body['metaData'] ?? null) ? $body['metaData'] : [];
        $patient = is_array($body['profile']['patient'] ?? null) ? $body['profile']['patient'] : [];

        $abhaNumber  = (string) ($patient['abhaNumber']  ?? '');
        $abhaAddress = (string) ($patient['abhaAddress'] ?? '');
        $name        = (string) ($patient['name']        ?? '');
        $gender      = (string) ($patient['gender']      ?? '');
        $phone       = (string) ($patient['phoneNumber'] ?? '');
        $hipId       = (string) ($meta['hipId']          ?? '');
        $context     = (string) ($meta['context']        ?? '');
        $hprId       = (string) ($meta['hprId']          ?? '');
        $dob         = is_array($patient['address'] ?? null) ? [] : [];  // dob parsed below
        $dayOfBirth   = (string) ($patient['dayOfBirth']   ?? '');
        $monthOfBirth = (string) ($patient['monthOfBirth'] ?? '');
        $yearOfBirth  = (string) ($patient['yearOfBirth']  ?? '');

        try {
            $tokenQueue  = new AbdmTokenQueue();
            $tokenNumber = $tokenQueue->nextTokenNumber();
            $today       = date('Y-m-d');

            $tokenQueue->insert([
                'abha_number'       => $abhaNumber,
                'abha_address'      => $abhaAddress,
                'patient_name'      => $name,
                'gender'            => $gender,
                'day_of_birth'      => $dayOfBirth,
                'month_of_birth'    => $monthOfBirth,
                'year_of_birth'     => $yearOfBirth,
                'phone'             => $phone,
                'hip_id'            => $hipId,
                'context'           => $context,
                'hpr_id'            => $hprId,
                'token_number'      => $tokenNumber,
                'token_date'        => $today,
                'status'            => 'PENDING',
                'request_id'        => $incomingRequestId,
                'share_request_json' => json_encode($body),
            ]);

            // Fire on-share acknowledgement back to ABDM
            $onShareSent = $this->sendOnShare($abhaAddress, $context, (string) $tokenNumber, $incomingRequestId);
            if ($onShareSent) {
                $tokenQueue->where('request_id', $incomingRequestId)->set(['on_share_sent' => 1])->update();
            }

            log_message('info', "hipPatientShare: token #{$tokenNumber} assigned to {$abhaNumber} (reqId={$incomingRequestId})");
        } catch (\Throwable $e) {
            log_message('error', 'hipPatientShare DB/on-share error: ' . $e->getMessage());
            // Still return 202 so ABDM doesn't retry indefinitely
        }

        return $this->response->setStatusCode(202)->setJSON(['status' => 'ACCEPTED']);
    }

    /**
     * Send the on-share acknowledgement back to ABDM gateway with the token number.
     */
    private function sendOnShare(string $abhaAddress, string $context, string $tokenNumber, string $requestId): bool
    {
        try {
            $abdmToken = $this->getAbdmAccessToken();
            $body = [
                'acknowledgement' => [
                    'status'  => 'SUCCESS',
                    'abhaAddress' => $abhaAddress,
                    'profile' => [
                        'context'     => $context,
                        'tokenNumber' => $tokenNumber,
                        'expiry'      => '1800',
                    ],
                ],
                'response' => [
                    'requestId' => $requestId,
                ],
            ];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => 'https://dev.abdm.gov.in/api/hiecm/patient-share/v3/on-share',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($body),
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'Authorization: Bearer ' . $abdmToken,
                    'REQUEST-ID: ' . $this->generateRequestId(),
                    'TIMESTAMP: ' . gmdate('Y-m-d\TH:i:s.000\Z'),
                ],
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $raw      = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode >= 400) {
                log_message('warning', "on-share HTTP {$httpCode}: " . substr((string) $raw, 0, 200));
                return false;
            }
            return true;
        } catch (\Throwable $e) {
            log_message('warning', 'sendOnShare failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Bridge ingress endpoint for HMS queue events.
     * POST /api/v1/bridge
     */
    public function bridgeDispatch()
    {
        $requestId = $this->generateRequestId();

        $authStatus = $this->validateBearer();
        if ($authStatus !== 'valid') {
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => 0,
                'error' => 'Unauthorized bridge request',
                'request_id' => $requestId,
            ]);
        }

        $body = (array) ($this->request->getJSON(true) ?? []);
        $eventType = trim((string) ($body['event_type'] ?? ''));
        $payload = isset($body['payload']) && is_array($body['payload']) ? $body['payload'] : $body;

        if ($eventType === '') {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => 0,
                'error' => 'event_type is required',
                'request_id' => $requestId,
            ]);
        }

        $route = $this->resolveBridgeRoute($eventType, $payload);
        if ($route['ok'] !== true) {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => 0,
                'event_type' => $eventType,
                'error' => $route['error'] ?? 'Unsupported event_type',
                'request_id' => $requestId,
            ]);
        }

        $dispatch = $this->dispatchBridgeRoute($route);

        if ($this->isTestMode()) {
            $this->logTestSubmission(
                $requestId,
                '/api/v1/bridge',
                [
                    'event_type' => $eventType,
                    'payload' => $payload,
                    'route' => $route,
                ],
                $dispatch,
                (int) ($dispatch['http_code'] ?? 200),
                $eventType
            );
        }

        return $this->response->setStatusCode((int) ($dispatch['http_code'] ?? 200))->setJSON([
            'ok' => (int) ($dispatch['ok'] ?? 0),
            'event_type' => $eventType,
            'target' => (string) ($route['path'] ?? ''),
            'request_id' => $requestId,
            'dispatch' => $dispatch,
        ]);
    }

    // ==================== Helper Methods ====================

    /**
     * Validate Bearer Token
     */
    protected function validateBearer()
    {
        $this->authHospitalId = null;
        $this->authUserId = null;
        $this->authHospitalMode = null;
        $this->authPrincipal = 'system';

        $authHeader = $this->request->getHeaderLine('Authorization');

        if (!$authHeader) {
            return 'missing';
        }

        $parts = explode(' ', $authHeader);
        if (count($parts) !== 2) {
            return 'invalid_format';
        }

        $scheme = trim((string) $parts[0]);
        $credential = trim((string) $parts[1]);

        if ($scheme === 'Bearer') {
            $configured = trim((string) config('AbdmGateway')->bearerToken);
            $accepted = array_filter([
                $configured,
                trim((string) env('ABDM_BRIDGE_TOKEN', '')),
                trim((string) env('BRIDGE_SYNC_TOKEN', '')),
            ], static fn($v) => $v !== '');

            foreach ($accepted as $expected) {
                if (hash_equals((string) $expected, $credential)) {
                    $this->authPrincipal = 'gateway';
                    return 'valid';
                }
            }

            if ($configured === '') {
                $configured = trim((string) env('GATEWAY_BEARER_TOKEN', ''));
            }

            if ($configured !== '' && hash_equals($configured, $credential)) {
                $this->authPrincipal = 'gateway';
                return 'valid';
            }

            $user = $this->findHospitalUserByToken($credential);
            if ($user === null) {
                // Also try HMS credentials API key
                $hmsUser = $this->findHospitalByHmsKey($credential);
                if ($hmsUser === null) {
                    return 'invalid_token';
                }
                $this->authHospitalId   = isset($hmsUser['hospital_id']) ? (int) $hmsUser['hospital_id'] : null;
                $this->authHospitalMode = isset($hmsUser['gateway_mode']) ? (string) $hmsUser['gateway_mode'] : null;
                $this->authPrincipal    = isset($hmsUser['hms_name']) ? (string) $hmsUser['hms_name'] : 'hms_api_key';
                return 'valid';
            }

            $this->authUserId = isset($user['user_id']) ? (int) $user['user_id'] : null;
            $this->authHospitalId = isset($user['hospital_id']) ? (int) $user['hospital_id'] : null;
            $this->authHospitalMode = isset($user['gateway_mode']) ? (string) $user['gateway_mode'] : null;
            $this->authPrincipal = isset($user['username']) ? (string) $user['username'] : 'hospital_user';
            $this->touchHospitalUserLogin($this->authUserId);

            return 'valid';
        }

        if ($scheme === 'Basic') {
            $decoded = base64_decode($credential, true);
            if ($decoded === false || strpos($decoded, ':') === false) {
                return 'invalid_format';
            }

            [$username, $password] = explode(':', $decoded, 2);
            $user = $this->findHospitalUserByUsername(trim((string) $username));
            if ($user === null) {
                return 'invalid_token';
            }

            $passwordHash = (string) ($user['password_hash'] ?? '');
            if ($passwordHash === '' || !password_verify((string) $password, $passwordHash)) {
                return 'invalid_token';
            }

            $this->authUserId = isset($user['user_id']) ? (int) $user['user_id'] : null;
            $this->authHospitalId = isset($user['hospital_id']) ? (int) $user['hospital_id'] : null;
            $this->authHospitalMode = isset($user['gateway_mode']) ? (string) $user['gateway_mode'] : null;
            $this->authPrincipal = isset($user['username']) ? (string) $user['username'] : 'hospital_user';
            $this->touchHospitalUserLogin($this->authUserId);

            return 'valid';
        }

        return 'invalid_format';
    }

    /**
     * Generate Request ID
     */
    protected function generateRequestId()
    {
        return 'REQ-' . date('YmdHis') . '-' . substr(md5(random_bytes(16)), 0, 8);
    }

    /**
     * Log Request
     */
    protected function logRequest($requestId, $method, $endpoint, $statusCode, $authStatus, $errorMessage = null, $responseTime = 0, $responseBody = null)
    {
        if ($this->isTestMode() || $this->requestLog === null) {
            return;
        }

        try {
            $this->requestLog->insert([
                'request_id'           => $requestId,
                'method'               => $method,
                'endpoint'             => $endpoint,
                'status_code'          => $statusCode,
                'response_time_ms'     => $responseTime,
                'ip_address'           => $this->request->getIPAddress(),
                'authorization_status' => $authStatus,
                'error_message'        => $errorMessage,
                'response_body'        => $responseBody !== null ? substr((string) $responseBody, 0, 2000) : null,
                'created_at'           => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // Silently fail - don't break the API
            log_message('error', 'Failed to log request: ' . $e->getMessage());
        }
    }

    protected function findHospitalUserByToken(string $token): ?array
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }

        $this->bootAuthRepository();

        try {
            $hash = hash('sha256', $token);

            $row = $this->hospitalUser
                ->select('abdm_hospital_users.id as user_id, abdm_hospital_users.hospital_id, abdm_hospital_users.username, abdm_hospital_users.password_hash, abdm_hospitals.gateway_mode')
                ->join('abdm_hospitals', 'abdm_hospitals.id = abdm_hospital_users.hospital_id', 'inner')
                ->where('abdm_hospital_users.api_token', $hash)
                ->where('abdm_hospital_users.is_active', 1)
                ->where('abdm_hospitals.is_active', 1)
                ->first();

            return is_object($row) ? (array) $row : (is_array($row) ? $row : null);
        } catch (\Exception $e) {
            log_message('error', 'Hospital token lookup failed: ' . $e->getMessage());
            return null;
        }
    }

    protected function findHospitalUserByUsername(string $username): ?array
    {
        $username = trim($username);
        if ($username === '') {
            return null;
        }

        $this->bootAuthRepository();

        try {
            $row = $this->hospitalUser
                ->select('abdm_hospital_users.id as user_id, abdm_hospital_users.hospital_id, abdm_hospital_users.username, abdm_hospital_users.password_hash, abdm_hospitals.gateway_mode')
                ->join('abdm_hospitals', 'abdm_hospitals.id = abdm_hospital_users.hospital_id', 'inner')
                ->where('abdm_hospital_users.username', $username)
                ->where('abdm_hospital_users.is_active', 1)
                ->where('abdm_hospitals.is_active', 1)
                ->first();

            return is_object($row) ? (array) $row : (is_array($row) ? $row : null);
        } catch (\Exception $e) {
            log_message('error', 'Hospital username lookup failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Find hospital by HMS credentials API key hash.
     * Returns hospital_id, gateway_mode, hms_name or null if not found.
     *
     * @return array<string,mixed>|null
     */
    protected function findHospitalByHmsKey(string $token): ?array
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }

        try {
            $db   = \Config\Database::connect();
            $hash = hash('sha256', $token);

            $row = $db->table('hms_credentials hc')
                ->select('hc.hospital_id, hc.hms_name, h.gateway_mode')
                ->join('abdm_hospitals h', 'h.id = hc.hospital_id', 'inner')
                ->where('hc.hms_api_key_hash', $hash)
                ->where('hc.is_active', 1)
                ->where('h.is_active', 1)
                ->get()
                ->getRowArray();

            return $row ?: null;
        } catch (\Exception $e) {
            log_message('error', 'HMS key lookup failed: ' . $e->getMessage());
            return null;
        }
    }

    protected function touchHospitalUserLogin(?int $userId): void
    {
        if ($userId === null || $userId <= 0) {
            return;
        }

        $this->bootAuthRepository();

        try {
            $this->hospitalUser->update($userId, ['last_login_at' => date('Y-m-d H:i:s')]);
        } catch (\Exception $e) {
            log_message('error', 'Failed to update last_login_at: ' . $e->getMessage());
        }
    }

    /**
     * @param array<string,mixed> $requestPayload
     * @param array<string,mixed> $responsePayload
     */
    protected function logTestSubmission(
        string $requestId,
        string $endpoint,
        array $requestPayload,
        array $responsePayload,
        int $httpStatus,
        ?string $eventType = null
    ): void {
        if (!$this->isTestMode()) {
            return;
        }

        $this->bootTestLogger();

        try {
            $this->testSubmissionLog->insert([
                'request_id' => $requestId,
                'hospital_id' => $this->authHospitalId,
                'user_id' => $this->authUserId,
                'event_type' => $eventType,
                'endpoint' => $endpoint,
                'http_status' => $httpStatus,
                'request_payload' => json_encode($requestPayload),
                'response_payload' => json_encode($responsePayload),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Failed to log test submission: ' . $e->getMessage());
        }
    }

    /**
     * Check Database Status
     */
    protected function checkDatabase()
    {
        if ($this->isTestMode()) {
            return 'skipped';
        }

        try {
            $db = \Config\Database::connect();
            $db->query('SELECT 1');
            return 'ok';
        } catch (\Exception $e) {
            return 'unreachable';
        }
    }

    /**
     * Check ABDM M3 API
     */
    protected function checkAbdmM3()
    {
        if ($this->isTestMode()) {
            return 'skipped';
        }

        try {
            $url = config('AbdmGateway')->m3Url . '/health';
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return $httpCode === 200 ? 'ok' : 'unreachable';
        } catch (\Exception $e) {
            return 'unreachable';
        }
    }

    /**
     * Check SNOMED Service
     */
    protected function checkSnomedService()
    {
        if ($this->isTestMode()) {
            return 'skipped';
        }

        try {
            $url = config('AbdmGateway')->snomedUrl . '/health';
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return $httpCode === 200 ? 'ok' : 'unreachable';
        } catch (\Exception $e) {
            return 'unreachable';
        }
    }

    protected function isTestMode(): bool
    {
        if ($this->authHospitalMode !== null) {
            return strtolower($this->authHospitalMode) !== 'live';
        }

        return (bool) config('AbdmGateway')->testMode;
    }

    // ─── ABDM M1 RSA Encryption Helpers ─────────────────────────────────────

    /** Runtime cache for ABDM M1 RSA public key. */
    private ?string $abdmPublicKey = null;

    /**
     * Fetch (and cache for the request lifetime) ABDM's RSA public key
     * from the certificate endpoint.
     */
    protected function getAbdmPublicKey(): string
    {
        if ($this->abdmPublicKey !== null) {
            return $this->abdmPublicKey;
        }

        $certUrl    = rtrim(config('AbdmGateway')->m1BaseUrl, '/') . '/abha/api/v3/profile/public/certificate';
        $abdmToken  = $this->getAbdmAccessToken();
        $ch = curl_init($certUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $abdmToken,
                'Accept: application/json',
                'REQUEST-ID: ' . $this->generateAbdmRequestId(),
                'TIMESTAMP: '  . gmdate('Y-m-d\TH:i:s.000\Z'),
            ],
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);

        if (!$resp) {
            return $this->abdmPublicKey = '';
        }

        $data = json_decode((string) $resp, true);
        // ABDM returns: { "publicKey": "-----BEGIN PUBLIC KEY-----\n..." }
        $this->abdmPublicKey = $data['publicKey'] ?? (string) $resp;
        return $this->abdmPublicKey;
    }

    /**
     * Generate a UUID v4 formatted request ID for ABDM headers.
     */
    private function generateAbdmRequestId(): string
    {
        $b = random_bytes(16);
        $b[6] = chr(ord($b[6]) & 0x0f | 0x40);
        $b[8] = chr(ord($b[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b), 4));
    }

    /**
     * RSA-OAEP encrypt $plain using ABDM's public key.
     * Returns base64-encoded ciphertext.
     * Throws RuntimeException on failure so callers can log it.
     */
    protected function encryptAbdmData(string $plain): string
    {
        $rawKey = $this->getAbdmPublicKey();
        if ($rawKey === '') {
            throw new \RuntimeException('Could not fetch ABDM public key for RSA encryption.');
        }

        // ABDM returns a raw base64 DER (SubjectPublicKeyInfo) without PEM headers.
        // Wrap it if headers are absent so openssl_pkey_get_public() can load it.
        if (strpos($rawKey, '-----BEGIN') === false) {
            $b64 = preg_replace('/\s+/', '', $rawKey);
            $pem = "-----BEGIN PUBLIC KEY-----\n"
                 . chunk_split($b64, 64, "\n")
                 . "-----END PUBLIC KEY-----\n";
        } else {
            $pem = $rawKey;
        }

        $keyResource = openssl_pkey_get_public($pem);
        if ($keyResource === false) {
            throw new \RuntimeException('ABDM public key is invalid: ' . openssl_error_string());
        }

        $encrypted = '';
        $ok = openssl_public_encrypt($plain, $encrypted, $keyResource, OPENSSL_PKCS1_OAEP_PADDING);
        if (!$ok) {
            throw new \RuntimeException('RSA-OAEP encryption failed: ' . openssl_error_string());
        }

        return base64_encode($encrypted);
    }

    /**
     * Send a pre-built payload to an ABDM M1 upstream endpoint.
     * Handles auth token, curl, logging, and response normalisation.
     */
    protected function sendM1Request(string $requestId, string $gatewayEndpoint, string $upstreamPath, array $body)
    {
        $this->bootRepositories();
        $startTime = microtime(true);
        try {
            $abdmUrl   = rtrim(config('AbdmGateway')->m1BaseUrl, '/') . '/' . ltrim($upstreamPath, '/');
            $abdmToken = $this->getAbdmAccessToken();

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $abdmUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => (int) config('AbdmGateway')->m3Timeout,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($body),
                CURLOPT_HTTPHEADER     => [
                    'Authorization: Bearer ' . $abdmToken,
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'X-Client-ID: '   . config('AbdmGateway')->sourceCode,
                    'REQUEST-ID: '    . $this->generateAbdmRequestId(),
                    'TIMESTAMP: '     . gmdate('Y-m-d\TH:i:s.000\Z'),
                ],
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $rawResponse = curl_exec($ch);
            $httpCode    = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError   = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                throw new \RuntimeException($curlError);
            }

            $responseTime = round((microtime(true) - $startTime) * 1000);
            $this->logRequest($requestId, 'POST', $gatewayEndpoint, $httpCode, 'valid', null, $responseTime, $rawResponse);

            $decoded = json_decode((string) $rawResponse, true);
            $payload = is_array($decoded) ? $decoded : ['raw_response' => trim((string) $rawResponse)];

            return $this->response->setStatusCode($httpCode)->setJSON([
                'ok'         => $httpCode >= 200 && $httpCode < 300 ? 1 : 0,
                'data'       => $payload,
                'request_id' => $requestId,
            ]);
        } catch (\Throwable $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000);
            $this->logRequest($requestId, 'POST', $gatewayEndpoint, 500, 'valid', $e->getMessage(), $responseTime);
            return $this->response->setStatusCode(500)->setJSON([
                'ok'         => 0,
                'error'      => $e->getMessage(),
                'request_id' => $requestId,
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Generic pass-through proxy for ABDM M1 endpoints.
     */
    protected function proxyM1Endpoint(string $gatewayEndpoint, string $upstreamPath)
    {
        $requestId = $this->generateRequestId();
        $authStatus = $this->validateBearer();
        if ($authStatus !== 'valid') {
            $this->logRequest($requestId, 'POST', $gatewayEndpoint, 403, $authStatus, 'Invalid or missing bearer token');
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => 0,
                'error' => 'Invalid authorization token',
                'request_id' => $requestId,
            ]);
        }

        $rawBody = (string) $this->request->getBody();
        $trimmedBody = trim($rawBody);
        $decodedBody = $trimmedBody === '' ? [] : json_decode($trimmedBody, true);

        if ($trimmedBody !== '' && !is_array($decodedBody) && json_last_error() !== JSON_ERROR_NONE) {
            $this->logRequest(
                $requestId,
                'POST',
                $gatewayEndpoint,
                400,
                'valid',
                'Invalid JSON body: ' . json_last_error_msg()
            );

            return $this->response->setStatusCode(400)->setJSON([
                'ok' => 0,
                'error' => 'invalid_json',
                'message' => 'Request body contains invalid JSON',
                'request_id' => $requestId,
            ]);
        }

        $body = is_array($decodedBody) ? $decodedBody : [];

        if ($this->isTestMode()) {
            $mock = [
                'ok' => 1,
                'mode' => 'test',
                'request_id' => $requestId,
                'data' => [
                    'gateway_endpoint' => $gatewayEndpoint,
                    'upstream_path' => $upstreamPath,
                    'message' => 'Mock response in test mode',
                ],
            ];

            $this->logTestSubmission(
                $requestId,
                $gatewayEndpoint,
                $body,
                $mock,
                200,
                'abdm.m1.proxy'
            );

            return $this->response->setJSON($mock);
        }

        $this->bootRepositories();
        $startTime = microtime(true);

        try {
            $m1BaseUrl = rtrim((string) config('AbdmGateway')->m1BaseUrl, '/');
            $abdmUrl = $m1BaseUrl . '/' . ltrim($upstreamPath, '/');
            $abdmToken = $this->getAbdmAccessToken();

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $abdmUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => (int) config('AbdmGateway')->m3Timeout,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($body),
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $abdmToken,
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'X-Client-ID: ' . config('AbdmGateway')->sourceCode,
                    'REQUEST-ID: '  . $this->generateAbdmRequestId(),
                    'TIMESTAMP: '   . gmdate('Y-m-d\\TH:i:s.000\\Z'),
                ],
                CURLOPT_SSL_VERIFYPEER => false,
            ]);

            $rawResponse = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                throw new \RuntimeException($curlError);
            }

            $responseTime = round((microtime(true) - $startTime) * 1000);
            $this->logRequest($requestId, 'POST', $gatewayEndpoint, $httpCode, 'valid', null, $responseTime, $rawResponse);

            $decoded = json_decode((string) $rawResponse, true);
            $payload = is_array($decoded)
                ? $decoded
                : ['raw_response' => trim((string) $rawResponse)];

            return $this->response->setStatusCode($httpCode)->setJSON([
                'ok' => $httpCode >= 200 && $httpCode < 300 ? 1 : 0,
                'data' => $payload,
                'request_id' => $requestId,
            ]);
        } catch (\Throwable $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000);
            $this->logRequest($requestId, 'POST', $gatewayEndpoint, 500, 'valid', $e->getMessage(), $responseTime);

            return $this->response->setStatusCode(500)->setJSON([
                'ok' => 0,
                'error' => $e->getMessage(),
                'request_id' => $requestId,
            ]);
        }
    }

    /**
     * Resolve ABDM access token.
     * Priority:
     * 1) Static ABDM_TOKEN (legacy)
     * 2) Client credentials using ABDM_CLIENT_ID/ABDM_CLIENT_SECRET
     */
    protected function getAbdmAccessToken(): string
    {
        if ($this->cachedAbdmToken !== null && $this->cachedAbdmToken !== '') {
            return $this->cachedAbdmToken;
        }

        $cfg = config('AbdmGateway');

        $staticToken = trim((string) ($cfg->m3Token ?? ''));
        if ($staticToken !== '') {
            $this->cachedAbdmToken = $staticToken;
            return $this->cachedAbdmToken;
        }

        $clientId = trim((string) ($cfg->abdmClientId ?? ''));
        $clientSecret = trim((string) ($cfg->abdmClientSecret ?? ''));
        $authUrl = trim((string) ($cfg->abdmAuthUrl ?? ''));

        if ($clientId === '' || $clientSecret === '' || $authUrl === '') {
            throw new \RuntimeException('ABDM auth is not configured. Set ABDM_TOKEN or ABDM_CLIENT_ID/ABDM_CLIENT_SECRET/ABDM_AUTH_URL.');
        }

        $payload = [
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $authUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new \RuntimeException('ABDM auth curl error: ' . $curlError);
        }

        $decoded = json_decode((string) $response, true);
        $token = '';
        if (is_array($decoded)) {
            $token = (string) ($decoded['accessToken'] ?? $decoded['token'] ?? $decoded['authToken'] ?? '');
        }

        if ($httpCode < 200 || $httpCode >= 300 || $token === '') {
            $preview = is_string($response) ? substr($response, 0, 300) : '';
            throw new \RuntimeException('ABDM auth failed (HTTP ' . $httpCode . '). Response: ' . $preview);
        }

        $this->cachedAbdmToken = $token;
        return $this->cachedAbdmToken;
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    protected function resolveBridgeRoute(string $eventType, array $payload): array
    {
        switch ($eventType) {
            case 'abdm.abha.validate':
                return [
                    'ok' => true,
                    'method' => 'POST',
                    'path' => '/api/v3/abha/validate',
                    'body' => [
                        'abha_id' => (string) ($payload['abha_id'] ?? ''),
                        'abha_address' => (string) ($payload['abha_address'] ?? ''),
                    ],
                ];

            case 'abdm.consent.requested':
                return [
                    'ok' => true,
                    'method' => 'POST',
                    'path' => '/api/v3/consent/request',
                    'body' => [
                        'patient_abha' => (string) ($payload['abha_id'] ?? $payload['patient_abha'] ?? ''),
                        'purpose' => (string) ($payload['purpose_code'] ?? $payload['purpose'] ?? 'TREATMENT'),
                        'hi_types' => $payload['hi_types'] ?? ['OPConsultation'],
                    ],
                ];

            case 'abdm.fhir.share.requested':
            case 'abdm.opd.prescription.share.requested':
            case 'abdm.ipd.admission.share.requested':
            case 'abdm.ipd.discharge.share.requested':
            case 'abdm.diagnosis.report.share.requested':
                return [
                    'ok' => true,
                    'method' => 'POST',
                    'path' => '/api/v3/bundle/push',
                    'body' => [
                        'consent_id' => (string) ($payload['consent_handle'] ?? $payload['consent_id'] ?? 'CONS-BRIDGE'),
                        'hi_type' => (string) ($payload['hi_type'] ?? 'OPConsultation'),
                        'fhir_bundle' => $payload['bundle'] ?? $payload['fhir_bundle'] ?? [],
                    ],
                ];

            case 'abdm.scan_share.lookup':
                return [
                    'ok' => true,
                    'method' => 'GET',
                    'path' => '/api/v3/snomed/search',
                    'query' => [
                        'term' => (string) ($payload['term'] ?? $payload['qr_payload'] ?? 'fever'),
                        'return_limit' => (string) ($payload['return_limit'] ?? '10'),
                    ],
                ];

            default:
                return [
                    'ok' => false,
                    'error' => 'No bridge mapping for event_type: ' . $eventType,
                ];
        }
    }

    /**
     * @param array<string,mixed> $route
     * @return array<string,mixed>
     */
    protected function dispatchBridgeRoute(array $route): array
    {
        $method = strtoupper((string) ($route['method'] ?? 'POST'));
        $path = (string) ($route['path'] ?? '');
        $query = isset($route['query']) && is_array($route['query']) ? $route['query'] : [];
        $body = isset($route['body']) && is_array($route['body']) ? $route['body'] : [];

        $baseUrl = rtrim((string) (config('AbdmGateway')->publicUrl ?? 'http://127.0.0.1'), '/');
        $url = $baseUrl . $path;
        if ($method === 'GET' && $query !== []) {
            $url .= '?' . http_build_query($query);
        }

        $ch = curl_init();
        $headers = ['Content-Type: application/json'];

        $bearer = trim((string) (config('AbdmGateway')->bearerToken ?? ''));
        if ($bearer !== '') {
            $headers[] = 'Authorization: Bearer ' . $bearer;
        }

        $curlOptions = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CUSTOMREQUEST => $method,
        ];

        if ($method !== 'GET') {
            $curlOptions[CURLOPT_POSTFIELDS] = json_encode($body);
        }

        curl_setopt_array($ch, $curlOptions);

        $raw = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError !== '') {
            return [
                'ok' => 0,
                'http_code' => 500,
                'error' => $curlError,
                'target_url' => $url,
            ];
        }

        $decoded = json_decode((string) $raw, true);

        return [
            'ok' => $httpCode >= 200 && $httpCode < 300 ? 1 : 0,
            'http_code' => $httpCode,
            'target_url' => $url,
            'response' => is_array($decoded) ? $decoded : ['raw' => (string) $raw],
        ];
    }
}
