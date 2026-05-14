<?php

namespace App\Controllers;

use App\Models\AbdmAuditTrail;
use App\Models\AbdmBundle;
use App\Models\AbdmHospital;
use App\Models\AbdmHospitalUser;
use App\Models\AbdmRequestLog;
use App\Models\AbdmTestSubmissionLog;
use App\Models\AbdmTokenQueue;
use App\Models\HmsCredential;
use App\Models\SupportTicket;
use App\Models\SupportMessage;
use App\Models\SupportAttachment;

class Admin extends BaseController
{
    protected AbdmHospital $hospitalModel;
    protected AbdmHospitalUser $userModel;
    protected AbdmTestSubmissionLog $testLogModel;
    protected HmsCredential $hmsCredentialModel;
    protected $abhaProfileModel;

    public function __construct()
    {
        $this->hospitalModel = new AbdmHospital();
        $this->userModel = new AbdmHospitalUser();
        $this->testLogModel = new AbdmTestSubmissionLog();
        $this->hmsCredentialModel = new HmsCredential();
        $this->abhaProfileModel = new \App\Models\AbdmAbhaProfile();
    }

    public function dashboard()
    {
        $requestLogModel = new AbdmRequestLog();
        $auditModel = new AbdmAuditTrail();
        $bundleModel = new AbdmBundle();

        $data = [
            'hospitalCount' => $this->hospitalModel->countAllResults(false),
            'userCount' => $this->userModel->countAllResults(false),
            'requestLogCount' => $requestLogModel->countAllResults(false),
            'auditCount' => $auditModel->countAllResults(false),
            'bundleCount' => $bundleModel->countAllResults(false),
            'testLogCount' => $this->testLogModel->countAllResults(false),
            'hmsCredentialCount' => $this->hmsCredentialModel->countAllResults(false),
            'hospitals' => $this->hospitalModel->orderBy('id', 'DESC')->findAll(20),
        ];

        return view('admin/dashboard', $data);
    }

    public function hospitals()
    {
        $data = [
            'hospitals' => $this->hospitalModel->orderBy('id', 'DESC')->findAll(100),
            'message' => session()->getFlashdata('message'),
            'error' => session()->getFlashdata('error'),
        ];

        return view('admin/hospitals', $data);
    }

    public function createHospital()
    {
        $hospitalName = trim((string) $this->request->getPost('hospital_name'));
        $hfrId = trim((string) $this->request->getPost('hfr_id'));
        $gatewayMode = strtolower(trim((string) $this->request->getPost('gateway_mode')));

        if ($hospitalName === '' || $hfrId === '') {
            return redirect()->to('/admin/hospitals')->with('error', 'Hospital name and HFR ID are required.');
        }

        if (!in_array($gatewayMode, ['test', 'live'], true)) {
            $gatewayMode = 'test';
        }

        $exists = $this->hospitalModel->where('hfr_id', $hfrId)->first();
        if ($exists !== null) {
            return redirect()->to('/admin/hospitals')->with('error', 'HFR ID already exists.');
        }

        $this->hospitalModel->insert([
            'hospital_name' => $hospitalName,
            'hfr_id' => $hfrId,
            'gateway_mode' => $gatewayMode,
            'contact_name' => trim((string) $this->request->getPost('contact_name')),
            'contact_email' => trim((string) $this->request->getPost('contact_email')),
            'contact_phone' => trim((string) $this->request->getPost('contact_phone')),
            'is_active' => (int) ($this->request->getPost('is_active') === '1'),
        ]);

        return redirect()->to('/admin/hospitals')->with('message', 'Hospital created successfully.');
    }

    public function updateHospitalMode(int $id)
    {
        $hospital = $this->hospitalModel->find($id);
        if ($hospital === null) {
            return redirect()->to('/admin/hospitals')->with('error', 'Hospital not found.');
        }

        $gatewayMode = strtolower(trim((string) $this->request->getPost('gateway_mode')));
        if (!in_array($gatewayMode, ['test', 'live'], true)) {
            return redirect()->to('/admin/hospitals')->with('error', 'Invalid gateway mode.');
        }

        $this->hospitalModel->update($id, ['gateway_mode' => $gatewayMode]);

        return redirect()->to('/admin/hospitals')->with('message', 'Hospital mode updated.');
    }

    public function users()
    {
        $users = $this->userModel
            ->select('abdm_hospital_users.*, abdm_hospitals.hospital_name, abdm_hospitals.hfr_id')
            ->join('abdm_hospitals', 'abdm_hospitals.id = abdm_hospital_users.hospital_id', 'left')
            ->orderBy('abdm_hospital_users.id', 'DESC')
            ->findAll(200);

        $data = [
            'users' => $users,
            'hospitals' => $this->hospitalModel->where('is_active', 1)->orderBy('hospital_name', 'ASC')->findAll(),
            'message' => session()->getFlashdata('message'),
            'error' => session()->getFlashdata('error'),
        ];

        return view('admin/users', $data);
    }

    public function createUser()
    {
        $hospitalId = (int) $this->request->getPost('hospital_id');
        $username = trim((string) $this->request->getPost('username'));
        $password = (string) $this->request->getPost('password');

        if ($hospitalId <= 0 || $username === '' || $password === '') {
            return redirect()->to('/admin/users')->with('error', 'Hospital, username, and password are required.');
        }

        if (strlen($password) < 8) {
            return redirect()->to('/admin/users')->with('error', 'Password must be at least 8 characters.');
        }

        $hospital = $this->hospitalModel->find($hospitalId);
        if ($hospital === null) {
            return redirect()->to('/admin/users')->with('error', 'Hospital not found.');
        }

        $exists = $this->userModel->where('username', $username)->first();
        if ($exists !== null) {
            return redirect()->to('/admin/users')->with('error', 'Username already exists.');
        }

        $plainToken = bin2hex(random_bytes(32));

        $this->userModel->insert([
            'hospital_id' => $hospitalId,
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'api_token' => hash('sha256', $plainToken),
            'role' => 'hospital_admin',
            'is_active' => 1,
        ]);

        $message = 'User created. Save this API token now (it will not be shown again): ' . $plainToken;
        return redirect()->to('/admin/users')->with('message', $message);
    }

    public function m1Index()
    {
        return view('admin/m1/index', [
            'profiles' => $this->abhaProfileModel->orderBy('last_verified_at', 'DESC')->findAll(20),
        ]);
    }

    public function m1Module()
    {
        return view('admin/m1_module', [
            'message' => session()->getFlashdata('message'),
            'error' => session()->getFlashdata('error'),
            'result' => session()->getFlashdata('m1_result'),
        ]);
    }

    public function m1AbhaValidate()
    {
        return view('admin/m1/abha_validate', [
            'message' => session()->getFlashdata('message'),
            'error' => session()->getFlashdata('error'),
            'abhaUser' => session()->getFlashdata('abhaUser'),
            'lastRun' => session()->getFlashdata('lastRun'),
            'profiles' => $this->abhaProfileModel->orderBy('last_verified_at', 'DESC')->findAll(10),
        ]);
    }

    public function m1AbhaValidatePost()
    {
        $abhaId = trim((string) $this->request->getPost('abha_id'));
        $mode = strtolower(trim((string) $this->request->getPost('mode')));
        $token = $this->resolveGatewayToken();

        if ($abhaId === '') {
            return redirect()->to('/admin/m1/abha-validate')->with('error', 'ABHA number is required.');
        }

        if ($token === '') {
            return redirect()->to('/admin/m1/abha-validate')->with('error', 'Gateway bearer token is not configured (GATEWAY_BEARER_TOKEN).');
        }

        if (!in_array($mode, ['sandbox', 'live'], true)) {
            $mode = 'sandbox';
        }

        $requestPayload = [
            'mode' => $mode,
            'payload' => ['abha_id' => $abhaId],
            'token_source' => 'server',
            'token_preview' => $this->maskToken($token),
        ];

        try {
            $response = $this->callGatewayEndpoint('POST', 'api/v3/abha/validate', ['abha_id' => $abhaId], $token);
            $responseData = $response['decoded'];
            $body = is_array($responseData['data'] ?? null) ? $responseData['data'] : $responseData;
            $profileId = $this->saveAbhaProfile($body, $abhaId, (string) ($responseData['request_id'] ?? ''));

            $this->storeM1Log(
                'abdm.abha.validate',
                '/api/v3/abha/validate',
                $response['statusCode'],
                $requestPayload,
                ['profile_id' => $profileId, 'response' => $responseData]
            );

            return redirect()->to('/admin/m1/abha-validate')
                ->with('message', 'ABHA validated and profile master updated.')
                ->with('abhaUser', $body)
                ->with('lastRun', [
                    'statusCode' => $response['statusCode'],
                    'requestId' => (string) ($responseData['request_id'] ?? ''),
                ]);
        } catch (\Throwable $e) {
            $this->storeM1Log(
                'abdm.abha.validate',
                '/api/v3/abha/validate',
                500,
                $requestPayload,
                ['error' => $e->getMessage()]
            );

            return redirect()->to('/admin/m1/abha-validate')->with('error', 'ABHA validation failed: ' . $e->getMessage());
        }
    }

    public function m1AbhaValidateOtp()
    {
        return redirect()->to('/admin/m1/otp-flow');
    }

    // ── Guided OTP Flow (Aadhaar / Mobile) ──────────────────────────────────

    public function m1OtpFlow()
    {
        $step       = (int) (session()->getFlashdata('otp_step') ?: 1);
        $abhaNumber = (string) (session()->getFlashdata('otp_abha_number') ?: '');

        // Step 4: look up saved profile from DB by ABHA number
        $resultProfile = session()->getFlashdata('otp_result_profile');
        if ($step === 4 && $resultProfile === null && $abhaNumber !== '') {
            $row = $this->abhaProfileModel->where('abha_number', $abhaNumber)->first();
            if ($row !== null) {
                $j = is_string($row->profile_json ?? null) ? json_decode($row->profile_json, true) : null;
                $resultProfile = is_array($j) ? $j : (array) $row;
            }
        }

        return view('admin/m1/otp_flow', [
            'error'         => session()->getFlashdata('error'),
            'message'       => session()->getFlashdata('message'),
            'step'          => $step,
            'txnId'         => (string) (session()->getFlashdata('otp_txn_id') ?: ''),
            'otpType'       => (string) (session()->getFlashdata('otp_type') ?: 'aadhaar'),
            'otpInput'      => (string) (session()->getFlashdata('otp_input') ?: ''),
            'resultProfile' => $resultProfile,
            'suggestions'   => (array)  (session()->getFlashdata('otp_suggestions') ?: []),
            'enrolTxnId'    => (string) (session()->getFlashdata('otp_enrol_txn_id') ?: ''),
            'xToken'        => (string) (session()->getFlashdata('otp_x_token') ?: ''),
            'abhaNumber'    => $abhaNumber,
        ]);
    }

