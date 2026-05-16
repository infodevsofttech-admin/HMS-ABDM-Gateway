<?php

namespace App\Controllers;

use App\Models\AbdmHospital;
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
     * ABHA Card Download
     * GET /api/v3/abha/card?token=<x_token>
     *
     * Returns the official ABHA card image (PNG/SVG) as base64 in JSON.
     * The `token` parameter is the X-Token returned by mobile/aadhaar verify-otp.
     */
    public function abhaCard()
    {
        $requestId  = $this->generateRequestId();
        $authStatus = $this->validateBearer();
        if ($authStatus !== 'valid') {
            $this->logRequest($requestId, 'GET', '/api/v3/abha/card', 403,
                              $authStatus, 'Invalid or missing bearer token');
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => 0, 'error' => 'Unauthorized', 'request_id' => $requestId,
            ]);
        }

        $xToken = trim((string) $this->request->getGet('token'));
        if ($xToken === '') {
            return $this->response->setStatusCode(400)->setJSON([
                'ok'         => 0,
                'error'      => 'token parameter required (X-Token value from verify-otp response)',
                'request_id' => $requestId,
            ]);
        }

        if ($this->isTestMode()) {
            // 1×1 transparent PNG placeholder for test mode
            $mockPng = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAC0lEQVQI12NgAAIABQAAbjAAAAABJRU5ErkJggg==';
            return $this->response->setJSON([
                'ok'         => 1,
                'mode'       => 'test',
                'request_id' => $requestId,
                'data'       => [
                    'card_format' => 'png',
                    'card_data'   => $mockPng,
                    'message'     => 'Mock ABHA card in test mode',
                ],
            ]);
        }

        $this->bootRepositories();
        $startTime = microtime(true);
        $cfg       = config('AbdmGateway');
        $abdmToken = $this->getAbdmAccessToken();
        $abdmReqId = $this->generateAbdmRequestId();
        $ep        = '/api/v3/abha/card';

        try {
            $cardUrl = rtrim($cfg->m1BaseUrl, '/') . '/abha/api/v3/profile/account/abha-card';
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $cardUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_HEADER         => true,
                CURLOPT_HTTPHEADER     => [
                    'Accept: image/png, image/svg+xml, */*',
                    'Authorization: Bearer ' . $abdmToken,
                    'X-Token: Bearer ' . $xToken,
                    'REQUEST-ID: ' . $abdmReqId,
                    'TIMESTAMP: ' . gmdate('Y-m-d\TH:i:s.000\Z'),
                ],
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $raw          = curl_exec($ch);
            $httpCode     = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $hdrSize      = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $curlErr      = curl_error($ch);
            curl_close($ch);

            if ($curlErr) {
                throw new \RuntimeException('cURL error: ' . $curlErr);
            }

            $respHeaders  = substr((string) $raw, 0, $hdrSize);
            $body         = substr((string) $raw, $hdrSize);
            $responseTime = round((microtime(true) - $startTime) * 1000);

            if ($httpCode >= 400) {
                $this->logRequest($requestId, 'GET', $ep, $httpCode, 'valid', 'ABDM card error HTTP ' . $httpCode, $responseTime, substr($body, 0, 500));
                return $this->response->setStatusCode(502)->setJSON([
                    'ok' => 0, 'error' => 'ABDM returned HTTP ' . $httpCode, 'request_id' => $requestId,
                ]);
            }

            // Detect content type from ABDM response headers
            $contentType = 'image/png';
            if (preg_match('/content-type:\s*([^\r\n]+)/i', $respHeaders, $m)) {
                $contentType = trim(explode(';', $m[1])[0]);
            }

            // Unwrap JSON wrapper if ABDM returns base64 inside JSON
            if (str_contains($contentType, 'application/json') || str_contains($contentType, 'text/')) {
                $decoded = json_decode($body, true);
                if (is_array($decoded)) {
                    $b64 = $decoded['image'] ?? $decoded['data'] ?? $decoded['abhaCard'] ?? null;
                    if ($b64 !== null) {
                        $this->logRequest($requestId, 'GET', $ep, 200, 'valid', null, $responseTime, $body);
                        return $this->response->setJSON([
                            'ok'         => 1,
                            'request_id' => $requestId,
                            'data'       => ['card_format' => 'png', 'card_data' => (string) $b64],
                        ]);
                    }
                }
            }

            $this->logRequest($requestId, 'GET', $ep, 200, 'valid', null, $responseTime, '[binary ' . strlen($body) . ' bytes]');
            return $this->response->setJSON([
                'ok'         => 1,
                'request_id' => $requestId,
                'data'       => ['card_format' => 'png', 'card_data' => base64_encode($body)],
            ]);

        } catch (\Throwable $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000);
            $this->logRequest($requestId, 'GET', $ep, 500, 'valid', $e->getMessage(), $responseTime);
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => 0, 'error' => $e->getMessage(), 'request_id' => $requestId,
            ]);
        }
    }

    // =====================================================================
    // M1 COMPLETION — Missing enrollment / account / login endpoints
    // =====================================================================

    /**
     * POST /api/v3/abha/enrol/auth
     * ABDM: POST /abha/api/v3/enrollment/auth/byAbdm
     * Used after ABHA creation to verify/update alternate mobile number.
     * Requires T1 X-Token from enrolByAadhaar response.
     */
    public function abhaEnrolAuth(): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->proxyM1WithXToken(
            '/api/v3/abha/enrol/auth',
            '/abha/api/v3/enrollment/auth/byAbdm'
        );
    }

    /**
     * POST /api/v3/abha/account/search
     * ABDM: POST /abha/api/v3/profile/account/abha/search
     * Search for an existing ABHA account by ABHA number.
     * Requires X-Token (user-level session token).
     */
    public function abhaAccountSearch(): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->proxyM1WithXToken(
            '/api/v3/abha/account/search',
            '/abha/api/v3/profile/account/abha/search'
        );
    }

    /**
     * GET /api/v3/abha/account/abha-card
     * ABDM: GET /abha/api/v3/profile/account/abha-card
     * Download ABHA card PNG using profile-level X-Token.
     * (Thin alternative to /abha/card — same upstream path, identical logic.)
     */
    public function abhaAccountCard(): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->proxyM1Get(
            '/api/v3/abha/account/abha-card',
            '/abha/api/v3/profile/account/abha-card'
        );
    }

    /**
     * POST /api/v3/abha/account/email/request-verify
     * ABDM: POST /abha/api/v3/profile/account/request/emailVerificationLink
     * Trigger email verification link for linked email address.
     * Requires X-Token.
     */
    public function abhaAccountEmailVerify(): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->proxyM1WithXToken(
            '/api/v3/abha/account/email/request-verify',
            '/abha/api/v3/profile/account/request/emailVerificationLink'
        );
    }

    /**
     * POST /api/v3/abha/login/search
     * ABDM: POST /abha/api/v3/profile/login/search
     * Password-based login: send ABHA address/number to find login methods.
     * No X-Token required (pre-auth step).
     */
    public function abhaLoginSearch(): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->proxyM1Endpoint(
            '/api/v3/abha/login/search',
            '/abha/api/v3/profile/login/search'
        );
    }

    // =====================================================================
    // ABDM Bridge URL Management (one-time setup / admin use)
    // =====================================================================

    /**
     * POST /api/v3/gateway/register-bridge
     * Registers this gateway URL with ABDM (PATCH gateway/v1/bridges).
     * Body: {"url": "https://abdm-bridge.e-atria.in"}
     * Only callable with valid bearer token; admin use only.
     */
    public function registerBridgeUrl(): \CodeIgniter\HTTP\ResponseInterface
    {
        $requestId  = $this->generateRequestId();
        $authStatus = $this->validateBearer();
        if ($authStatus !== 'valid') {
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => 0, 'error' => 'Unauthorized', 'request_id' => $requestId,
            ]);
        }

        $body = (array) ($this->request->getJSON(true) ?? []);
        $url  = trim((string) ($body['url'] ?? ''));
        if ($url === '') {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => 0, 'error' => 'url field required', 'request_id' => $requestId,
            ]);
        }

        if ($this->isTestMode()) {
            return $this->response->setJSON([
                'ok' => 1, 'mode' => 'test', 'message' => 'Mock bridge register — test mode',
                'request_id' => $requestId,
            ]);
        }

        $this->bootRepositories();
        $cfg       = config('AbdmGateway');
        $abdmToken = $this->getAbdmAccessToken();
        $abdmReqId = $this->generateAbdmRequestId();

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => 'https://dev.abdm.gov.in/gateway/v1/bridges',
            CURLOPT_CUSTOMREQUEST  => 'PATCH',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_POSTFIELDS     => json_encode(['url' => $url]),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $abdmToken,
                'REQUEST-ID: ' . $abdmReqId,
                'TIMESTAMP: ' . gmdate('Y-m-d\TH:i:s.000\Z'),
            ],
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $respBody = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            return $this->response->setStatusCode(502)->setJSON([
                'ok' => 0, 'error' => 'cURL: ' . $curlErr, 'request_id' => $requestId,
            ]);
        }

        $decoded = json_decode((string) $respBody, true);
        return $this->response->setStatusCode($httpCode >= 200 && $httpCode < 300 ? 200 : $httpCode)->setJSON([
            'ok'         => ($httpCode >= 200 && $httpCode < 300) ? 1 : 0,
            'http_code'  => $httpCode,
            'abdm'       => $decoded ?? (string) $respBody,
            'request_id' => $requestId,
        ]);
    }

    /**
     * GET /api/v3/gateway/bridge-services
     * Returns the bridge services currently registered with ABDM.
     */
    public function getBridgeServices(): \CodeIgniter\HTTP\ResponseInterface
    {
        $requestId  = $this->generateRequestId();
        $authStatus = $this->validateBearer();
        if ($authStatus !== 'valid') {
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => 0, 'error' => 'Unauthorized', 'request_id' => $requestId,
            ]);
        }

        if ($this->isTestMode()) {
            return $this->response->setJSON([
                'ok' => 1, 'mode' => 'test', 'message' => 'Mock bridge services — test mode',
                'request_id' => $requestId,
            ]);
        }

        $this->bootRepositories();
        $abdmToken = $this->getAbdmAccessToken();
        $abdmReqId = $this->generateAbdmRequestId();

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => 'https://dev.abdm.gov.in/gateway/v1/bridges/getServices',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Authorization: Bearer ' . $abdmToken,
                'REQUEST-ID: ' . $abdmReqId,
                'TIMESTAMP: ' . gmdate('Y-m-d\TH:i:s.000\Z'),
            ],
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $respBody = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            return $this->response->setStatusCode(502)->setJSON([
                'ok' => 0, 'error' => 'cURL: ' . $curlErr, 'request_id' => $requestId,
            ]);
        }

        $decoded = json_decode((string) $respBody, true);
        return $this->response->setJSON([
            'ok'         => ($httpCode >= 200 && $httpCode < 300) ? 1 : 0,
            'http_code'  => $httpCode,
            'abdm'       => $decoded ?? (string) $respBody,
            'request_id' => $requestId,
        ]);
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
            // Resolve hospital_id from hipId (hfr_id) so token appears in the right hospital's OPD queue
            $hospitalRow = (new AbdmHospital())->where('hfr_id', $hipId)->first();
            $hospitalId  = $hospitalRow ? (int) $hospitalRow->id : null;

            $tokenQueue  = new AbdmTokenQueue();
            $tokenNumber = $tokenQueue->nextTokenNumber();
            $today       = date('Y-m-d');

            $tokenQueue->insert([
                'hospital_id'       => $hospitalId,
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

    // ==================== OPD Queue API ====================

    /**
     * Fetch OPD token queue for the authenticated hospital.
     * GET /api/v3/opd/queue
     *
     * Query params:
     *   date   (Y-m-d, default: today)
     *   status (PENDING|CALLED|COMPLETED|CANCELLED, default: all)
     *   page   (int, default: 1)
     *   limit  (int, 1-100, default: 50)
     */
    public function opdQueueList()
    {
        $authStatus = $this->validateBearer();
        if ($authStatus !== 'valid') {
            return $this->response->setStatusCode(401)->setJSON(['ok' => 0, 'error' => 'unauthorized']);
        }

        $hospitalId = $this->authHospitalId;
        if (!$hospitalId) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => 0, 'error' => 'hospital_not_resolved']);
        }

        $date   = trim((string) ($this->request->getGet('date')   ?: date('Y-m-d')));
        $status = strtoupper(trim((string) ($this->request->getGet('status') ?? '')));
        $limit  = max(1, min(100, (int) ($this->request->getGet('limit') ?: 50)));
        $page   = max(1, (int) ($this->request->getGet('page') ?: 1));
        $offset = ($page - 1) * $limit;

        // Validate date format
        if (!\DateTime::createFromFormat('Y-m-d', $date)) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error' => 'invalid_date_format', 'message' => 'Use Y-m-d format, e.g. 2026-05-16']);
        }

        $validStatuses = ['PENDING', 'CALLED', 'COMPLETED', 'CANCELLED'];

        $tokenModel = new AbdmTokenQueue();
        $builder = $tokenModel->where('hospital_id', $hospitalId)->where('token_date', $date);

        if ($status !== '' && in_array($status, $validStatuses, true)) {
            $builder = $builder->where('status', $status);
        }

        $total  = $builder->countAllResults(false);
        $tokens = $builder->orderBy('token_number', 'ASC')->findAll($limit, $offset);

        $counts = [];
        foreach ($validStatuses as $s) {
            $counts[strtolower($s)] = $tokenModel->where('hospital_id', $hospitalId)
                ->where('token_date', $date)->where('status', $s)->countAllResults();
        }

        return $this->response->setJSON([
            'ok'   => 1,
            'date' => $date,
            'pagination' => [
                'total'  => $total,
                'page'   => $page,
                'limit'  => $limit,
                'pages'  => (int) ceil($total / $limit),
            ],
            'summary' => $counts,
            'tokens'  => array_map(static function ($t): array {
                $t = (array) $t;
                return [
                    'id'           => (int) $t['id'],
                    'token_number' => (int) $t['token_number'],
                    'patient_name' => $t['patient_name'] ?? '',
                    'abha_number'  => $t['abha_number']  ?? null,
                    'abha_address' => $t['abha_address'] ?? null,
                    'gender'       => $t['gender']       ?? null,
                    'dob'          => isset($t['day_of_birth'], $t['month_of_birth'], $t['year_of_birth'])
                                      ? sprintf('%04d-%02d-%02d', $t['year_of_birth'], $t['month_of_birth'], $t['day_of_birth'])
                                      : null,
                    'phone'        => $t['phone']        ?? null,
                    'department'   => $t['context']      ?? null,
                    'status'       => $t['status']       ?? 'PENDING',
                    'source'       => empty($t['abha_number']) ? 'manual' : 'scan_share',
                    'created_at'   => $t['created_at']   ?? null,
                    'updated_at'   => $t['updated_at']   ?? null,
                ];
            }, $tokens),
        ]);
    }

    /**
     * Add a manual OPD token (walk-in patient without ABHA scan).
     * POST /api/v3/opd/token
     *
     * Body (JSON):
     *   patient_name  string  required
     *   phone         string  optional
     *   abha_number   string  optional
     *   gender        string  optional (M/F/O)
     *   department    string  optional (default: General OPD)
     *   date          string  optional (Y-m-d, default: today)
     */
    public function opdTokenCreate()
    {
        $authStatus = $this->validateBearer();
        if ($authStatus !== 'valid') {
            return $this->response->setStatusCode(401)->setJSON(['ok' => 0, 'error' => 'unauthorized']);
        }

        $hospitalId = $this->authHospitalId;
        if (!$hospitalId) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => 0, 'error' => 'hospital_not_resolved']);
        }

        $body = (array) ($this->request->getJSON(true) ?? []);
        $patientName = trim((string) ($body['patient_name'] ?? ''));
        if ($patientName === '') {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error' => 'patient_name_required']);
        }

        $tokenDate = trim((string) ($body['date'] ?? date('Y-m-d')));
        if (!\DateTime::createFromFormat('Y-m-d', $tokenDate)) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error' => 'invalid_date_format']);
        }

        $tokenModel  = new AbdmTokenQueue();
        $tokenNumber = $tokenModel->where('hospital_id', $hospitalId)
            ->where('token_date', $tokenDate)->countAllResults() + 1;

        $departmentCode = trim((string) ($body['department_code'] ?? '')) ?: null;

        $id = $tokenModel->insert([
            'hospital_id'     => $hospitalId,
            'patient_name'    => $patientName,
            'phone'           => trim((string) ($body['phone']       ?? '')),
            'abha_number'     => trim((string) ($body['abha_number'] ?? '')) ?: null,
            'gender'          => trim((string) ($body['gender']      ?? '')) ?: null,
            'context'         => trim((string) ($body['department']  ?? '')) ?: 'General OPD',
            'department_code' => $departmentCode,
            'token_number'    => $tokenNumber,
            'token_date'      => $tokenDate,
            'status'          => 'PENDING',
        ]);

        $token = $tokenModel->find($id);

        return $this->response->setStatusCode(201)->setJSON([
            'ok'           => 1,
            'token_number' => $tokenNumber,
            'token_id'     => $id,
            'patient_name' => $patientName,
            'date'         => $tokenDate,
            'status'       => 'PENDING',
            'token'        => $token,
        ]);
    }

    /**
     * Update the status of an OPD token.
     * PATCH /api/v3/opd/token/{id}
     *
     * Body (JSON):
     *   status  string  required  (CALLED|COMPLETED|CANCELLED|PENDING)
     */
    public function opdTokenUpdateStatus(int $id)
    {
        $authStatus = $this->validateBearer();
        if ($authStatus !== 'valid') {
            return $this->response->setStatusCode(401)->setJSON(['ok' => 0, 'error' => 'unauthorized']);
        }

        $hospitalId = $this->authHospitalId;
        if (!$hospitalId) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => 0, 'error' => 'hospital_not_resolved']);
        }

        $body   = (array) ($this->request->getJSON(true) ?? []);
        $status = strtoupper(trim((string) ($body['status'] ?? '')));

        $allowed = ['PENDING', 'CALLED', 'COMPLETED', 'CANCELLED'];
        if (!in_array($status, $allowed, true)) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => 0, 'error' => 'invalid_status',
                'allowed' => $allowed,
            ]);
        }

        $tokenModel = new AbdmTokenQueue();
        $token = $tokenModel->where('id', $id)->where('hospital_id', $hospitalId)->first();
        if ($token === null) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => 0, 'error' => 'token_not_found']);
        }

        $tokenModel->update($id, ['status' => $status]);

        return $this->response->setJSON([
            'ok'           => 1,
            'token_id'     => $id,
            'token_number' => (int) $token['token_number'],
            'patient_name' => $token['patient_name'],
            'status'       => $status,
        ]);
    }

    /**
     * GET /api/v3/opd/running-token-status
     * Returns the current running token number and average wait time for a HIP from ABDM.
     *
     * Query params:
     *   hip_id   string  HIP ID to query (defaults to authenticated hospital's hfr_id)
     *   context  string  Department/counter context (default "1")
     */
    public function opdRunningTokenStatus()
    {
        $authStatus = $this->validateBearer();
        if ($authStatus !== 'valid') {
            return $this->response->setStatusCode(401)->setJSON(['ok' => 0, 'error' => 'unauthorized']);
        }

        $cfg     = config('AbdmGateway');
        $hipId   = trim((string) ($this->request->getGet('hip_id') ?? ''));
        $context = trim((string) ($this->request->getGet('context') ?? '1'));

        // Resolve hip_id from authenticated hospital when not supplied
        if ($hipId === '' && $this->authHospitalId) {
            $hospital = (new AbdmHospital())->find($this->authHospitalId);
            $hipId    = trim((string) (is_array($hospital) ? ($hospital['hfr_id'] ?? '') : ($hospital->hfr_id ?? '')));
        }
        if ($hipId === '') {
            $hipId = trim((string) $cfg->hfrId);
        }
        if ($hipId === '') {
            return $this->response->setStatusCode(400)->setJSON(['ok' => 0, 'error' => 'hip_id_required']);
        }

        if ($this->isTestMode()) {
            return $this->response->setJSON([
                'ok'                           => 1,
                'test_mode'                    => true,
                'hip_id'                       => $hipId,
                'context'                      => $context,
                'running_token_number'         => '10',
                'average_service_time_minutes' => 3,
            ]);
        }

        try {
            $abdmToken = $this->getAbdmAccessToken();
            $hfrId     = $cfg->hfrId ?: $hipId;

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => 'https://dev.abdm.gov.in/api/hiecm/patient-share/v3/running-token/status',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode(['hipId' => $hipId, 'context' => $context]),
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'Authorization: Bearer ' . $abdmToken,
                    'REQUEST-ID: ' . $this->generateRequestId(),
                    'TIMESTAMP: ' . gmdate('Y-m-d\TH:i:s.000\Z'),
                    'X-CM-ID: sbx',
                    'X-HIU-ID: ' . $hfrId,
                    'X-AUTH-TOKEN: ' . $abdmToken,
                ],
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $raw      = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $decoded = json_decode((string) $raw, true);
            if (!is_array($decoded)) {
                $decoded = ['raw' => (string) $raw];
            }

            if ($httpCode >= 400) {
                return $this->response->setStatusCode($httpCode)->setJSON(['ok' => 0, 'error' => 'abdm_error', 'details' => $decoded]);
            }

            $tokenData = is_array($decoded['token'] ?? null) ? $decoded['token'] : [];

            return $this->response->setJSON([
                'ok'                           => 1,
                'hip_id'                       => $hipId,
                'context'                      => $context,
                'running_token_number'         => $tokenData['runningTokenNumber'] ?? null,
                'average_service_time_minutes' => $tokenData['averageTokenServiceTimeInMinutes'] ?? null,
                'raw'                          => $decoded,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'opdRunningTokenStatus: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['ok' => 0, 'error' => $e->getMessage()]);
        }
    }

    // ==================== Face Auth ====================

    /**
     * Step 1 — Face Auth Enrollment: Initialise transaction.
     * POST /api/v3/abha/face/enrol/init
     *
     * No body required from HMS.  Gateway injects the required scope.
     * Returns: { txnId }
     */
    public function abhaFaceEnrolInit()
    {
        $ep        = '/api/v3/abha/face/enrol/init';
        $requestId = $this->generateRequestId();

        $authStatus = $this->validateBearer();
        if ($authStatus !== 'valid') {
            $this->logRequest($requestId, 'POST', $ep, 403, $authStatus, 'Invalid or missing bearer token');
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => 0, 'error' => 'Invalid authorization token', 'request_id' => $requestId,
            ]);
        }

        if ($this->isTestMode()) {
            $mock = [
                'ok' => 1, 'mode' => 'test', 'request_id' => $requestId,
                'data' => ['txnId' => 'TEST-FACE-TXN-' . uniqid(), 'message' => 'Test mode: face auth transaction initialised.'],
            ];
            $this->logTestSubmission($requestId, $ep, [], $mock, 200, 'abdm.face.enrol.init');
            return $this->response->setJSON($mock);
        }

        $abdmPayload = ['scope' => ['abha-enrol', 'face-verify']];
        return $this->sendM1Request($requestId, $ep, '/abha/api/v3/enrollment/enrol/auth/init', $abdmPayload);
    }

    /**
     * Step 2 — Face Auth (shared): Retrieve PID challenge from ABDM biometric device bridge.
     * POST /api/v3/abha/face/capture-pid
     *
     * HMS sends: { "scope": ["abha-enrol","face-verify"], "txnId": "..." }
     *         or: { "scope": ["abha-login","face-verify"], "txnId": "..." }
     * (scope varies by flow; gateway passes it through unchanged)
     * Returns ABDM PID challenge XML consumed by the biometric device.
     */
    public function abhaFaceCapturePid()
    {
        $ep        = '/api/v3/abha/face/capture-pid';
        $requestId = $this->generateRequestId();

        $authStatus = $this->validateBearer();
        if ($authStatus !== 'valid') {
            $this->logRequest($requestId, 'POST', $ep, 403, $authStatus, 'Invalid or missing bearer token');
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => 0, 'error' => 'Invalid authorization token', 'request_id' => $requestId,
            ]);
        }

        $body  = json_decode((string) $this->request->getBody(), true) ?? [];
        $txnId = trim((string) ($body['txnId'] ?? $body['transactionId'] ?? ''));

        if ($txnId === '') {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => 0, 'error' => 'missing_txnId', 'message' => 'Field "txnId" is required.', 'request_id' => $requestId,
            ]);
        }

        if ($this->isTestMode()) {
            $mock = [
                'ok' => 1, 'mode' => 'test', 'request_id' => $requestId,
                'data' => ['txnId' => $txnId, 'message' => 'Test mode: PID challenge would be returned from ABDM.'],
            ];
            $this->logTestSubmission($requestId, $ep, $body, $mock, 200, 'abdm.face.capture-pid');
            return $this->response->setJSON($mock);
        }

        // Forward body as-is (scope+txnId) to ABDM capturePID
        return $this->sendM1Request($requestId, $ep, '/abha/api/v3/enrollment/enrol/capturePID', $body);
    }

    /**
     * Step 3 — Face Auth Enrollment: Submit biometric PID and create ABHA.
     * POST /api/v3/abha/face/enrol/submit
     *
     * HMS sends: { "aadhaar": "574287571374", "rdPidData": "<PidData>...</PidData>", "mobile": "9999999999" }
     * Gateway RSA-encrypts `aadhaar` and wraps into ABDM authData format.
     * Returns: ABHA profile
     */
    public function abhaFaceEnrolSubmit()
    {
        $ep        = '/api/v3/abha/face/enrol/submit';
        $requestId = $this->generateRequestId();

        $authStatus = $this->validateBearer();
        if ($authStatus !== 'valid') {
            $this->logRequest($requestId, 'POST', $ep, 403, $authStatus, 'Invalid or missing bearer token');
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => 0, 'error' => 'Invalid authorization token', 'request_id' => $requestId,
            ]);
        }

        $body        = json_decode((string) $this->request->getBody(), true) ?? [];
        $plainAadhaar = trim((string) ($body['aadhaar'] ?? ''));
        $rdPidData   = trim((string) ($body['rdPidData'] ?? $body['pid_data'] ?? $body['faceAuthPid'] ?? ''));
        $mobile      = trim((string) ($body['mobile'] ?? $body['mobileNumber'] ?? ''));

        if (!preg_match('/^\d{12}$/', $plainAadhaar)) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => 0, 'error' => 'invalid_aadhaar', 'message' => 'Field "aadhaar" must be a 12-digit number.', 'request_id' => $requestId,
            ]);
        }

        if ($rdPidData === '') {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => 0, 'error' => 'missing_rdPidData', 'message' => 'Field "rdPidData" (biometric PID data) is required.', 'request_id' => $requestId,
            ]);
        }

        if ($this->isTestMode()) {
            $mock = [
                'ok' => 1, 'mode' => 'test', 'request_id' => $requestId,
                'data' => ['message' => 'Test mode: ABHA would be created via face auth.', 'ABHAProfile' => ['ABHANumber' => '14-0000-0000-0000', 'mobile' => $mobile]],
            ];
            $this->logTestSubmission($requestId, $ep, ['aadhaar' => '***', 'mobile' => $mobile], $mock, 200, 'abdm.face.enrol.submit');
            return $this->response->setJSON($mock);
        }

        try {
            $encAadhaar = $this->encryptAbdmData($plainAadhaar);
        } catch (\Throwable $e) {
            $this->logRequest($requestId, 'POST', $ep, 500, 'valid', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => 0, 'error' => 'encryption_failed', 'message' => $e->getMessage(), 'request_id' => $requestId,
            ]);
        }

        $abdmPayload = [
            'authData' => [
                'authMethods' => ['face'],
                'face' => [
                    'aadhaar'   => $encAadhaar,
                    'rdPidData' => $rdPidData,
                    'mobile'    => $mobile,
                ],
            ],
            'consent' => [
                'code'    => 'abha-enrollment',
                'version' => '1.4',
            ],
        ];

        return $this->sendM1Request($requestId, $ep, '/abha/api/v3/enrollment/enrol/byAadhaar', $abdmPayload);
    }

    /**
     * Face Auth QR Login Step 1 — Search ABHA by mobile.
     * POST /api/v3/abha/face/login/search
     *
     * HMS sends: { "mobile": "9999999999" }
     * Gateway RSA-encrypts mobile, adds scope, forwards to ABDM.
     * Returns: { txnId, loginId (index) }
     */
    public function abhaFaceLoginSearch()
    {
        $ep        = '/api/v3/abha/face/login/search';
        $requestId = $this->generateRequestId();

        $authStatus = $this->validateBearer();
        if ($authStatus !== 'valid') {
            $this->logRequest($requestId, 'POST', $ep, 403, $authStatus, 'Invalid or missing bearer token');
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => 0, 'error' => 'Invalid authorization token', 'request_id' => $requestId,
            ]);
        }

        $body        = json_decode((string) $this->request->getBody(), true) ?? [];
        $plainMobile = trim((string) ($body['mobile'] ?? $body['mobileNumber'] ?? ''));

        if (!preg_match('/^\d{10}$/', $plainMobile)) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => 0, 'error' => 'invalid_mobile', 'message' => 'Field "mobile" must be a 10-digit number.', 'request_id' => $requestId,
            ]);
        }

        if ($this->isTestMode()) {
            $mock = [
                'ok' => 1, 'mode' => 'test', 'request_id' => $requestId,
                'data' => ['txnId' => 'TEST-FACE-TXN-' . uniqid(), 'message' => 'Test mode: ABHA search result.'],
            ];
            $this->logTestSubmission($requestId, $ep, ['mobile' => $plainMobile], $mock, 200, 'abdm.face.login.search');
            return $this->response->setJSON($mock);
        }

        try {
            $encMobile = $this->encryptAbdmData($plainMobile);
        } catch (\Throwable $e) {
            $this->logRequest($requestId, 'POST', $ep, 500, 'valid', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => 0, 'error' => 'encryption_failed', 'message' => $e->getMessage(), 'request_id' => $requestId,
            ]);
        }

        $abdmPayload = [
            'scope'  => ['search-abha'],
            'mobile' => $encMobile,
        ];

        return $this->sendM1Request($requestId, $ep, '/abha/api/v3/profile/account/abha/search', $abdmPayload);
    }

    /**
     * Face Auth QR Login Step 2 — Initiate face auth login session.
     * POST /api/v3/abha/face/login/request
     *
     * HMS sends: { "txnId": "...", "loginId": "<index-from-search>" }
     * Gateway builds full scope+loginHint payload for ABDM.
     * Returns: { txnId } for next step
     */
    public function abhaFaceLoginRequest()
    {
        $ep        = '/api/v3/abha/face/login/request';
        $requestId = $this->generateRequestId();

        $authStatus = $this->validateBearer();
        if ($authStatus !== 'valid') {
            $this->logRequest($requestId, 'POST', $ep, 403, $authStatus, 'Invalid or missing bearer token');
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => 0, 'error' => 'Invalid authorization token', 'request_id' => $requestId,
            ]);
        }

        $body    = json_decode((string) $this->request->getBody(), true) ?? [];
        $txnId   = trim((string) ($body['txnId'] ?? $body['transactionId'] ?? ''));
        $loginId = trim((string) ($body['loginId'] ?? ''));

        if ($txnId === '' || $loginId === '') {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => 0, 'error' => 'missing_fields', 'message' => 'Required fields: txnId and loginId (index from search response).', 'request_id' => $requestId,
            ]);
        }

        if ($this->isTestMode()) {
            $mock = [
                'ok' => 1, 'mode' => 'test', 'request_id' => $requestId,
                'data' => ['txnId' => $txnId, 'message' => 'Test mode: face auth login session initiated.'],
            ];
            $this->logTestSubmission($requestId, $ep, $body, $mock, 200, 'abdm.face.login.request');
            return $this->response->setJSON($mock);
        }

        $abdmPayload = [
            'scope'     => ['abha-login', 'search-abha', 'qr-verify'],
            'loginHint' => 'index',
            'loginId'   => $loginId,
            'otpSystem' => 'aadhaar',
            'txnId'     => $txnId,
        ];

        return $this->sendM1Request($requestId, $ep, '/abha/api/v3/profile/login/request/otp', $abdmPayload);
    }

    /**
     * Face Auth QR Login Step 4 — Verify biometric PID and complete login.
     * POST /api/v3/abha/face/login/verify
     *
     * HMS sends: { "txnId": "...", "faceAuthPid": "<base64-encoded-PID-from-device>" }
     * Gateway wraps into ABDM authData format.
     * Returns: ABHA profile + X-Token
     */
    public function abhaFaceLoginVerify()
    {
        $ep        = '/api/v3/abha/face/login/verify';
        $requestId = $this->generateRequestId();

        $authStatus = $this->validateBearer();
        if ($authStatus !== 'valid') {
            $this->logRequest($requestId, 'POST', $ep, 403, $authStatus, 'Invalid or missing bearer token');
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => 0, 'error' => 'Invalid authorization token', 'request_id' => $requestId,
            ]);
        }

        $body        = json_decode((string) $this->request->getBody(), true) ?? [];
        $txnId       = trim((string) ($body['txnId'] ?? $body['transactionId'] ?? ''));
        $faceAuthPid = trim((string) ($body['faceAuthPid'] ?? $body['rdPidData'] ?? $body['pid_data'] ?? ''));

        if ($txnId === '' || $faceAuthPid === '') {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => 0, 'error' => 'missing_fields',
                'message' => 'Required fields: txnId and faceAuthPid (base64-encoded PID data from biometric device).',
                'request_id' => $requestId,
            ]);
        }

        if ($this->isTestMode()) {
            $mock = [
                'ok' => 1, 'mode' => 'test', 'request_id' => $requestId,
                'data' => [
                    'message' => 'Test mode: ABHA logged in via face auth.',
                    'token'   => 'TEST-XTOKEN-' . uniqid(),
                    'ABHAProfile' => ['ABHANumber' => '14-0000-0000-0000'],
                ],
            ];
            $this->logTestSubmission($requestId, $ep, ['txnId' => $txnId], $mock, 200, 'abdm.face.login.verify');
            return $this->response->setJSON($mock);
        }

        $this->bootRepositories();
        $startTime = microtime(true);
        $cfg       = config('AbdmGateway');

        try {
            $abdmToken = $this->getAbdmAccessToken();
            $verifyUrl = rtrim($cfg->m1BaseUrl, '/') . '/abha/api/v3/profile/login/verify';

            $abdmPayload = [
                'scope'    => ['abha-login', 'aadhaar-face-verify'],
                'authData' => [
                    'authMethods' => ['face'],
                    'face' => [
                        'txnId'       => $txnId,
                        'faceAuthPid' => $faceAuthPid,
                    ],
                ],
            ];

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
            $rawVerify = curl_exec($ch);
            $httpCode  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
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

            // Optionally fetch profile if X-Token returned
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

    // ==================== Biometric (Fingerprint / Iris) ====================

    /**
     * Step 1 — Biometric Enrollment: Initialise transaction.
     * POST /api/v3/abha/bio/enrol/init
     *
     * No body required from HMS.  Gateway injects scope [abha-enrol, bio-verify].
     * Returns: { txnId }
     */
    public function abhaBioEnrolInit()
    {
        $ep        = '/api/v3/abha/bio/enrol/init';
        $requestId = $this->generateRequestId();

        $authStatus = $this->validateBearer();
        if ($authStatus !== 'valid') {
            $this->logRequest($requestId, 'POST', $ep, 403, $authStatus, 'Invalid or missing bearer token');
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => 0, 'error' => 'Invalid authorization token', 'request_id' => $requestId,
            ]);
        }

        if ($this->isTestMode()) {
            $mock = [
                'ok' => 1, 'mode' => 'test', 'request_id' => $requestId,
                'data' => ['txnId' => 'TEST-BIO-TXN-' . uniqid(), 'message' => 'Test mode: biometric auth transaction initialised.'],
            ];
            $this->logTestSubmission($requestId, $ep, [], $mock, 200, 'abdm.bio.enrol.init');
            return $this->response->setJSON($mock);
        }

        $abdmPayload = ['scope' => ['abha-enrol', 'bio-verify']];
        return $this->sendM1Request($requestId, $ep, '/abha/api/v3/enrollment/enrol/auth/init', $abdmPayload);
    }

    /**
     * Step 3 — Biometric Enrollment: Submit fingerprint or iris PID and create ABHA.
     * POST /api/v3/abha/bio/enrol/submit
     *
     * HMS sends (fingerprint): { "aadhaar": "...", "fingerPrintAuthPid": "<PID>", "mobile": "...", "type": "fingerprint" }
     * HMS sends (iris):        { "aadhaar": "...", "irisPid": "<PID>",            "mobile": "...", "type": "iris" }
     * Gateway RSA-encrypts aadhaar and wraps into ABDM authData.
     * Returns: ABHA profile
     */
    public function abhaBioEnrolSubmit()
    {
        $ep        = '/api/v3/abha/bio/enrol/submit';
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
        $mobile       = trim((string) ($body['mobile'] ?? $body['mobileNumber'] ?? ''));
        $bioType      = strtolower(trim((string) ($body['type'] ?? 'fingerprint')));

        // Accept fingerprint PID under various common field names
        $fpPid   = trim((string) ($body['fingerPrintAuthPid'] ?? $body['fingerprint_pid'] ?? $body['fp_pid'] ?? ''));
        // Accept iris PID under various field names
        $irisPid = trim((string) ($body['irisPid'] ?? $body['iris_pid'] ?? $body['Pid'] ?? ''));

        if ($bioType === 'iris') {
            $pidValue  = $irisPid;
            $pidField  = 'Pid';
        } else {
            $pidValue  = $fpPid ?: $irisPid; // fallback if type not set but iris field provided
            $pidField  = 'fingerPrintAuthPid';
            $bioType   = 'fingerprint';
        }

        if (!preg_match('/^\d{12}$/', $plainAadhaar)) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => 0, 'error' => 'invalid_aadhaar', 'message' => 'Field "aadhaar" must be a 12-digit number.', 'request_id' => $requestId,
            ]);
        }

        if ($pidValue === '') {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => 0, 'error' => 'missing_pid',
                'message' => 'Biometric PID is required: "fingerPrintAuthPid" for fingerprint or "irisPid" for iris.',
                'request_id' => $requestId,
            ]);
        }

        if ($this->isTestMode()) {
            $mock = [
                'ok' => 1, 'mode' => 'test', 'request_id' => $requestId,
                'data' => [
                    'message'     => "Test mode: ABHA would be created via $bioType biometric.",
                    'ABHAProfile' => ['ABHANumber' => '14-0000-0000-0000', 'mobile' => $mobile],
                ],
            ];
            $this->logTestSubmission($requestId, $ep, ['aadhaar' => '***', 'type' => $bioType, 'mobile' => $mobile], $mock, 200, 'abdm.bio.enrol.submit');
            return $this->response->setJSON($mock);
        }

        try {
            $encAadhaar = $this->encryptAbdmData($plainAadhaar);
        } catch (\Throwable $e) {
            $this->logRequest($requestId, 'POST', $ep, 500, 'valid', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => 0, 'error' => 'encryption_failed', 'message' => $e->getMessage(), 'request_id' => $requestId,
            ]);
        }

        $authMethod = ($bioType === 'iris') ? 'iris' : 'bio';
        $bioPayload = [
            'aadhaar' => $encAadhaar,
            $pidField => $pidValue,
            'mobile'  => $mobile,
        ];

        $abdmPayload = [
            'authData' => [
                'authMethods' => [$authMethod],
                $authMethod   => $bioPayload,
            ],
            'consent' => [
                'code'    => 'abha-enrollment',
                'version' => '1.4',
            ],
        ];

        return $this->sendM1Request($requestId, $ep, '/abha/api/v3/enrollment/enrol/byAadhaar', $abdmPayload);
    }

    /**
     * Biometric Login Step 1 — Request auth session by ABHA number.
     * POST /api/v3/abha/bio/login/request
     *
     * HMS sends: { "abha_number": "14-XXXX-XXXX-XXXX" }
     * Gateway RSA-encrypts the ABHA number and sends with scope [abha-login, aadhaar-bio-verify].
     * Returns: { txnId } for next step
     */
    public function abhaBioLoginRequest()
    {
        $ep        = '/api/v3/abha/bio/login/request';
        $requestId = $this->generateRequestId();

        $authStatus = $this->validateBearer();
        if ($authStatus !== 'valid') {
            $this->logRequest($requestId, 'POST', $ep, 403, $authStatus, 'Invalid or missing bearer token');
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => 0, 'error' => 'Invalid authorization token', 'request_id' => $requestId,
            ]);
        }

        $body       = json_decode((string) $this->request->getBody(), true) ?? [];
        $abhaNumber = trim((string) ($body['abha_number'] ?? $body['abhaNumber'] ?? $body['ABHANumber'] ?? ''));
        // Accept with or without hyphens; normalise to digits-only for encryption
        $plainAbha  = preg_replace('/[^0-9]/', '', $abhaNumber);

        if (strlen($plainAbha) !== 14) {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => 0, 'error' => 'invalid_abha_number',
                'message' => 'Field "abha_number" must be a 14-digit ABHA number (hyphens optional).',
                'request_id' => $requestId,
            ]);
        }

        if ($this->isTestMode()) {
            $mock = [
                'ok' => 1, 'mode' => 'test', 'request_id' => $requestId,
                'data' => ['txnId' => 'TEST-BIO-TXN-' . uniqid(), 'message' => 'Test mode: biometric login session initiated.'],
            ];
            $this->logTestSubmission($requestId, $ep, ['abha_number' => $abhaNumber], $mock, 200, 'abdm.bio.login.request');
            return $this->response->setJSON($mock);
        }

        try {
            $encAbha = $this->encryptAbdmData($plainAbha);
        } catch (\Throwable $e) {
            $this->logRequest($requestId, 'POST', $ep, 500, 'valid', $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => 0, 'error' => 'encryption_failed', 'message' => $e->getMessage(), 'request_id' => $requestId,
            ]);
        }

        $abdmPayload = [
            'scope'     => ['abha-login', 'aadhaar-bio-verify'],
            'loginHint' => 'abha-number',
            'loginId'   => $encAbha,
            'otpSystem' => 'aadhaar',
        ];

        return $this->sendM1Request($requestId, $ep, '/abha/api/v3/profile/login/request/otp', $abdmPayload);
    }

    /**
     * Biometric Login Step 3 — Verify biometric PID and complete login.
     * POST /api/v3/abha/bio/login/verify
     *
     * HMS sends (fingerprint): { "txnId": "...", "fingerPrintAuthPid": "<PID>" }
     * HMS sends (iris):        { "txnId": "...", "irisPid": "<PID>" }
     * Gateway detects type from field names and builds ABDM authData.
     * Returns: ABHA profile + X-Token
     */
    public function abhaBioLoginVerify()
    {
        $ep        = '/api/v3/abha/bio/login/verify';
        $requestId = $this->generateRequestId();

        $authStatus = $this->validateBearer();
        if ($authStatus !== 'valid') {
            $this->logRequest($requestId, 'POST', $ep, 403, $authStatus, 'Invalid or missing bearer token');
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => 0, 'error' => 'Invalid authorization token', 'request_id' => $requestId,
            ]);
        }

        $body    = json_decode((string) $this->request->getBody(), true) ?? [];
        $txnId   = trim((string) ($body['txnId'] ?? $body['transactionId'] ?? ''));
        $fpPid   = trim((string) ($body['fingerPrintAuthPid'] ?? $body['fingerprint_pid'] ?? $body['fp_pid'] ?? ''));
        $irisPid = trim((string) ($body['irisPid'] ?? $body['iris_pid'] ?? ''));

        if ($txnId === '') {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => 0, 'error' => 'missing_txnId', 'message' => 'Field "txnId" is required.', 'request_id' => $requestId,
            ]);
        }

        if ($fpPid === '' && $irisPid === '') {
            return $this->response->setStatusCode(400)->setJSON([
                'ok' => 0, 'error' => 'missing_pid',
                'message' => 'Biometric PID required: "fingerPrintAuthPid" for fingerprint or "irisPid" for iris.',
                'request_id' => $requestId,
            ]);
        }

        if ($this->isTestMode()) {
            $mock = [
                'ok' => 1, 'mode' => 'test', 'request_id' => $requestId,
                'data' => [
                    'message'     => 'Test mode: ABHA logged in via biometric.',
                    'token'       => 'TEST-XTOKEN-' . uniqid(),
                    'ABHAProfile' => ['ABHANumber' => '14-0000-0000-0000'],
                ],
            ];
            $this->logTestSubmission($requestId, $ep, ['txnId' => $txnId, 'type' => $fpPid ? 'fingerprint' : 'iris'], $mock, 200, 'abdm.bio.login.verify');
            return $this->response->setJSON($mock);
        }

        // Build authData — iris takes precedence only if fingerprint PID absent
        if ($irisPid !== '' && $fpPid === '') {
            $authMethod = 'iris';
            $authData   = ['authMethods' => ['iris'], 'iris' => ['txnId' => $txnId, 'irisPid' => $irisPid]];
        } else {
            $authMethod = 'bio';
            $authData   = ['authMethods' => ['bio'], 'bio' => ['txnId' => $txnId, 'fingerPrintAuthPid' => $fpPid]];
        }

        $this->bootRepositories();
        $startTime = microtime(true);
        $cfg       = config('AbdmGateway');

        try {
            $abdmToken = $this->getAbdmAccessToken();
            $verifyUrl = rtrim($cfg->m1BaseUrl, '/') . '/abha/api/v3/profile/login/verify';

            $abdmPayload = [
                'scope'    => ['abha-login', 'aadhaar-bio-verify'],
                'authData' => $authData,
            ];

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
            $rawVerify = curl_exec($ch);
            $httpCode  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
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

            // Fetch profile if X-Token returned
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

    // ==================== Scan & Pay ====================

    /**
     * Authenticated proxy to ABDM scan-gateway endpoints.
     * All Scan & Pay steps follow the same pattern: validate HMS auth, forward to ABDM, return response.
     *
     * @param string $path   Path under /api/hiecm/scan-gateway/v3/
     * @param string $method HTTP method to use
     */
    private function scanGatewayCall(string $path, string $method = 'POST'): \CodeIgniter\HTTP\ResponseInterface
    {
        $authStatus = $this->validateBearer();
        if ($authStatus !== 'valid') {
            return $this->response->setStatusCode(401)->setJSON(['ok' => 0, 'error' => 'unauthorized']);
        }

        if (!$this->authHospitalId) {
            return $this->response->setStatusCode(403)->setJSON(['ok' => 0, 'error' => 'hospital_not_resolved']);
        }

        if ($this->isTestMode()) {
            return $this->response->setJSON(['ok' => 1, 'test_mode' => true, 'path' => $path, 'method' => $method]);
        }

        try {
            $cfg       = config('AbdmGateway');
            $abdmToken = $this->getAbdmAccessToken();
            $hfrId     = $cfg->hfrId;
            $url       = 'https://dev.abdm.gov.in/api/hiecm/scan-gateway/v3/' . ltrim($path, '/');

            $headers = [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $abdmToken,
                'REQUEST-ID: ' . $this->generateRequestId(),
                'TIMESTAMP: ' . gmdate('Y-m-d\TH:i:s.000\Z'),
                'X-CM-ID: sbx',
                'X-HIP-ID: ' . $hfrId,
            ];

            $opts = [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 20,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_SSL_VERIFYPEER => false,
            ];

            if ($method === 'POST') {
                $body = (array) ($this->request->getJSON(true) ?? []);
                $opts[CURLOPT_POST]       = true;
                $opts[CURLOPT_POSTFIELDS] = json_encode($body);
            } elseif ($method === 'PATCH') {
                $body = (array) ($this->request->getJSON(true) ?? []);
                $opts[CURLOPT_CUSTOMREQUEST] = 'PATCH';
                $opts[CURLOPT_POSTFIELDS]    = json_encode($body);
            } elseif ($method === 'GET') {
                $allowed = ['status', 'limit', 'startDate', 'endDate'];
                $params  = [];
                foreach ($allowed as $k) {
                    $v = $this->request->getGet($k);
                    if ($v !== null && $v !== '') {
                        $params[$k] = $v;
                    }
                }
                if ($params) {
                    $opts[CURLOPT_URL] = $url . '?' . http_build_query($params);
                }
            }

            $ch = curl_init();
            curl_setopt_array($ch, $opts);
            $raw      = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $decoded = json_decode((string) $raw, true);
            $payload = is_array($decoded) ? $decoded : ['raw' => (string) $raw];

            if ($httpCode >= 400) {
                return $this->response->setStatusCode($httpCode)
                    ->setJSON(['ok' => 0, 'error' => 'abdm_error', 'http_code' => $httpCode, 'details' => $payload]);
            }

            return $this->response->setStatusCode($httpCode ?: 200)
                ->setJSON(array_merge(['ok' => 1], $payload));

        } catch (\Throwable $e) {
            log_message('error', 'scanGatewayCall[' . $path . ']: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON(['ok' => 0, 'error' => $e->getMessage()]);
        }
    }

    /**
     * POST /api/v3/scan-pay/open-order
     * Step 1: Hospital initiates a Scan & Pay order for a patient.
     * Body: { intent, metadata: { hipId, counterId }, profile: { patient: {...} } }
     */
    public function scanPayOpenOrder()
    {
        return $this->scanGatewayCall('patient/share/open-order');
    }

    /**
     * POST /api/v3/scan-pay/on-share-open-order
     * Step 2: Hospital responds with the procedure/service list after patient profile received.
     * Body: { intent, abhaAddress, patientUid, procedures: [...] }
     */
    public function scanPayOnShareOpenOrder()
    {
        return $this->scanGatewayCall('patient/on-share/open-order');
    }

    /**
     * POST /api/v3/scan-pay/selection
     * Step 3: Forward patient's service selection to ABDM.
     * Body: { intent, openOrderRequestId, abhaAddress, procedures: [...] }
     */
    public function scanPaySelection()
    {
        return $this->scanGatewayCall('patient/selection');
    }

    /**
     * POST /api/v3/scan-pay/on-selection
     * Step 4: Hospital acknowledges the patient's selection.
     */
    public function scanPayOnSelection()
    {
        return $this->scanGatewayCall('patient/on-selection');
    }

    /**
     * POST /api/v3/scan-pay/notify
     * Step 5: Hospital sends payment notification to ABDM.
     * Body: { intent, orderId, abhaAddress, payment: { ... } }
     */
    public function scanPayNotify()
    {
        return $this->scanGatewayCall('patient/scan-pay/notify');
    }

    /**
     * POST /api/v3/scan-pay/order-status
     * Step 6: Query the status of a Scan & Pay order.
     * Body: { orderId }
     */
    public function scanPayOrderStatus()
    {
        return $this->scanGatewayCall('patient/scan-pay/order-status');
    }

    /**
     * GET /api/v3/scan-pay/details
     * Retrieve Scan & Pay transaction history.
     * Query params: status, limit, startDate (ISO), endDate (ISO)
     */
    public function scanPayDetails()
    {
        return $this->scanGatewayCall('patient/scan-pay/details', 'GET');
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
    // ==================== M2 Proxy Helpers ====================

    /**
     * Extract X-Token from request body key 'x_token' or X-Token header.
     */
    protected function extractXToken(): string
    {
        $body = (array) ($this->request->getJSON(true) ?? []);
        if (!empty($body['x_token'])) {
            return ltrim((string) $body['x_token'], 'Bearer ');
        }
        $header = $this->request->getHeaderLine('X-Token');
        if ($header !== '') {
            return ltrim(trim((string) $header), 'Bearer ');
        }
        return '';
    }

    /**
     * Generic HFR proxy: POST to HFR base URL (no X-Token; uses gateway access token).
     */
    protected function proxyHfrEndpoint(string $endpoint, string $hfrPath): \CodeIgniter\HTTP\ResponseInterface
    {
        $requestId  = $this->generateRequestId();
        $authStatus = $this->validateBearer();
        if ($authStatus !== 'valid') {
            $this->logRequest($requestId, 'POST', $endpoint, 403, $authStatus, 'Unauthorized');
            return $this->response->setStatusCode(403)->setJSON(['ok' => 0, 'error' => 'Unauthorized', 'request_id' => $requestId]);
        }

        $rawBody = (string) $this->request->getBody();
        $body    = trim($rawBody) !== '' ? ((array) json_decode($rawBody, true) ?: []) : [];

        if ($this->isTestMode()) {
            $mock = ['ok' => 1, 'mode' => 'test', 'request_id' => $requestId,
                     'data' => ['endpoint' => $endpoint, 'message' => 'Mock HFR response in test mode']];
            $this->logTestSubmission($requestId, $endpoint, $body, $mock, 200, 'hfr.proxy');
            return $this->response->setJSON($mock);
        }

        $this->bootRepositories();
        $startTime = microtime(true);
        try {
            $url       = rtrim((string) config('AbdmGateway')->hfrBaseUrl, '/') . '/' . ltrim($hfrPath, '/');
            $abdmToken = $this->getAbdmAccessToken();
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => (int) config('AbdmGateway')->m3Timeout,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($body),
                CURLOPT_HTTPHEADER     => [
                    'Authorization: Bearer ' . $abdmToken,
                    'Content-Type: application/json',
                    'Accept: application/json',
                ],
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $raw  = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err  = curl_error($ch);
            curl_close($ch);
            if ($err) throw new \RuntimeException($err);

            $responseTime = round((microtime(true) - $startTime) * 1000);
            $this->logRequest($requestId, 'POST', $endpoint, $code, 'valid', null, $responseTime, $raw);
            $decoded = json_decode((string) $raw, true);
            return $this->response->setStatusCode($code)->setJSON([
                'ok'         => $code >= 200 && $code < 300 ? 1 : 0,
                'data'       => is_array($decoded) ? $decoded : ['raw_response' => trim((string) $raw)],
                'request_id' => $requestId,
            ]);
        } catch (\Throwable $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000);
            $this->logRequest($requestId, 'POST', $endpoint, 500, 'valid', $e->getMessage(), $responseTime);
            return $this->response->setStatusCode(500)->setJSON(['ok' => 0, 'error' => $e->getMessage(), 'request_id' => $requestId]);
        }
    }

    /**
     * Generic M1 proxy for POST endpoints that pass an X-Token (user session token).
     * x_token is read from body or X-Token header; stripped from forwarded body.
     */
    protected function proxyM1WithXToken(string $endpoint, string $upstreamPath): \CodeIgniter\HTTP\ResponseInterface
    {
        $requestId  = $this->generateRequestId();
        $authStatus = $this->validateBearer();
        if ($authStatus !== 'valid') {
            $this->logRequest($requestId, 'POST', $endpoint, 403, $authStatus, 'Unauthorized');
            return $this->response->setStatusCode(403)->setJSON(['ok' => 0, 'error' => 'Unauthorized', 'request_id' => $requestId]);
        }

        $rawBody = (string) $this->request->getBody();
        $body    = trim($rawBody) !== '' ? ((array) json_decode($rawBody, true) ?: []) : [];
        $xToken  = $this->extractXToken();
        unset($body['x_token']); // strip internal routing key before forwarding

        if ($this->isTestMode()) {
            $mock = ['ok' => 1, 'mode' => 'test', 'request_id' => $requestId,
                     'data' => ['endpoint' => $endpoint, 'message' => 'Mock response in test mode']];
            $this->logTestSubmission($requestId, $endpoint, $body, $mock, 200, 'abdm.m1.xtoken');
            return $this->response->setJSON($mock);
        }

        $this->bootRepositories();
        $startTime = microtime(true);
        try {
            $url       = rtrim((string) config('AbdmGateway')->m1BaseUrl, '/') . '/' . ltrim($upstreamPath, '/');
            $abdmToken = $this->getAbdmAccessToken();
            $headers   = [
                'Authorization: Bearer ' . $abdmToken,
                'Content-Type: application/json',
                'Accept: application/json',
                'REQUEST-ID: ' . $this->generateAbdmRequestId(),
                'TIMESTAMP: '  . gmdate('Y-m-d\TH:i:s.000\Z'),
            ];
            if ($xToken !== '') {
                $headers[] = 'X-Token: Bearer ' . $xToken;
            }
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => (int) config('AbdmGateway')->m3Timeout,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($body),
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $raw  = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err  = curl_error($ch);
            curl_close($ch);
            if ($err) throw new \RuntimeException($err);

            $responseTime = round((microtime(true) - $startTime) * 1000);
            $this->logRequest($requestId, 'POST', $endpoint, $code, 'valid', null, $responseTime, $raw);
            $decoded = json_decode((string) $raw, true);
            return $this->response->setStatusCode($code)->setJSON([
                'ok'         => $code >= 200 && $code < 300 ? 1 : 0,
                'data'       => is_array($decoded) ? $decoded : ['raw_response' => trim((string) $raw)],
                'request_id' => $requestId,
            ]);
        } catch (\Throwable $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000);
            $this->logRequest($requestId, 'POST', $endpoint, 500, 'valid', $e->getMessage(), $responseTime);
            return $this->response->setStatusCode(500)->setJSON(['ok' => 0, 'error' => $e->getMessage(), 'request_id' => $requestId]);
        }
    }

    /**
     * Generic M1 proxy for GET requests. Forwards query params; optionally attaches X-Token.
     */
    protected function proxyM1Get(string $endpoint, string $upstreamPath): \CodeIgniter\HTTP\ResponseInterface
    {
        $requestId  = $this->generateRequestId();
        $authStatus = $this->validateBearer();
        if ($authStatus !== 'valid') {
            $this->logRequest($requestId, 'GET', $endpoint, 403, $authStatus, 'Unauthorized');
            return $this->response->setStatusCode(403)->setJSON(['ok' => 0, 'error' => 'Unauthorized', 'request_id' => $requestId]);
        }

        $xToken      = $this->extractXToken();
        $queryParams = $this->request->getGet() ?? [];

        if ($this->isTestMode()) {
            return $this->response->setJSON(['ok' => 1, 'mode' => 'test', 'request_id' => $requestId,
                'data' => ['endpoint' => $endpoint, 'message' => 'Mock response in test mode']]);
        }

        $this->bootRepositories();
        $startTime = microtime(true);
        try {
            $url = rtrim((string) config('AbdmGateway')->m1BaseUrl, '/') . '/' . ltrim($upstreamPath, '/');
            if (!empty($queryParams)) {
                $url .= '?' . http_build_query($queryParams);
            }
            $abdmToken = $this->getAbdmAccessToken();
            $headers   = [
                'Authorization: Bearer ' . $abdmToken,
                'Accept: application/json',
                'REQUEST-ID: ' . $this->generateAbdmRequestId(),
                'TIMESTAMP: '  . gmdate('Y-m-d\TH:i:s.000\Z'),
            ];
            if ($xToken !== '') {
                $headers[] = 'X-Token: Bearer ' . $xToken;
            }
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => (int) config('AbdmGateway')->m3Timeout,
                CURLOPT_HTTPGET        => true,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $raw  = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err  = curl_error($ch);
            curl_close($ch);
            if ($err) throw new \RuntimeException($err);

            $responseTime = round((microtime(true) - $startTime) * 1000);
            $this->logRequest($requestId, 'GET', $endpoint, $code, 'valid', null, $responseTime, $raw);
            $decoded = json_decode((string) $raw, true);
            return $this->response->setStatusCode($code)->setJSON([
                'ok'         => $code >= 200 && $code < 300 ? 1 : 0,
                'data'       => is_array($decoded) ? $decoded : ['raw_response' => trim((string) $raw)],
                'request_id' => $requestId,
            ]);
        } catch (\Throwable $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000);
            $this->logRequest($requestId, 'GET', $endpoint, 500, 'valid', $e->getMessage(), $responseTime);
            return $this->response->setStatusCode(500)->setJSON(['ok' => 0, 'error' => $e->getMessage(), 'request_id' => $requestId]);
        }
    }

    /**
     * Generic M1 proxy for PATCH requests with X-Token.
     */
    protected function proxyM1Patch(string $endpoint, string $upstreamPath): \CodeIgniter\HTTP\ResponseInterface
    {
        $requestId  = $this->generateRequestId();
        $authStatus = $this->validateBearer();
        if ($authStatus !== 'valid') {
            $this->logRequest($requestId, 'PATCH', $endpoint, 403, $authStatus, 'Unauthorized');
            return $this->response->setStatusCode(403)->setJSON(['ok' => 0, 'error' => 'Unauthorized', 'request_id' => $requestId]);
        }

        $rawBody = (string) $this->request->getBody();
        $body    = trim($rawBody) !== '' ? ((array) json_decode($rawBody, true) ?: []) : [];
        $xToken  = $this->extractXToken();
        unset($body['x_token']);

        if ($this->isTestMode()) {
            return $this->response->setJSON(['ok' => 1, 'mode' => 'test', 'request_id' => $requestId,
                'data' => ['endpoint' => $endpoint, 'message' => 'Mock PATCH response in test mode']]);
        }

        $this->bootRepositories();
        $startTime = microtime(true);
        try {
            $url       = rtrim((string) config('AbdmGateway')->m1BaseUrl, '/') . '/' . ltrim($upstreamPath, '/');
            $abdmToken = $this->getAbdmAccessToken();
            $headers   = [
                'Authorization: Bearer ' . $abdmToken,
                'Content-Type: application/json',
                'Accept: application/json',
                'REQUEST-ID: ' . $this->generateAbdmRequestId(),
                'TIMESTAMP: '  . gmdate('Y-m-d\TH:i:s.000\Z'),
            ];
            if ($xToken !== '') {
                $headers[] = 'X-Token: Bearer ' . $xToken;
            }
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => (int) config('AbdmGateway')->m3Timeout,
                CURLOPT_CUSTOMREQUEST  => 'PATCH',
                CURLOPT_POSTFIELDS     => json_encode($body),
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $raw  = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err  = curl_error($ch);
            curl_close($ch);
            if ($err) throw new \RuntimeException($err);

            $responseTime = round((microtime(true) - $startTime) * 1000);
            $this->logRequest($requestId, 'PATCH', $endpoint, $code, 'valid', null, $responseTime, $raw);
            $decoded = json_decode((string) $raw, true);
            return $this->response->setStatusCode($code)->setJSON([
                'ok'         => $code >= 200 && $code < 300 ? 1 : 0,
                'data'       => is_array($decoded) ? $decoded : ['raw_response' => trim((string) $raw)],
                'request_id' => $requestId,
            ]);
        } catch (\Throwable $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000);
            $this->logRequest($requestId, 'PATCH', $endpoint, 500, 'valid', $e->getMessage(), $responseTime);
            return $this->response->setStatusCode(500)->setJSON(['ok' => 0, 'error' => $e->getMessage(), 'request_id' => $requestId]);
        }
    }

    // ==================== M2 — HFR (Health Facility Registry) ====================

    /** POST /api/v3/hfr/facility/search — search facilities by name/ownership/state */
    public function hfrFacilitySearch(): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->proxyHfrEndpoint('/api/v3/hfr/facility/search',
            '/v4/int/FacilityManagement/v1.5/facility/search');
    }

    /** POST /api/v3/hfr/facility/contact — fetch facility contact details */
    public function hfrFacilityContact(): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->proxyHfrEndpoint('/api/v3/hfr/facility/contact',
            '/v4/int/v1.5/facility/fetchFacilityContactDetails');
    }

    /** POST /api/v3/hfr/facility/send-otp — send OTP to facility's registered mobile */
    public function hfrFacilitySendOtp(): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->proxyHfrEndpoint('/api/v3/hfr/facility/send-otp',
            '/v4/int/v1.5/facility/sendOtpToContact');
    }

    /** POST /api/v3/hfr/facility/validate-otp — validate OTP and link HMS source to facility */
    public function hfrFacilityValidateOtp(): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->proxyHfrEndpoint('/api/v3/hfr/facility/validate-otp',
            '/v4/int/v1.5/facility/validateOtp');
    }

    /** POST /api/v3/hfr/hrp/link — link/update bridge HRP services against a facility */
    public function hfrHrpLink(): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->proxyHfrEndpoint('/api/v3/hfr/hrp/link',
            '/v4/int/v1/bridges/MutipleHRPAddUpdateServices');
    }

    // ==================== M2 — PHR / ABHA Address Verification ====================

    /** POST /api/v3/phr/abha/search — search ABHA address */
    public function phrAbhaSearch(): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->proxyM1Endpoint('/api/v3/phr/abha/search',
            '/abha/api/v3/phr/web/login/abha/search');
    }

    /** POST /api/v3/phr/abha/request-otp — request OTP for ABHA address login */
    public function phrAbhaRequestOtp(): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->proxyM1Endpoint('/api/v3/phr/abha/request-otp',
            '/abha/api/v3/phr/web/login/abha/request/otp');
    }

    /** POST /api/v3/phr/abha/verify — verify OTP and get PHR session token */
    public function phrAbhaVerify(): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->proxyM1Endpoint('/api/v3/phr/abha/verify',
            '/abha/api/v3/phr/web/login/abha/verify');
    }

    /** GET /api/v3/phr/profile — fetch ABHA profile via PHR session (X-Token required) */
    public function phrGetProfile(): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->proxyM1Get('/api/v3/phr/profile',
            '/abha/api/v3/phr/web/login/profile/abha-profile');
    }

    /** GET /api/v3/phr/phr-card — fetch PHR card image (X-Token required) */
    public function phrGetCard(): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->proxyM1Get('/api/v3/phr/phr-card',
            '/abha/api/v3/phr/web/login/profile/abha/phr-card');
    }

    /** GET /api/v3/phr/qr-code — fetch PHR QR code (X-Token required) */
    public function phrGetQrCode(): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->proxyM1Get('/api/v3/phr/qr-code',
            '/abha/api/v3/phr/web/login/profile/abha/qr-code');
    }

    // ==================== M2 — ABHA Address Management ====================

    /** GET /api/v3/abha/suggestions — get ABHA address suggestions (?txnId=...) */
    public function abhaAddressSuggestions(): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->proxyM1Get('/api/v3/abha/suggestions',
            '/abha/api/v3/enrollment/enrol/suggestion');
    }

    /** POST /api/v3/abha/set-address — set/confirm chosen ABHA address (X-Token required) */
    public function abhaSetAddress(): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->proxyM1WithXToken('/api/v3/abha/set-address',
            '/abha/api/v3/enrollment/enrol/abha-address');
    }

    /** POST /api/v3/abha/forgot/request-otp — retrieve forgotten ABHA via mobile/Aadhaar OTP */
    public function abhaForgotRequestOtp(): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->proxyM1Endpoint('/api/v3/abha/forgot/request-otp',
            '/abha/api/v3/profile/login/request/otp');
    }

    /** POST /api/v3/abha/forgot/verify — verify OTP to retrieve ABHA number */
    public function abhaForgotVerify(): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->proxyM1Endpoint('/api/v3/abha/forgot/verify',
            '/abha/api/v3/profile/login/verify');
    }

    // ==================== M2 — Child ABHA ====================

    /** GET /api/v3/abha/children — list children linked under guardian ABHA (X-Token required) */
    public function abhaGetChildren(): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->proxyM1Get('/api/v3/abha/children',
            '/abha/api/v3/enrollment/profile/children');
    }

    /** POST /api/v3/abha/child/create — create child ABHA (enrol/byAadhaar with guardian X-Token) */
    public function abhaCreateChild(): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->proxyM1WithXToken('/api/v3/abha/child/create',
            '/abha/api/v3/enrollment/enrol/byAadhaar');
    }

    // ==================== M2 — ABHA Profile Management ====================

    /** GET /api/v3/profile/account — fetch full ABHA profile (X-Token required) */
    public function profileGetAccount(): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->proxyM1Get('/api/v3/profile/account',
            '/abha/api/v3/profile/account');
    }

    /** PATCH /api/v3/profile/account — update ABHA profile fields (X-Token required) */
    public function profileUpdateAccount(): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->proxyM1Patch('/api/v3/profile/account',
            '/abha/api/v3/profile/account');
    }

    /** GET /api/v3/profile/qrcode — fetch ABHA profile QR code (X-Token required) */
    public function profileGetQrCode(): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->proxyM1Get('/api/v3/profile/qrcode',
            '/abha/api/v3/profile/account/qrCode');
    }

    /** POST /api/v3/profile/update/request-otp — request OTP for profile update (email/mobile/re-KYC) */
    public function profileUpdateRequestOtp(): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->proxyM1WithXToken('/api/v3/profile/update/request-otp',
            '/abha/api/v3/profile/account/request/otp');
    }

    /** POST /api/v3/profile/update/verify — verify OTP to confirm profile update */
    public function profileUpdateVerify(): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->proxyM1WithXToken('/api/v3/profile/update/verify',
            '/abha/api/v3/profile/account/verify');
    }

    /** GET /api/v3/profile/logout — logout ABHA session (X-Token required) */
    public function profileLogout(): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->proxyM1Get('/api/v3/profile/logout',
            '/abha/api/v3/profile/account/request/logout');
    }

    /** POST /api/v3/profile/delete/request-otp — initiate ABHA deletion (send scope:delete OTP) */
    public function profileDeleteRequestOtp(): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->proxyM1WithXToken('/api/v3/profile/delete/request-otp',
            '/abha/api/v3/profile/account/request/otp');
    }

    /** POST /api/v3/profile/delete/confirm — confirm ABHA deletion with OTP */
    public function profileDeleteConfirm(): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->proxyM1WithXToken('/api/v3/profile/delete/confirm',
            '/abha/api/v3/profile/account/verify');
    }

    /** POST /api/v3/profile/deactivate/request-otp — initiate ABHA deactivation OTP */
    public function profileDeactivateRequestOtp(): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->proxyM1WithXToken('/api/v3/profile/deactivate/request-otp',
            '/abha/api/v3/profile/account/request/otp');
    }

    /** POST /api/v3/profile/deactivate/confirm — confirm ABHA deactivation with OTP */
    public function profileDeactivateConfirm(): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->proxyM1WithXToken('/api/v3/profile/deactivate/confirm',
            '/abha/api/v3/profile/account/verify');
    }

    /** POST /api/v3/profile/reactivate/request-otp — request OTP to reactivate ABHA */
    public function profileReactivateRequestOtp(): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->proxyM1Endpoint('/api/v3/profile/reactivate/request-otp',
            '/abha/api/v3/profile/login/request/otp');
    }

    /** POST /api/v3/profile/reactivate/verify — verify OTP and reactivate ABHA */
    public function profileReactivateVerify(): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->proxyM1Endpoint('/api/v3/profile/reactivate/verify',
            '/abha/api/v3/profile/login/verify');
    }

    // ==================== M2 — Benefit APIs ====================

    /** POST /api/v3/benefit/link — link or delink a benefit scheme to/from ABHA (X-Token required) */
    public function benefitLinkDelink(): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->proxyM1WithXToken('/api/v3/benefit/link',
            '/abha/api/v3/profile/benefit/linkAndDelink');
    }

    /** POST /api/v3/benefit/search — search benefit schemes for a patient (X-Token required) */
    public function benefitSearch(): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->proxyM1WithXToken('/api/v3/benefit/search',
            '/abha/api/v3/profile/benefit/search');
    }

    /** GET /api/v3/benefit/abha/{abha} — get benefits linked to a specific ABHA number */
    public function benefitGetByAbha(string $abha): \CodeIgniter\HTTP\ResponseInterface
    {
        $cleanAbha = preg_replace('/[^0-9\-]/', '', $abha);
        return $this->proxyM1Get(
            '/api/v3/benefit/abha/' . $cleanAbha,
            '/abha/api/v3/profile/benefit/abha/' . $cleanAbha
        );
    }

    // ─────────────────────────────────────────────────────────────────────────

    protected function dispatchBridgeRoute(array $route): array    {
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