    public function m1OtpGeneratePost()
    {
        $otpType = $this->request->getPost('otp_type') === 'mobile' ? 'mobile' : 'aadhaar';
        $input   = trim((string) $this->request->getPost('otp_input'));
        $mode    = strtolower(trim((string) $this->request->getPost('mode')));
        $token   = $this->resolveGatewayToken();

        if ($input === '') {
            return redirect()->to('/admin/m1/otp-flow')
                ->with('error', 'Please enter ' . ($otpType === 'mobile' ? 'mobile number.' : 'Aadhaar number.'))
                ->with('otp_type', $otpType)
                ->with('otp_step', 1);
        }

        if ($token === '') {
            return redirect()->to('/admin/m1/otp-flow')
                ->with('error', 'Gateway bearer token is not configured (GATEWAY_BEARER_TOKEN).')
                ->with('otp_type', $otpType)
                ->with('otp_step', 1);
        }

        if (!in_array($mode, ['sandbox', 'live'], true)) {
            $mode = 'sandbox';
        }

        $path        = $otpType === 'mobile' ? 'api/v3/abha/mobile/generate-otp' : 'api/v3/abha/aadhaar/generate-otp';
        $fieldName   = $otpType === 'mobile' ? 'mobile' : 'aadhaar';
        $payload     = [$fieldName => $input];
        $reqPayload  = ['mode' => $mode, 'payload' => $payload, 'otp_type' => $otpType, 'token_source' => 'server'];

        try {
            $response = $this->callGatewayEndpoint('POST', $path, $payload, $token);
            $decoded  = $response['decoded'];
            $txnId    = (string) ($decoded['txnId'] ?? $decoded['data']['txnId'] ?? $decoded['transaction_id'] ?? '');

            $this->storeM1Log(
                'abdm.abha.' . $otpType . '.generate_otp',
                '/' . $path,
                $response['statusCode'],
                $reqPayload,
                $decoded
            );

            return redirect()->to('/admin/m1/otp-flow')
                ->with('message', 'OTP sent. Enter the OTP you received to continue.')
                ->with('otp_step', 2)
                ->with('otp_txn_id', $txnId)
                ->with('otp_type', $otpType)
                ->with('otp_input', $input);

        } catch (\Throwable $e) {
            $this->storeM1Log(
                'abdm.abha.' . $otpType . '.generate_otp',
                '/' . $path,
                500,
                $reqPayload,
                ['error' => $e->getMessage()]
            );

            return redirect()->to('/admin/m1/otp-flow')
                ->with('error', 'Failed to generate OTP: ' . $e->getMessage())
                ->with('otp_type', $otpType)
                ->with('otp_step', 1);
        }
    }

    public function m1OtpVerifyPost()
    {
        $otpType  = $this->request->getPost('otp_type') === 'mobile' ? 'mobile' : 'aadhaar';
        $txnId    = trim((string) $this->request->getPost('txn_id'));
        $otp      = trim((string) $this->request->getPost('otp'));
        $otpInput = trim((string) $this->request->getPost('otp_input'));
        $mobile   = trim((string) $this->request->getPost('mobile'));   // required for Aadhaar verify (ABHA communication)
        $mode     = strtolower(trim((string) $this->request->getPost('mode')));
        $token    = $this->resolveGatewayToken();

        if ($otp === '') {
            return redirect()->to('/admin/m1/otp-flow')
                ->with('error', 'OTP is required.')
                ->with('otp_step', 2)
                ->with('otp_txn_id', $txnId)
                ->with('otp_type', $otpType)
                ->with('otp_input', $otpInput);
        }

        if ($token === '') {
            return redirect()->to('/admin/m1/otp-flow')->with('error', 'Gateway bearer token is not configured.');
        }

        if (!in_array($mode, ['sandbox', 'live'], true)) {
            $mode = 'sandbox';
        }

        $path       = $otpType === 'mobile' ? 'api/v3/abha/mobile/verify-otp' : 'api/v3/abha/aadhaar/verify-otp';
        $payload    = ['txnId' => $txnId, 'otp' => $otp, 'mobile' => $mobile];
        $reqPayload = ['mode' => $mode, 'payload' => $payload, 'otp_type' => $otpType, 'token_source' => 'server'];

        try {
            $response  = $this->callGatewayEndpoint('POST', $path, $payload, $token);
            $decoded   = $response['decoded'];

            // Handle both test-mode mock (data.abhaNumber) and ABDM v3 live (ABHAProfile.ABHANumber)
            $profileData = is_array($decoded['data'] ?? null) ? $decoded['data']
                : (is_array($decoded['ABHAProfile'] ?? null) ? $decoded['ABHAProfile'] : $decoded);
            $requestId   = (string) ($decoded['request_id'] ?? '');
            $abhaNumber  = (string) ($profileData['abhaNumber'] ?? $profileData['ABHANumber'] ?? $profileData['abha_id'] ?? '');

            $profileId = null;
            if ($abhaNumber !== '' || $profileData !== []) {
                try {
                    $profileId = $this->saveAbhaProfile($profileData, $abhaNumber, $requestId);
                } catch (\Throwable $ex) {
                    log_message('warning', 'OTP verify: profile save skipped: ' . $ex->getMessage());
                }
            }

            $this->storeM1Log(
                'abdm.abha.' . $otpType . '.verify_otp',
                '/' . $path,
                $response['statusCode'],
                $reqPayload,
                ['profile_id' => $profileId, 'response' => $decoded]
            );

            // Extract X-Token and enrollment txnId from ABDM v3 response (not present in test mode)
            $xToken     = (string) ($decoded['tokens']['token'] ?? '');
            $enrolTxnId = (string) ($decoded['txnId'] ?? '');

            // In live mode, fetch ABHA address suggestions for step 3
            $suggestions = [];
            if ($enrolTxnId !== '' && !(bool) config('AbdmGateway')->testMode) {
                try {
                    $suggestions = $this->fetchAbdmAddressSuggestions($enrolTxnId);
                } catch (\Throwable $ex) {
                    log_message('warning', 'ABHA address suggestions skipped: ' . $ex->getMessage());
                }
            }

            $msg = 'OTP verified. ABHA created successfully.';
            if ($profileId !== null) {
                $msg .= ' (Profile ID: ' . $profileId . ')';
            }

            // If we have suggestions or live mode, go to step 3 for address selection.
            // In test mode (no real enrollment txnId), go directly to step 4.
            $nextStep = (count($suggestions) > 0 || ($enrolTxnId !== '' && !(bool) config('AbdmGateway')->testMode)) ? 3 : 4;

            return redirect()->to('/admin/m1/otp-flow')
                ->with('message', $msg)
                ->with('otp_result_profile', $nextStep === 4 ? $profileData : null)
                ->with('otp_abha_number', $abhaNumber)
                ->with('otp_x_token', $xToken)
                ->with('otp_enrol_txn_id', $enrolTxnId)
                ->with('otp_suggestions', $suggestions)
                ->with('otp_step', $nextStep);

        } catch (\Throwable $e) {
            $this->storeM1Log(
                'abdm.abha.' . $otpType . '.verify_otp',
                '/' . $path,
                500,
                $reqPayload,
                ['error' => $e->getMessage()]
            );

            return redirect()->to('/admin/m1/otp-flow')
                ->with('error', 'OTP verification failed: ' . $e->getMessage())
                ->with('otp_step', 2)
                ->with('otp_txn_id', $txnId)
                ->with('otp_type', $otpType)
                ->with('otp_input', $otpInput);
        }
    }

    public function m1AbhaProfiles()
    {
        return view('admin/m1/profiles', [
            'profiles' => $this->abhaProfileModel->orderBy('last_verified_at', 'DESC')->findAll(200),
        ]);
    }

    public function m1ScanShare()
    {
        $date   = trim((string) ($this->request->getGet('date') ?: date('Y-m-d')));
        $model  = new AbdmTokenQueue();
        $tokens = $model->where('token_date', $date)->orderBy('token_number', 'ASC')->findAll(500);
        return view('admin/m1/scan_share', [
            'tokens' => $tokens,
            'date'   => $date,
        ]);
    }

    public function m1ScanShareSetup()
    {
        return view('admin/m1/scan_share_setup', [
            'error'   => session()->getFlashdata('error'),
            'message' => session()->getFlashdata('message'),
            'result'  => session()->getFlashdata('result'),
        ]);
    }

    public function m1ScanShareSetupPost()
    {
        $action = (string) $this->request->getPost('action');

        try {
            $abdmToken = $this->fetchAbdmTokenForAdmin();
            $requestId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000, mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff));
            $ts        = gmdate('Y-m-d\TH:i:s.000\Z');

            if ($action === 'bridge_url') {
                $url = trim((string) $this->request->getPost('bridge_url'));
                if ($url === '') {
                    return redirect()->to('/admin/m1/scan-share-setup')->with('error', 'Bridge URL is required.');
                }
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL            => 'https://dev.abdm.gov.in/api/hiecm/gateway/v3/bridge/url',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => 20,
                    CURLOPT_CUSTOMREQUEST  => 'PATCH',
                    CURLOPT_POSTFIELDS     => json_encode(['url' => $url]),
                    CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json', 'Authorization: Bearer ' . $abdmToken, 'REQUEST-ID: ' . $requestId, 'TIMESTAMP: ' . $ts],
                    CURLOPT_SSL_VERIFYPEER => false,
                ]);
                $raw  = curl_exec($ch);
                $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                $result = ['action' => 'bridge_url', 'url' => $url, 'http_status' => $code, 'response' => substr((string) $raw, 0, 300)];
                $msg = $code < 300 ? 'Bridge URL updated (HTTP ' . $code . ').' : 'Returned HTTP ' . $code . '. See result below.';
                return redirect()->to('/admin/m1/scan-share-setup')->with('message', $msg)->with('result', $result);

            } elseif ($action === 'register_hip') {
                $facilityId   = trim((string) $this->request->getPost('facility_id'));
                $facilityName = trim((string) $this->request->getPost('facility_name'));
                $hipName      = trim((string) $this->request->getPost('hip_name'));
                $clientId     = (string) (config('AbdmGateway')->abdmClientId ?: env('ABDM_CLIENT_ID', ''));
                if ($facilityId === '' || $facilityName === '' || $hipName === '') {
                    return redirect()->to('/admin/m1/scan-share-setup')->with('error', 'All HIP fields are required.');
                }
                $body = ['facilityId' => $facilityId, 'facilityName' => $facilityName, 'HRP' => [['bridgeId' => $clientId, 'hipName' => $hipName, 'type' => 'HIP', 'active' => true]]];
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL            => 'https://facilitysbx.abdm.gov.in/v1/bridges/MutipleHRPAddUpdateServices',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT        => 20,
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => json_encode($body),
                    CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json', 'Authorization: Bearer ' . $abdmToken, 'REQUEST-ID: ' . $requestId, 'TIMESTAMP: ' . $ts],
                    CURLOPT_SSL_VERIFYPEER => false,
                ]);
                $raw  = curl_exec($ch);
                $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                $result = ['action' => 'register_hip', 'body' => $body, 'http_status' => $code, 'response' => substr((string) $raw, 0, 400)];
                $msg = $code < 300 ? 'HIP registered (HTTP ' . $code . ').' : 'HIP registration returned HTTP ' . $code . '. See result.';
                return redirect()->to('/admin/m1/scan-share-setup')->with('message', $msg)->with('result', $result);
            }
        } catch (\Throwable $e) {
            return redirect()->to('/admin/m1/scan-share-setup')->with('error', 'Error: ' . $e->getMessage());
        }

        return redirect()->to('/admin/m1/scan-share-setup')->with('error', 'Unknown action.');
    }

    public function m1AbhaCard()
    {
        $xToken = trim((string) $this->request->getGet('x_token'));

        if ($xToken === '') {
            return $this->response->setStatusCode(400)->setBody('Missing x_token');
        }

        try {
            $abdmToken = $this->fetchAbdmTokenForAdmin();
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => 'https://abhasbx.abdm.gov.in/abha/api/v3/profile/account/abha-card',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_HEADER         => true,
                CURLOPT_HTTPHEADER     => [
                    'Accept: image/png, image/svg+xml, */*',
                    'Authorization: Bearer ' . $abdmToken,
                    'X-Token: Bearer ' . $xToken,
                    'REQUEST-ID: ' . sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000, mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff)),
                    'TIMESTAMP: ' . gmdate('Y-m-d\TH:i:s.000\Z'),
                ],
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $raw      = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            curl_close($ch);

            $headers = substr((string) $raw, 0, $headerSize);
            $body    = substr((string) $raw, $headerSize);

            if ($httpCode >= 400) {
                return $this->response->setStatusCode(502)->setBody('ABDM card API error (HTTP ' . $httpCode . '): ' . substr($body, 0, 300));
            }

            // Detect content type from response headers
            $contentType = 'image/png';
            if (preg_match('/content-type:\s*([^\r\n]+)/i', $headers, $m)) {
                $contentType = trim($m[1]);
            }

            // If ABDM returned JSON with base64 image, decode it
            if (str_contains($contentType, 'application/json') || str_contains($contentType, 'text/')) {
                $decoded = json_decode($body, true);
                if (is_array($decoded)) {
                    $b64 = $decoded['image'] ?? $decoded['data'] ?? $decoded['abhaCard'] ?? null;
                    if ($b64 !== null) {
                        $body = base64_decode((string) $b64);
                        $contentType = 'image/png';
                    }
                }
            }

            return $this->response
                ->setHeader('Content-Type', $contentType)
                ->setHeader('Cache-Control', 'no-store')
                ->setBody($body);

        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setBody('Error: ' . $e->getMessage());
        }
    }

    public function m1OtpAddressSetPost()
    {
        $enrolTxnId  = trim((string) $this->request->getPost('enrol_txn_id'));
        $abhaAddress = trim((string) $this->request->getPost('abha_address'));
        $xToken      = trim((string) $this->request->getPost('x_token'));
        $abhaNumber  = trim((string) $this->request->getPost('abha_number'));
        $skip        = $this->request->getPost('skip_address') !== null;

        $message = 'ABHA created successfully.';

        if (!$skip && $abhaAddress !== '') {
            try {
                $abdmToken = $this->fetchAbdmTokenForAdmin();
                $this->abdmPost(
                    'https://abhasbx.abdm.gov.in/abha/api/v3/enrollment/enrol/abha-address',
                    ['txnId' => $enrolTxnId, 'abhaAddress' => $abhaAddress, 'preferred' => 1],
                    $abdmToken,
                    sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000, mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff)),
                    gmdate('Y-m-d\TH:i:s.000\Z')
                );
                $message = 'ABHA address set to ' . $abhaAddress . '.';
                if ($abhaNumber !== '') {
                    $this->abhaProfileModel->where('abha_number', $abhaNumber)->set([
                        'abha_address' => $abhaAddress,
                        'phr_address'  => $abhaAddress,
                        'updated_at'   => date('Y-m-d H:i:s'),
                    ])->update();
                }
            } catch (\Throwable $e) {
                log_message('warning', 'ABHA address set failed: ' . $e->getMessage());
                $message = 'ABHA created. Address selection failed: ' . $e->getMessage();
            }
        }

        return redirect()->to('/admin/m1/otp-flow')
            ->with('message', $message)
            ->with('otp_x_token', $xToken)
            ->with('otp_abha_number', $abhaNumber)
            ->with('otp_step', 4);
    }

    // -----------------------------------------------------------------------
    // ABHA Verification Flow (for existing ABHA holders)
    // -----------------------------------------------------------------------

    public function m1VerifyFlow()
    {
        return view('admin/m1/verify_flow', [
            'error'         => session()->getFlashdata('error'),
            'message'       => session()->getFlashdata('message'),
            'step'          => (string) (session()->getFlashdata('verify_step') ?: '1'),
            'txnId'         => (string) (session()->getFlashdata('verify_txn_id') ?: ''),
            'verifyMethod'  => (string) (session()->getFlashdata('verify_method') ?: 'abha-abdm'),
            'loginId'       => (string) (session()->getFlashdata('verify_login_id') ?: ''),
            'abhaList'      => (array)  (session()->getFlashdata('verify_abha_list') ?: []),
            'resultProfile' => session()->getFlashdata('verify_result_profile'),
            'xToken'        => (string) (session()->getFlashdata('verify_x_token') ?: ''),
        ]);
    }

    public function m1VerifyOtpRequestPost()
    {
        $method  = trim((string) $this->request->getPost('verify_method'));
        $loginId = trim((string) $this->request->getPost('login_id'));

        if (!in_array($method, ['abha-abdm', 'abha-aadhaar', 'mobile'], true)) {
            $method = 'abha-abdm';
        }

        if ($loginId === '') {
            return redirect()->to('/admin/m1/verify-flow')
                ->with('error', 'Please enter ' . ($method === 'mobile' ? 'mobile number.' : 'ABHA number.'))
                ->with('verify_method', $method)
                ->with('verify_step', '1');
        }

        try {
            $result = $this->callAbdmLoginRequestOtp($method, $loginId);
            $txnId  = (string) ($result['decoded']['txnId'] ?? '');

            return redirect()->to('/admin/m1/verify-flow')
                ->with('message', 'OTP sent successfully. Enter the OTP you received.')
                ->with('verify_step', '2')
                ->with('verify_txn_id', $txnId)
                ->with('verify_method', $method)
                ->with('verify_login_id', $loginId);

        } catch (\Throwable $e) {
            return redirect()->to('/admin/m1/verify-flow')
                ->with('error', 'Failed to send OTP: ' . $e->getMessage())
                ->with('verify_method', $method)
                ->with('verify_login_id', $loginId)
                ->with('verify_step', '1');
        }
    }

    public function m1VerifyOtpConfirmPost()
    {
        $method  = trim((string) $this->request->getPost('verify_method'));
        $txnId   = trim((string) $this->request->getPost('txn_id'));
        $otp     = trim((string) $this->request->getPost('otp'));
        $loginId = trim((string) $this->request->getPost('login_id'));

        if (!in_array($method, ['abha-abdm', 'abha-aadhaar', 'mobile'], true)) {
            $method = 'abha-abdm';
        }

        if ($otp === '') {
            return redirect()->to('/admin/m1/verify-flow')
                ->with('error', 'OTP is required.')
                ->with('verify_step', '2')
                ->with('verify_txn_id', $txnId)
                ->with('verify_method', $method)
                ->with('verify_login_id', $loginId);
        }

        try {
            $result  = $this->callAbdmLoginVerifyOtp($method, $txnId, $otp);
            $decoded = $result['decoded'];

            // Mobile method may return a list of linked ABHA numbers to select from
            $abhaList = $decoded['accounts'] ?? $decoded['ABHANumbers'] ?? null;
            if ($method === 'mobile' && is_array($abhaList) && count($abhaList) > 1) {
                $newTxnId = (string) ($decoded['txnId'] ?? $txnId);
                return redirect()->to('/admin/m1/verify-flow')
                    ->with('message', 'Multiple ABHA numbers found on this mobile. Please select one.')
                    ->with('verify_step', '2b')
                    ->with('verify_txn_id', $newTxnId)
                    ->with('verify_method', $method)
                    ->with('verify_login_id', $loginId)
                    ->with('verify_abha_list', $abhaList);
            }

            // Extract X-Token (user-scoped token for this ABHA account)
            $xToken = (string) ($decoded['token'] ?? $decoded['xToken'] ?? $decoded['tokens']['token'] ?? '');
            $profile = [];

            if ($xToken !== '') {
                try {
                    $profResult = $this->fetchAbdmProfileWithXToken($xToken);
                    $profile    = $profResult['decoded']['profile']
                        ?? $profResult['decoded']['ABHAProfile']
                        ?? $profResult['decoded'];
                } catch (\Throwable $ex) {
                    log_message('warning', 'Verify: profile fetch skipped: ' . $ex->getMessage());
                    $profile = is_array($decoded['ABHAProfile'] ?? null) ? $decoded['ABHAProfile'] : $decoded;
                }
            }

            // Save to patient master
            $profileData = !empty($profile) ? $profile : $decoded;
            $abhaNumber  = (string) ($profileData['ABHANumber'] ?? $profileData['abhaNumber'] ?? $loginId);
            if (!empty($profileData)) {
                try {
                    $this->saveAbhaProfile($profileData, $abhaNumber, '');
                } catch (\Throwable $ex) {
                    log_message('warning', 'Verify: profile save skipped: ' . $ex->getMessage());
                }
            }

            return redirect()->to('/admin/m1/verify-flow')
                ->with('message', 'ABHA verified successfully.')
                ->with('verify_result_profile', $profileData)
                ->with('verify_x_token', $xToken)
                ->with('verify_step', '3');

        } catch (\Throwable $e) {
            return redirect()->to('/admin/m1/verify-flow')
                ->with('error', 'OTP verification failed: ' . $e->getMessage())
                ->with('verify_step', '2')
                ->with('verify_txn_id', $txnId)
                ->with('verify_method', $method)
                ->with('verify_login_id', $loginId);
        }
    }

    public function m1VerifyUserSelectPost()
    {
        $txnId      = trim((string) $this->request->getPost('txn_id'));
        $abhaNumber = trim((string) $this->request->getPost('abha_number'));
        $loginId    = trim((string) $this->request->getPost('login_id'));

        if ($abhaNumber === '') {
            return redirect()->to('/admin/m1/verify-flow')
                ->with('error', 'Please select an ABHA number.')
                ->with('verify_step', '2b')
                ->with('verify_txn_id', $txnId)
                ->with('verify_method', 'mobile')
                ->with('verify_login_id', $loginId);
        }

        try {
            $result  = $this->callAbdmLoginVerifyUser($txnId, $abhaNumber);
            $decoded = $result['decoded'];
            $xToken  = (string) ($decoded['token'] ?? $decoded['xToken'] ?? $decoded['tokens']['token'] ?? '');
            $profile = [];

            if ($xToken !== '') {
                try {
                    $profResult = $this->fetchAbdmProfileWithXToken($xToken);
                    $profile    = $profResult['decoded']['profile']
                        ?? $profResult['decoded']['ABHAProfile']
                        ?? $profResult['decoded'];
                } catch (\Throwable $ex) {
                    log_message('warning', 'Verify user-select: profile fetch skipped: ' . $ex->getMessage());
                }
            }

            $profileData = !empty($profile) ? $profile : $decoded;
            $abhaNum     = (string) ($profileData['ABHANumber'] ?? $profileData['abhaNumber'] ?? $abhaNumber);
            if (!empty($profileData)) {
                try {
                    $this->saveAbhaProfile($profileData, $abhaNum, '');
                } catch (\Throwable $ex) {
                    log_message('warning', 'Verify user-select: profile save skipped: ' . $ex->getMessage());
                }
            }

            return redirect()->to('/admin/m1/verify-flow')
                ->with('message', 'ABHA verified successfully.')
                ->with('verify_result_profile', $profileData)
                ->with('verify_x_token', $xToken)
                ->with('verify_step', '3');

        } catch (\Throwable $e) {
            return redirect()->to('/admin/m1/verify-flow')
                ->with('error', 'Failed to confirm ABHA selection: ' . $e->getMessage())
                ->with('verify_step', '2b')
                ->with('verify_txn_id', $txnId)
                ->with('verify_method', 'mobile')
                ->with('verify_login_id', $loginId);
        }
    }

    public function runM1Test()
    {
        if (!$this->request->is('post')) {
            return redirect()->to('/admin/m1-module');
        }

        $endpointKey = trim((string) $this->request->getPost('endpoint_key'));
        $payloadRaw = trim((string) $this->request->getPost('payload_json'));
        $mode = strtolower(trim((string) $this->request->getPost('mode')));
        $submittedToken = trim((string) $this->request->getPost('token'));
        $allowTokenOverride = filter_var(env('GATEWAY_DEBUG_ALLOW_TOKEN_OVERRIDE', 'false'), FILTER_VALIDATE_BOOLEAN);
        $token = $allowTokenOverride && $submittedToken !== ''
            ? $submittedToken
            : $this->resolveGatewayToken();

        $endpointMap = [
            'abha_validate' => ['method' => 'POST', 'path' => 'api/v3/abha/validate', 'event' => 'abdm.abha.validate'],
            'aadhaar_generate_otp' => ['method' => 'POST', 'path' => 'api/v3/abha/aadhaar/generate-otp', 'event' => 'abdm.abha.aadhaar.generate_otp'],
            'aadhaar_verify_otp' => ['method' => 'POST', 'path' => 'api/v3/abha/aadhaar/verify-otp', 'event' => 'abdm.abha.aadhaar.verify_otp'],
            'mobile_generate_otp' => ['method' => 'POST', 'path' => 'api/v3/abha/mobile/generate-otp', 'event' => 'abdm.abha.mobile.generate_otp'],
            'mobile_verify_otp' => ['method' => 'POST', 'path' => 'api/v3/abha/mobile/verify-otp', 'event' => 'abdm.abha.mobile.verify_otp'],
            'gateway_status' => ['method' => 'GET', 'path' => 'api/v3/gateway/status', 'event' => 'abdm.gateway.status'],
            'health' => ['method' => 'GET', 'path' => 'api/v3/health', 'event' => 'abdm.gateway.health'],
        ];

        if (!isset($endpointMap[$endpointKey])) {
            return redirect()->to('/admin/m1-module')->with('error', 'Invalid M1 test endpoint selected.');
        }

        if ($token === '') {
            return redirect()->to('/admin/m1-module')->with('error', 'Gateway bearer token is not configured on server.');
        }

        if (!in_array($mode, ['sandbox', 'live'], true)) {
            $mode = 'sandbox';
        }

        $payload = [];
        if ($payloadRaw !== '') {
            $decoded = json_decode($payloadRaw, true);
            if (!is_array($decoded)) {
                return redirect()->to('/admin/m1-module')->with('error', 'Payload must be valid JSON object.');
            }
            $payload = $decoded;
        }

        $target = $endpointMap[$endpointKey];
        $requestPayload = [
            'mode' => $mode,
            'payload' => $payload,
            'token_source' => $allowTokenOverride && $submittedToken !== '' ? 'request_override' : 'server',
            'token_preview' => $this->maskToken($token),
        ];

        try {
            $response = $this->callGatewayEndpoint($target['method'], $target['path'], $payload, $token);

            $this->storeM1Log(
                $target['event'],
                '/' . $target['path'],
                $response['statusCode'],
                $requestPayload,
                $response['decoded'] ?? ['raw' => $response['body']]
            );

            return redirect()->to('/admin/m1-module')->with('m1_result', [
                'endpointKey' => $endpointKey,
                'method' => $target['method'],
                'path' => '/' . $target['path'],
                'statusCode' => $response['statusCode'],
                'requestJson' => $this->encodePretty($requestPayload),
                'responseBody' => $this->encodePretty($response['decoded'] ?? ['raw' => $response['body']]),
            ]);
        } catch (\Throwable $e) {
            $this->storeM1Log(
                $target['event'],
                '/' . $target['path'],
                500,
                $requestPayload,
                ['error' => $e->getMessage()]
            );

            return redirect()->to('/admin/m1-module')->with('error', 'M1 test failed: ' . $e->getMessage());
        }
    }

    public function exportM1Logs()
    {
        $format = strtolower((string) $this->request->getGet('format'));
        $logs = $this->testLogModel
            ->select('abdm_test_submission_logs.*, abdm_hospitals.hospital_name, abdm_hospital_users.username')
            ->join('abdm_hospitals', 'abdm_hospitals.id = abdm_test_submission_logs.hospital_id', 'left')
            ->join('abdm_hospital_users', 'abdm_hospital_users.id = abdm_test_submission_logs.user_id', 'left')
            ->orderBy('abdm_test_submission_logs.id', 'DESC')
            ->findAll(200);

        if ($format === 'json') {
            return $this->response
                ->setHeader('Content-Disposition', 'attachment; filename="m1_test_logs_' . date('Ymd_His') . '.json"')
                ->setJSON($logs);
        }

        if ($format !== 'csv') {
            return redirect()->to('/admin/m1-module')->with('error', 'Invalid export format.');
        }

        $rows = [];
        $rows[] = ['id', 'request_id', 'hospital_name', 'username', 'event_type', 'endpoint', 'http_status', 'request_payload', 'response_payload', 'created_at'];

        foreach ($logs as $log) {
            $reqPayload = $log->request_payload ?? $log->test_data ?? '';
            $resPayload = $log->response_payload ?? $log->response ?? '';
            $rows[] = [
                (string) ($log->id ?? ''),
                (string) ($log->request_id ?? ''),
                (string) ($log->hospital_name ?? ''),
                (string) ($log->username ?? ''),
                (string) ($log->event_type ?? $log->test_type ?? ''),
                (string) ($log->endpoint ?? ''),
                (string) ($log->http_status ?? $log->status ?? ''),
                is_string($reqPayload) ? $reqPayload : json_encode($reqPayload),
                is_string($resPayload) ? $resPayload : json_encode($resPayload),
                (string) ($log->created_at ?? ''),
            ];
        }

        $csv = '';
        foreach ($rows as $row) {
            $escaped = array_map(static function ($value): string {
                $text = (string) $value;
                return '"' . str_replace('"', '""', $text) . '"';
            }, $row);
            $csv .= implode(',', $escaped) . "\r\n";
        }

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="m1_test_logs_' . date('Ymd_His') . '.csv"')
            ->setBody($csv);
    }

    public function testSubmissionLogs()
    {
        $logs = $this->testLogModel
            ->select('abdm_test_submission_logs.*, abdm_hospitals.hospital_name, abdm_hospital_users.username')
            ->join('abdm_hospitals', 'abdm_hospitals.id = abdm_test_submission_logs.hospital_id', 'left')
            ->join('abdm_hospital_users', 'abdm_hospital_users.id = abdm_test_submission_logs.user_id', 'left')
            ->orderBy('abdm_test_submission_logs.id', 'DESC')
            ->findAll(200);

        return view('admin/test_logs', ['logs' => $logs]);
    }

    public function requestLogs()
    {
        $logs = (new AbdmRequestLog())
            ->orderBy('id', 'DESC')
            ->findAll(200);

        return view('admin/request_logs', ['logs' => $logs]);
    }

    public function auditTrail()
    {
        $logs = (new AbdmAuditTrail())
            ->orderBy('id', 'DESC')
            ->findAll(200);

        return view('admin/audit_trail', ['logs' => $logs]);
    }

    public function bundles()
    {
        $logs = (new AbdmBundle())
            ->orderBy('id', 'DESC')
            ->findAll(200);

        return view('admin/bundles', ['logs' => $logs]);
    }

    public function hmsAccess()
    {
        $data = [
            'hospitals' => $this->hospitalModel->where('is_active', 1)->orderBy('hospital_name', 'ASC')->findAll(),
            'credentials' => $this->hmsCredentialModel
                ->select('hms_credentials.*, abdm_hospitals.hospital_name, abdm_hospitals.hfr_id')
                ->join('abdm_hospitals', 'abdm_hospitals.id = hms_credentials.hospital_id', 'left')
                ->orderBy('hms_credentials.id', 'DESC')
                ->findAll(200),
            'message' => session()->getFlashdata('message'),
            'error' => session()->getFlashdata('error'),
        ];

        return view('admin/hms_access', $data);
    }

    public function hmsCredentialDetail(int $id)
    {
        $credential = $this->hmsCredentialModel
            ->select('hms_credentials.*, abdm_hospitals.hospital_name, abdm_hospitals.hfr_id')
            ->join('abdm_hospitals', 'abdm_hospitals.id = hms_credentials.hospital_id', 'left')
            ->where('hms_credentials.id', $id)
            ->first();

        if ($credential === null) {
            return redirect()->to('/admin/hms-access')->with('error', 'Credential not found.');
        }

        return view('admin/hms_credential_detail', [
            'credential' => $credential,
            'message' => session()->getFlashdata('message'),
            'error' => session()->getFlashdata('error'),
        ]);
    }

    public function createHmsCredential()
    {
        $hospitalId = (int) $this->request->getPost('hospital_id');
        $hmsName = trim((string) $this->request->getPost('hms_name'));
        $hmsEndpoint = trim((string) $this->request->getPost('hms_api_endpoint'));
        $hmsAuthType = strtolower(trim((string) $this->request->getPost('hms_auth_type')));

        if ($hospitalId <= 0 || $hmsName === '' || $hmsEndpoint === '') {
            return redirect()->to('/admin/hms-access')->with('error', 'Hospital, HMS name, and API endpoint are required.');
        }

        $hospital = $this->hospitalModel->find($hospitalId);
        if ($hospital === null) {
            return redirect()->to('/admin/hms-access')->with('error', 'Hospital not found.');
        }

        if (!in_array($hmsAuthType, ['api_key', 'basic'], true)) {
            $hmsAuthType = 'api_key';
        }

        $insertData = [
            'hospital_id' => $hospitalId,
            'hms_name' => $hmsName,
            'hms_api_endpoint' => $hmsEndpoint,
            'hms_auth_type' => $hmsAuthType,
            'is_active' => 1,
        ];

        if ($hmsAuthType === 'api_key') {
            $hmsApiKey = trim((string) $this->request->getPost('hms_api_key'));
            if ($hmsApiKey === '') {
                return redirect()->to('/admin/hms-access')->with('error', 'API key is required for api_key auth type.');
            }
            $insertData['hms_api_key'] = $this->encryptCredential($hmsApiKey);
        } elseif ($hmsAuthType === 'basic') {
            $hmsUsername = trim((string) $this->request->getPost('hms_username'));
            $hmsPassword = trim((string) $this->request->getPost('hms_password'));
            if ($hmsUsername === '' || $hmsPassword === '') {
                return redirect()->to('/admin/hms-access')->with('error', 'Username and password are required for basic auth type.');
            }
            $insertData['hms_username'] = $hmsUsername;
            $insertData['hms_password'] = $this->encryptCredential($hmsPassword);
        }

        $this->hmsCredentialModel->insert($insertData);

        return redirect()->to('/admin/hms-access')->with('message', 'HMS credential created successfully.');
    }

    public function updateHmsCredential(int $id)
    {
        $credential = $this->hmsCredentialModel->find($id);
        if ($credential === null) {
            return redirect()->to('/admin/hms-access')->with('error', 'Credential not found.');
        }

        $hmsEndpoint = trim((string) $this->request->getPost('hms_api_endpoint'));
        if ($hmsEndpoint === '') {
            return redirect()->to('/admin/hms-access')->with('error', 'API endpoint is required.');
        }

        $updateData = [
            'hms_api_endpoint' => $hmsEndpoint,
            'is_active' => (int) ($this->request->getPost('is_active') === '1'),
        ];

        if ($credential->hms_auth_type === 'api_key') {
            $hmsApiKey = trim((string) $this->request->getPost('hms_api_key'));
            if ($hmsApiKey !== '') {
                $updateData['hms_api_key'] = $this->encryptCredential($hmsApiKey);
            }
        } elseif ($credential->hms_auth_type === 'basic') {
            $hmsPassword = trim((string) $this->request->getPost('hms_password'));
            if ($hmsPassword !== '') {
                $updateData['hms_password'] = $this->encryptCredential($hmsPassword);
            }
        }

        $this->hmsCredentialModel->update($id, $updateData);

        return redirect()->to('/admin/hms-access')->with('message', 'HMS credential updated successfully.');
    }

    public function testHmsCredential(int $id)
    {
        $credential = $this->hmsCredentialModel->find($id);
        if ($credential === null) {
            return redirect()->to('/admin/hms-access')->with('error', 'Credential not found.');
        }

        $result = $this->hmsCredentialModel->testConnection($credential);

        if ($result['success']) {
            $this->hmsCredentialModel->update($id, ['is_verified' => 1, 'last_verified_at' => date('Y-m-d H:i:s')]);
            $message = 'HMS connection verified successfully (HTTP ' . $result['status_code'] . ').';
        } else {
            $this->hmsCredentialModel->update($id, ['is_verified' => 0]);
            $message = 'HMS connection failed: ' . $result['message'];
        }

        return redirect()->to('/admin/hms-access')->with($result['success'] ? 'message' : 'error', $message);
    }

    public function deleteHmsCredential(int $id)
    {
        $credential = $this->hmsCredentialModel->find($id);
        if ($credential === null) {
            return redirect()->to('/admin/hms-access')->with('error', 'Credential not found.');
        }

        $this->hmsCredentialModel->delete($id);

        return redirect()->to('/admin/hms-access')->with('message', 'HMS credential deleted successfully.');
    }

    public function fetchAbdmToken()
    {
        $cfg = config('AbdmGateway');
        $clientId = trim((string) ($cfg->abdmClientId ?? ''));
        $clientSecret = trim((string) ($cfg->abdmClientSecret ?? ''));
        $authUrl = trim((string) ($cfg->abdmAuthUrl ?? 'https://dev.abdm.gov.in/gateway/v0.5/sessions'));

        if ($clientId === '' || $clientSecret === '') {
            return $this->response->setJSON(['error' => 'ABDM credentials not configured.'])->setStatusCode(500);
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $authUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['clientId' => $clientId, 'clientSecret' => $clientSecret]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return $this->response->setJSON(['error' => 'cURL error: ' . $curlError])->setStatusCode(500);
        }

        $decoded = json_decode((string) $response, true);
        $token = '';
        if (is_array($decoded)) {
            $token = (string) ($decoded['accessToken'] ?? $decoded['token'] ?? $decoded['authToken'] ?? '');
        }

        if ($httpCode < 200 || $httpCode >= 300 || $token === '') {
            $preview = is_string($response) ? substr($response, 0, 300) : '';
            return $this->response->setJSON(['error' => 'ABDM auth failed (HTTP ' . $httpCode . '): ' . $preview])->setStatusCode(502);
        }

        return $this->response->setJSON(['token' => $token, 'expires_in' => (int) ($decoded['expiresIn'] ?? 0)]);
    }

    // -----------------------------------------------------------------------
    // ABHA Verification private helpers
    // -----------------------------------------------------------------------

    private function fetchAbdmAddressSuggestions(string $enrolTxnId): array
    {
        $abdmToken = $this->fetchAbdmTokenForAdmin();
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => 'https://abhasbx.abdm.gov.in/abha/api/v3/enrollment/enrol/suggestion',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Authorization: Bearer ' . $abdmToken,
                'Transaction_Id: ' . $enrolTxnId,
                'REQUEST-ID: ' . sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000, mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff)),
                'TIMESTAMP: ' . gmdate('Y-m-d\TH:i:s.000\Z'),
            ],
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $raw      = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            throw new \RuntimeException('Suggestion API error (HTTP ' . $httpCode . '): ' . substr((string) $raw, 0, 200));
        }
        $decoded = json_decode((string) $raw, true);
        // Returns: { "abhaAddressList": ["dev.singh@sbx", ...] }
        $list = $decoded['abhaAddressList'] ?? $decoded['suggestions'] ?? $decoded['data'] ?? [];
        return is_array($list) ? array_values(array_filter($list, 'is_string')) : [];
    }

    private function callAbdmLoginRequestOtp(string $method, string $loginId): array
    {
        $abdmToken  = $this->fetchAbdmTokenForAdmin();
        $publicKey  = $this->fetchAbdmPublicKey($abdmToken);
        $encLoginId = $this->encryptForAbdm($loginId, $publicKey);
        $requestId  = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000, mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff));
        $timestamp  = gmdate('Y-m-d\TH:i:s.000\Z');

        switch ($method) {
            case 'abha-aadhaar':
                $body = ['scope' => ['abha-login', 'aadhaar-verify'], 'loginHint' => 'abha-number', 'loginId' => $encLoginId, 'otpSystem' => 'aadhaar'];
                break;
            case 'mobile':
                $body = ['scope' => ['abha-login', 'mobile-verify'], 'loginHint' => 'mobile', 'loginId' => $encLoginId, 'otpSystem' => 'abdm'];
                break;
            default: // 'abha-abdm'
                $body = ['scope' => ['abha-login', 'mobile-verify'], 'loginHint' => 'abha-number', 'loginId' => $encLoginId, 'otpSystem' => 'abdm'];
        }

        return $this->abdmPost('https://abhasbx.abdm.gov.in/abha/api/v3/profile/login/request/otp', $body, $abdmToken, $requestId, $timestamp);
    }

    private function callAbdmLoginVerifyOtp(string $method, string $txnId, string $otp): array
    {
        $abdmToken = $this->fetchAbdmTokenForAdmin();
        $publicKey = $this->fetchAbdmPublicKey($abdmToken);
        $encOtp    = $this->encryptForAbdm($otp, $publicKey);
        $scope     = ($method === 'abha-aadhaar') ? ['abha-login', 'aadhaar-verify'] : ['abha-login', 'mobile-verify'];

        $body = [
            'scope'    => $scope,
            'authData' => ['authMethods' => ['otp'], 'otp' => ['txnId' => $txnId, 'otpValue' => $encOtp]],
        ];

        return $this->abdmPost(
            'https://abhasbx.abdm.gov.in/abha/api/v3/profile/login/verify',
            $body, $abdmToken,
            sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000, mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff)),
            gmdate('Y-m-d\TH:i:s.000\Z')
        );
    }

    private function callAbdmLoginVerifyUser(string $txnId, string $abhaNumber): array
    {
        $abdmToken = $this->fetchAbdmTokenForAdmin();
        $body      = ['ABHANumber' => $abhaNumber, 'txnId' => $txnId];

        return $this->abdmPost(
            'https://abhasbx.abdm.gov.in/abha/api/v3/profile/login/verify/user',
            $body, $abdmToken,
            sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000, mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff)),
            gmdate('Y-m-d\TH:i:s.000\Z')
        );
    }

    private function fetchAbdmProfileWithXToken(string $xToken): array
    {
        $abdmToken = $this->fetchAbdmTokenForAdmin();
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => 'https://abhasbx.abdm.gov.in/abha/api/v3/profile/account',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Authorization: Bearer ' . $abdmToken,
                'X-Token: Bearer ' . $xToken,
                'REQUEST-ID: ' . sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000, mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff)),
                'TIMESTAMP: ' . gmdate('Y-m-d\TH:i:s.000\Z'),
            ],
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $raw      = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr !== '') {
            throw new \RuntimeException('ABHA profile fetch failed: ' . $curlErr);
        }
        $decoded = json_decode((string) $raw, true);
        if ($httpCode >= 400) {
            $msg = is_array($decoded) ? ($decoded['message'] ?? 'Error ' . $httpCode) : 'HTTP ' . $httpCode;
            throw new \RuntimeException(is_string($msg) ? $msg : json_encode($msg));
        }
        return ['statusCode' => $httpCode, 'decoded' => is_array($decoded) ? $decoded : ['raw' => (string) $raw]];
    }

    /**
     * Shared CURL POST helper for ABDM v3 API calls.
     */
    private function abdmPost(string $url, array $body, string $abdmToken, string $requestId, string $timestamp): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($body),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $abdmToken,
                'REQUEST-ID: ' . $requestId,
                'TIMESTAMP: ' . $timestamp,
            ],
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $raw      = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr !== '') {
            throw new \RuntimeException('ABDM connection failed: ' . $curlErr);
        }
        $decoded = json_decode((string) $raw, true);
        if ($httpCode >= 400) {
            $msg = is_array($decoded)
                ? ($decoded['message'] ?? (is_array($decoded['error'] ?? null) ? ($decoded['error']['message'] ?? 'ABDM error') : ($decoded['error'] ?? 'ABDM API error')))
                : 'ABDM API error (HTTP ' . $httpCode . ')';
            throw new \RuntimeException(is_string($msg) ? $msg : json_encode($msg));
        }
        return ['statusCode' => $httpCode, 'body' => (string) $raw, 'decoded' => is_array($decoded) ? $decoded : ['raw' => (string) $raw]];
    }

    private function callGatewayEndpoint(string $method, string $path, array $payload, string $token): array
    {
        // In test mode skip all external calls and return a mock response.
        if ((bool) config('AbdmGateway')->testMode) {
            return $this->mockGatewayResponse($path, $payload);
        }

        // Live mode: call ABDM v3 API directly (bypasses the internal HTTP proxy
        // which times out because the server cannot make HTTPS self-connections).
        return $this->callAbdmV3Direct($path, $payload);
    }

    /**
     * Call the ABDM v3 API directly, transforming our internal path/payload into
     * the correct ABDM v3 request format including RSA-OAEP encryption.
     */
    private function callAbdmV3Direct(string $internalPath, array $payload): array
    {
        $abdmToken = $this->fetchAbdmTokenForAdmin();
        $baseUrl   = 'https://abhasbx.abdm.gov.in';
        $requestId = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        $timestamp = gmdate('Y-m-d\TH:i:s.000\Z');

        if (str_contains($internalPath, 'generate-otp')) {
            $isAadhaar = !str_contains($internalPath, 'mobile');
            $publicKey = $this->fetchAbdmPublicKey($abdmToken);
            $loginIdRaw = $isAadhaar ? ($payload['aadhaar'] ?? '') : ($payload['mobile'] ?? '');
            $body = [
                'txnId'     => '',
                'scope'     => $isAadhaar ? ['abha-enrol'] : ['abha-enrol', 'mobile-verify'],
                'loginHint' => $isAadhaar ? 'aadhaar' : 'mobile',
                'loginId'   => $this->encryptForAbdm($loginIdRaw, $publicKey),
                'otpSystem' => $isAadhaar ? 'aadhaar' : 'abdm',
            ];
            $abdmUrl = $baseUrl . '/abha/api/v3/enrollment/request/otp';

        } elseif (str_contains($internalPath, 'verify-otp')) {
            $isAadhaar = !str_contains($internalPath, 'mobile');
            $publicKey = $this->fetchAbdmPublicKey($abdmToken);
            $encOtp    = $this->encryptForAbdm((string) ($payload['otp'] ?? ''), $publicKey);

            if ($isAadhaar) {
                $body = [
                    'authData' => [
                        'authMethods' => ['otp'],
                        'otp' => [
                            'txnId'    => (string) ($payload['txnId'] ?? ''),
                            'otpValue' => $encOtp,
                            'mobile'   => (string) ($payload['mobile'] ?? ''),
                        ],
                    ],
                    'consent' => ['code' => 'abha-enrollment', 'version' => '1.4'],
                ];
                $abdmUrl = $baseUrl . '/abha/api/v3/enrollment/enrol/byAadhaar';
            } else {
                $body = [
                    'authData' => [
                        'authMethods' => ['otp'],
                        'otp' => [
                            'txnId'    => (string) ($payload['txnId'] ?? ''),
                            'otpValue' => $encOtp,
                        ],
                    ],
                    'consent' => ['code' => 'abha-enrollment', 'version' => '1.4'],
                ];
                $abdmUrl = $baseUrl . '/abha/api/v3/enrollment/enrol/byMobile';
            }
        } elseif (str_contains($internalPath, 'abha/validate')) {
            // ABHA number search/validate — POST /profile/login/search
            $abhaNumber = (string) ($payload['abha_id'] ?? $payload['abha_number'] ?? '');
            // Normalise: strip hyphens/spaces so both 91-5101-6530-5101 and 91510165305101 are accepted
            $abhaNumber = preg_replace('/[\s\-]/', '', $abhaNumber);
            // Re-format as XX-XXXX-XXXX-XXXX if 14 digits
            if (strlen($abhaNumber) === 14 && ctype_digit($abhaNumber)) {
                $abhaNumber = substr($abhaNumber, 0, 2) . '-' . substr($abhaNumber, 2, 4) . '-' . substr($abhaNumber, 6, 4) . '-' . substr($abhaNumber, 10, 4);
            }
            $body    = ['ABHANumber' => $abhaNumber];
            $abdmUrl = $baseUrl . '/abha/api/v3/profile/login/search';
        } else {
            throw new \RuntimeException('Unknown internal path for ABDM direct call: ' . $internalPath);
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $abdmUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($body),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $abdmToken,
                'REQUEST-ID: ' . $requestId,
                'TIMESTAMP: ' . $timestamp,
            ],
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $raw      = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr !== '') {
            throw new \RuntimeException('ABDM API connection failed: ' . $curlErr);
        }

        $decoded = json_decode((string) $raw, true);

        if ($httpCode >= 400) {
            $msg = is_array($decoded)
                ? ($decoded['message'] ?? (is_array($decoded['error'] ?? null) ? ($decoded['error']['message'] ?? 'ABDM error') : ($decoded['error'] ?? 'ABDM API error')))
                : 'ABDM API error (HTTP ' . $httpCode . ')';
            throw new \RuntimeException(is_string($msg) ? $msg : json_encode($msg));
        }

        return [
            'statusCode' => $httpCode,
            'body'       => (string) $raw,
            'decoded'    => is_array($decoded) ? $decoded : ['raw' => (string) $raw],
        ];
    }

    /**
     * Fetch an ABDM access token using client credentials (with short-lived cache).
     */
    private function fetchAbdmTokenForAdmin(): string
    {
        $cache  = service('cache');
        $cached = $cache->get('abdm_access_token_admin');
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $cfg          = config('AbdmGateway');
        $clientId     = (string) ($cfg->abdmClientId ?: env('ABDM_CLIENT_ID', ''));
        $clientSecret = (string) ($cfg->abdmClientSecret ?: env('ABDM_CLIENT_SECRET', ''));

        if ($clientId === '' || $clientSecret === '') {
            throw new \RuntimeException('ABDM client credentials not configured (ABDM_CLIENT_ID / ABDM_CLIENT_SECRET).');
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => 'https://dev.abdm.gov.in/api/hiecm/gateway/v3/sessions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode([
                'clientId'     => $clientId,
                'clientSecret' => $clientSecret,
                'grantType'    => 'client_credentials',
            ]),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'REQUEST-ID: ' . sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000, mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff)),
                'TIMESTAMP: ' . gmdate('Y-m-d\TH:i:s.000\Z'),
                'X-CM-ID: sbx',
            ],
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $raw      = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr !== '') {
            throw new \RuntimeException('ABDM session API connection failed: ' . $curlErr);
        }

        $data = json_decode((string) $raw, true);

        if ($httpCode !== 200 || !is_array($data) || empty($data['accessToken'])) {
            $msg = is_array($data) ? ($data['message'] ?? $data['error'] ?? 'Auth failed') : ('HTTP ' . $httpCode);
            throw new \RuntimeException('ABDM token fetch failed: ' . (is_string($msg) ? $msg : json_encode($msg)));
        }

        $token = (string) $data['accessToken'];
        $ttl   = max(60, (int) ($data['expiresIn'] ?? 1800) - 60);
        $cache->save('abdm_access_token_admin', $token, $ttl);

        return $token;
    }

    /**
     * Fetch the ABDM RSA public key from the certificate API (cached 24 h).
     */
    private function fetchAbdmPublicKey(string $abdmToken): string
    {
        // Highest priority: key stored in .env (updated monthly by admin)
        $envKey = (string) env('ABDM_PUBLIC_KEY', '');
        if ($envKey !== '') {
            if (strpos($envKey, '-----BEGIN') === false) {
                $envKey = "-----BEGIN PUBLIC KEY-----\n" . chunk_split(trim($envKey), 64, "\n") . "-----END PUBLIC KEY-----\n";
            }
            return $envKey;
        }

        $cache  = service('cache');
        $cached = $cache->get('abdm_public_key_v3');
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => 'https://abhasbx.abdm.gov.in/abha/api/v3/profile/public/certificate',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Authorization: Bearer ' . $abdmToken,
                'REQUEST-ID: ' . sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000, mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff)),
                'TIMESTAMP: ' . gmdate('Y-m-d\TH:i:s.000\Z'),
            ],
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $raw      = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr !== '' || $httpCode !== 200) {
            throw new \RuntimeException('Failed to fetch ABDM public certificate (HTTP ' . $httpCode . ')' . ($curlErr !== '' ? ': ' . $curlErr : '.'));
        }

        $data = json_decode((string) $raw, true);

        if (is_array($data)) {
            $rawKey = (string) ($data['publicKey'] ?? $data['Certificate'] ?? $data['certificate'] ?? '');
        } else {
            $rawKey = trim((string) $raw);
        }

        if ($rawKey === '') {
            throw new \RuntimeException('ABDM cert API returned empty key. Response: ' . substr((string) $raw, 0, 200));
        }

        // Normalise: wrap bare base64 in PEM headers if needed
        if (strpos($rawKey, '-----BEGIN') === false) {
            $rawKey = "-----BEGIN PUBLIC KEY-----\n" . chunk_split(trim($rawKey), 64, "\n") . "-----END PUBLIC KEY-----\n";
        }

        $cache->save('abdm_public_key_v3', $rawKey, 86400);

        return $rawKey;
    }

    /**
     * RSA-OAEP (SHA-1 / MGF1) encrypt a plain-text value with the ABDM public key.
     */
    private function encryptForAbdm(string $plainText, string $publicKeyPem): string
    {
        $key = openssl_get_publickey($publicKeyPem);
        if ($key === false) {
            throw new \RuntimeException('Invalid ABDM public key: ' . openssl_error_string());
        }
        $encrypted = '';
        if (!openssl_public_encrypt($plainText, $encrypted, $key, OPENSSL_PKCS1_OAEP_PADDING)) {
            throw new \RuntimeException('ABDM RSA encryption failed: ' . openssl_error_string());
        }
        return base64_encode($encrypted);
    }

    private function mockGatewayResponse(string $path, array $payload): array
    {
        $requestId = 'MOCK-' . date('YmdHis') . '-' . substr(md5(uniqid('', true)), 0, 8);

        if (str_contains($path, 'generate-otp')) {
            $data = [
                'ok'         => 1,
                'mode'       => 'test',
                'request_id' => $requestId,
                'txnId'      => 'TXN-MOCK-' . date('YmdHis'),
                'data'       => ['message' => 'Mock OTP dispatched (test mode). Enter any 6-digit OTP to continue.'],
            ];
        } elseif (str_contains($path, 'verify-otp')) {
            $data = [
                'ok'         => 1,
                'mode'       => 'test',
                'request_id' => $requestId,
                'txnId'      => 'TXN-MOCK-VERIFIED-' . date('YmdHis'),
                'data'       => [
                    'abhaNumber'  => '91-' . rand(1000, 9999) . '-' . rand(1000, 9999) . '-' . rand(1000, 9999),
                    'name'        => 'Test Patient (Mock)',
                    'gender'      => 'M',
                    'yearOfBirth' => '1990',
                    'mobile'      => $payload['mobile'] ?? '9999999999',
                    'message'     => 'Mock OTP verified in test mode.',
                ],
            ];
        } else {
            $data = [
                'ok'         => 1,
                'mode'       => 'test',
                'request_id' => $requestId,
                'data'       => ['message' => 'Mock response in test mode'],
            ];
        }

        return [
            'statusCode' => 200,
            'body'       => json_encode($data),
            'decoded'    => $data,
        ];
    }

    private function saveAbhaProfile(array $payload, string $fallbackAbhaId, string $requestId): int
    {
        // Handle both ABDM v3 (ABHANumber) and older formats
        $abhaNumber = trim((string) ($payload['ABHANumber'] ?? $payload['abhaNumber'] ?? $payload['abha_id'] ?? $fallbackAbhaId));
        if ($abhaNumber === '') {
            throw new \RuntimeException('ABHA number missing in validation response.');
        }

        // Assemble full name from v3 parts or fall back to legacy fields
        $firstName  = trim((string) ($payload['firstName']  ?? $payload['first_name']  ?? ''));
        $middleName = trim((string) ($payload['middleName'] ?? $payload['middle_name'] ?? ''));
        $lastName   = trim((string) ($payload['lastName']   ?? $payload['last_name']   ?? ''));
        $fullName   = trim(implode(' ', array_filter([$firstName, $middleName, $lastName])));
        if ($fullName === '') {
            $fullName = trim((string) ($payload['name'] ?? $payload['fullName'] ?? $payload['full_name'] ?? ''));
        }

        // Primary PHR address (v3 returns phrAddress as array)
        $phrRaw    = $payload['phrAddress'] ?? $payload['abhaAddress'] ?? $payload['abha_address'] ?? null;
        $phrAddr   = is_array($phrRaw) ? ($phrRaw[0] ?? '') : (string) ($phrRaw ?? '');

        // DOB / year
        $dob = trim((string) ($payload['dob'] ?? $payload['dateOfBirth'] ?? $payload['date_of_birth'] ?? ''));
        $yob = '';
        if ($dob !== '') {
            // Format can be DD-MM-YYYY (ABDM v3) or YYYY-MM-DD
            if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $dob, $m)) {
                $yob = $m[3];
            } elseif (preg_match('/^(\d{4})/', $dob, $m)) {
                $yob = $m[1];
            }
        }
        if ($yob === '') {
            $yob = trim((string) ($payload['yearOfBirth'] ?? $payload['year_of_birth'] ?? ''));
        }

        $row = [
            'hospital_id'     => session('hospital_id') ?: null,
            'user_id'         => session('user_id') ?: null,
            'abha_number'     => $abhaNumber,
            'abha_address'    => $phrAddr,
            'phr_address'     => $phrAddr,
            'full_name'       => $fullName,
            'first_name'      => $firstName,
            'middle_name'     => $middleName,
            'last_name'       => $lastName,
            'gender'          => strtoupper(trim((string) ($payload['gender'] ?? ''))),
            'mobile'          => trim((string) ($payload['mobile'] ?? $payload['mobileNumber'] ?? '')),
            'email'           => trim((string) ($payload['email'] ?? '')) ?: null,
            'mobile_verified' => !empty($payload['mobileVerified']) ? 1 : 0,
            'date_of_birth'   => $dob,
            'year_of_birth'   => $yob,
            'address'         => trim((string) ($payload['address'] ?? $payload['preferredAddress'] ?? '')) ?: null,
            'pin_code'        => trim((string) ($payload['pinCode'] ?? $payload['pin_code'] ?? '')) ?: null,
            'state_code'      => trim((string) ($payload['stateCode'] ?? $payload['state_code'] ?? '')) ?: null,
            'state_name'      => trim((string) ($payload['stateName'] ?? $payload['state_name'] ?? '')) ?: null,
            'district_code'   => trim((string) ($payload['districtCode'] ?? $payload['district_code'] ?? '')) ?: null,
            'district_name'   => trim((string) ($payload['districtName'] ?? $payload['district_name'] ?? '')) ?: null,
            'abha_type'       => trim((string) ($payload['abhaType'] ?? $payload['abha_type'] ?? '')) ?: null,
            'abha_status'     => trim((string) ($payload['abhaStatus'] ?? $payload['abha_status'] ?? 'ACTIVE')),
            'status'          => 'verified',
            'last_request_id' => $requestId,
            'last_verified_at'=> date('Y-m-d H:i:s'),
            'profile_json'    => $this->encodeJson($payload),
        ];

        $existing = $this->abhaProfileModel->where('abha_number', $abhaNumber)->first();
        if ($existing !== null) {
            $this->abhaProfileModel->update((int) $existing->id, $row);
            return (int) $existing->id;
        }

        $this->abhaProfileModel->insert($row);
        return (int) $this->abhaProfileModel->getInsertID();
    }

    private function storeM1Log(string $eventType, string $endpoint, int $statusCode, array $requestPayload, array $responsePayload): void
    {
        $table = 'abdm_test_submission_logs';
        $db = $this->testLogModel->db;

        try {
            $fields = array_map('strtolower', $db->getFieldNames($table));
            if ($fields === []) {
                return;
            }

            $requestId = null;
            if (isset($responsePayload['response']) && is_array($responsePayload['response'])) {
                $requestId = $responsePayload['response']['request_id'] ?? null;
            }
            $requestId ??= $responsePayload['request_id'] ?? null;

            $requestJson = $this->encodeJson(array_merge(['endpoint' => $endpoint], $requestPayload));
            $responseJson = $this->encodeJson($responsePayload);

            $row = [];

            if (in_array('request_id', $fields, true) && is_string($requestId) && $requestId !== '') {
                $row['request_id'] = $requestId;
            }
            if (in_array('hospital_id', $fields, true)) {
                $row['hospital_id'] = session('hospital_id') ?: null;
            }
            if (in_array('user_id', $fields, true)) {
                $row['user_id'] = session('user_id') ?: null;
            }

            // Current schema mapping.
            if (in_array('event_type', $fields, true)) {
                $row['event_type'] = $eventType;
            }
            if (in_array('endpoint', $fields, true)) {
                $row['endpoint'] = $endpoint;
            }
            if (in_array('http_status', $fields, true)) {
                $row['http_status'] = $statusCode;
            }
            if (in_array('request_payload', $fields, true)) {
                $row['request_payload'] = $requestJson;
            }
            if (in_array('response_payload', $fields, true)) {
                $row['response_payload'] = $responseJson;
            }

            // Legacy schema mapping kept for backward compatibility.
            if (in_array('test_type', $fields, true)) {
                $row['test_type'] = $eventType;
            }
            if (in_array('test_data', $fields, true)) {
                $row['test_data'] = $requestJson;
            }
            if (in_array('status', $fields, true)) {
                $row['status'] = (string) $statusCode;
            }
            if (in_array('response', $fields, true)) {
                $row['response'] = $responseJson;
            }

            if (in_array('created_at', $fields, true)) {
                $row['created_at'] = date('Y-m-d H:i:s');
            }

            if ($row !== []) {
                $db->table($table)->insert($row);
            }
        } catch (\Throwable $e) {
            log_message('error', 'Failed to write M1 submission log: ' . $e->getMessage());
        }
    }

    private function resolveGatewayToken(): string
    {
        $cfgToken = trim((string) (config('AbdmGateway')->bearerToken ?? ''));
        if ($cfgToken !== '') {
            return $cfgToken;
        }

        $candidates = [
            trim((string) env('GATEWAY_BEARER_TOKEN', '')),
            trim((string) env('ABDM_BRIDGE_TOKEN', '')),
            trim((string) env('BRIDGE_SYNC_TOKEN', '')),
        ];

        foreach ($candidates as $token) {
            if ($token !== '') {
                return $token;
            }
        }

        return '';
    }

    private function encodeJson(array $payload): string
    {
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
        return $json === false ? '{}' : $json;
    }

    private function encodePretty(array $payload): string
    {
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        return $json === false ? '{}' : $json;
    }

    private function maskToken(string $token): string
    {
        if ($token === '') {
            return '';
        }

        if (strlen($token) <= 12) {
            return substr($token, 0, 2) . '...' . substr($token, -2);
        }

        return substr($token, 0, 8) . '...' . substr($token, -4);
    }

    private function encryptCredential(string $data): string
    {
        $key = getenv('ENCRYPTION_KEY') ?: bin2hex(random_bytes(32));
        return base64_encode($data . ':' . hash_hmac('sha256', $data, $key));
    }

    private function decryptCredential(string $data): string
    {
        $parts = explode(':', base64_decode($data));
        return $parts[0] ?? '';
    }

    // ─── Support Tickets ─────────────────────────────────────────────────────

    public function supportTickets()
    {
        $ticketModel = new SupportTicket();
        $status   = $this->request->getGet('status') ?? '';
        $priority = $this->request->getGet('priority') ?? '';

        $q = $ticketModel->withHospital()->orderBy('t.created_at', 'DESC');
        if ($status   !== '') $q->where('t.status', $status);
        if ($priority !== '') $q->where('t.priority', $priority);
        $tickets = $q->get(100)->getResultObject();

        // Unread count per status
        $counts = [
            'open'        => $ticketModel->where('status','open')->countAllResults(false),
            'in_progress' => $ticketModel->where('status','in_progress')->countAllResults(false),
            'resolved'    => $ticketModel->where('status','resolved')->countAllResults(false),
            'closed'      => $ticketModel->where('status','closed')->countAllResults(false),
        ];

        return view('admin/support_tickets', [
            'tickets'       => $tickets,
            'counts'        => $counts,
            'staleCount'    => $ticketModel->countStale(),
            'filterStatus'  => $status,
            'filterPriority'=> $priority,
        ]);
    }

    public function supportTicketView(int $id)
    {
        $ticketModel  = new SupportTicket();
        $messageModel = new SupportMessage();
        $attachModel  = new SupportAttachment();

        $ticket = $ticketModel->withHospital()
            ->where('t.id', $id)->get(1)->getRowObject();

        if ($ticket === null) {
            return redirect()->to('/admin/support')->with('error', 'Ticket not found.');
        }

        $messages    = $messageModel->forTicket($id);
        $attachments = [];
        foreach ($messages as $msg) {
            $attachments[(int)$msg->id] = $attachModel->forMessage((int)$msg->id);
        }

        return view('admin/support_ticket_view', [
            'ticket'      => $ticket,
            'messages'    => $messages,
            'attachments' => $attachments,
        ]);
    }

    public function supportTicketReplyPost(int $id)
    {
        $ticketModel  = new SupportTicket();
        $messageModel = new SupportMessage();
        $attachModel  = new SupportAttachment();

        $ticket = $ticketModel->find($id);
        if ($ticket === null) {
            return redirect()->to('/admin/support')->with('error', 'Ticket not found.');
        }

        $message   = $this->sanitizeSupportText((string) $this->request->getPost('message'));
        $newStatus = trim((string) $this->request->getPost('status'));
        $files     = $this->request->getFiles();
        $uploads   = $files['attachments'] ?? [];
        if (!is_array($uploads)) $uploads = [$uploads];
        $hasFile = false;
        foreach ($uploads as $f) {
            if ($f !== null && $f->isValid() && !$f->hasMoved()) { $hasFile = true; break; }
        }

        if (strip_tags($message) === '' && $newStatus === '' && !$hasFile) {
            return redirect()->to('/admin/support/' . $id)->with('error', 'Nothing to update.');
        }

        $now   = date('Y-m-d H:i:s');
        $aname = (string) session()->get('username');
        $aid   = (int) session()->get('user_id');

        $msgId = null;
        if (strip_tags($message) !== '' || $hasFile) {
            $msgId = $messageModel->insert([
                'ticket_id'   => $id,
                'message'     => $message,
                'sender_type' => 'admin',
                'sender_id'   => $aid,
                'sender_name' => $aname,
                'created_at'  => $now,
            ], true);
            $this->processSupportAttachments($id, (int)$msgId, 'admin', $attachModel);
        }

        $update = ['last_reply_at' => $now, 'last_reply_by' => 'admin'];
        if ($msgId !== null) {
            $update['message_count'] = (int)$ticket->message_count + 1;
        }
        $validStatuses = ['open','in_progress','resolved','closed'];
        if (in_array($newStatus, $validStatuses, true)) {
            $update['status'] = $newStatus;
        }
        $ticketModel->update($id, $update);

        return redirect()->to('/admin/support/' . $id)->with('message', 'Ticket updated.');
    }

    public function supportAttachmentDownload(int $attachId)
    {
        $attachModel = new SupportAttachment();
        $attach      = $attachModel->find($attachId);
        if ($attach === null) {
            return redirect()->back()->with('error', 'Attachment not found.');
        }
        $path = SupportAttachment::storagePath($attach->stored_name);
        if (!is_file($path)) {
            return redirect()->back()->with('error', 'File not found on server.');
        }
        return $this->response
            ->setHeader('Content-Type', $attach->mime_type ?? 'application/octet-stream')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $attach->original_name . '"')
            ->setBody(file_get_contents($path));
    }

    public function supportTicketClose(int $id)
    {
        $ticketModel = new SupportTicket();
        $ticket      = $ticketModel->find($id);
        if ($ticket === null) {
            return redirect()->to('/admin/support')->with('error', 'Ticket not found.');
        }
        $ticketModel->update($id, ['status' => 'closed']);
        return redirect()->to('/admin/support/' . $id)->with('message', 'Ticket closed.');
    }

    public function supportCloseStale()
    {
        $ticketModel = new SupportTicket();
        $ticketModel->db->query(
            "UPDATE abdm_support_tickets
             SET status = 'closed', updated_at = NOW()
             WHERE status NOT IN ('closed','resolved')
               AND (last_reply_at IS NULL OR last_reply_at < DATE_SUB(NOW(), INTERVAL 7 DAY))"
        );
        $affected = $ticketModel->db->affectedRows();
        return redirect()->to('/admin/support')->with('message', $affected . ' stale ticket(s) closed.');
    }

    private function processSupportAttachments(int $ticketId, int $msgId, string $uploaderType, SupportAttachment $attachModel): void
    {
        $files   = $this->request->getFiles();
        $uploads = $files['attachments'] ?? [];
        if (!is_array($uploads)) $uploads = [$uploads];

        $allowed = ['pdf','doc','docx','xls','xlsx','jpg','jpeg','png','gif','txt','zip'];
        $maxSize = 5 * 1024 * 1024;

        foreach ($uploads as $file) {
            if ($file === null || !$file->isValid() || $file->hasMoved()) continue;
            if ($file->getSize() > $maxSize) continue;
            $ext = strtolower($file->getClientExtension());
            if (!in_array($ext, $allowed, true)) continue;

            $storedName = bin2hex(random_bytes(16)) . '.' . $ext;
            $dir = WRITEPATH . 'uploads/support/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $file->move($dir, $storedName);

            $attachModel->insert([
                'ticket_id'     => $ticketId,
                'message_id'    => $msgId,
                'original_name' => $file->getClientName(),
                'stored_name'   => $storedName,
                'mime_type'     => $file->getClientMimeType(),
                'file_size'     => $file->getSize(),
                'uploaded_by'   => $uploaderType,
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function sanitizeSupportText(string $html): string
    {
        $allowed = '<p><br><b><strong><i><em><u><s><strike><ol><ul><li><h1><h2><h3><blockquote><pre><code><span><a>';
        return strip_tags($html, $allowed);
    }
}

