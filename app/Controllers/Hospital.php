<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\AbdmHospital;
use App\Models\AbdmAbhaProfile;
use App\Models\AbdmTokenQueue;
use App\Models\HmsCredential;
use App\Models\SupportTicket;
use App\Models\SupportMessage;
use App\Models\SupportAttachment;

class Hospital extends BaseController
{
    // ─── Auth guard ───────────────────────────────────────────────────────────

    private function guardHospital(): bool
    {
        return (bool) session()->get('is_logged_in') && session()->get('portal') === 'hospital';
    }

    private function hospitalId(): int
    {
        return (int) session()->get('hospital_id');
    }

    private function redirectUnauth()
    {
        return redirect()->to('/')->with('error', 'Please login to access the hospital portal.');
    }

    // ─── Dashboard ────────────────────────────────────────────────────────────

    public function dashboard()
    {
        if (!$this->guardHospital()) return $this->redirectUnauth();

        $hid = $this->hospitalId();
        $hospitalModel = new AbdmHospital();
        $hospital      = $hospitalModel->find($hid);

        $profileModel  = new AbdmAbhaProfile();
        $tokenModel    = new AbdmTokenQueue();

        $totalPatients  = $profileModel->where('hospital_id', $hid)->countAllResults();
        $todayTokens    = $tokenModel->where('hospital_id', $hid)->where('token_date', date('Y-m-d'))->countAllResults();
        $monthTokens    = $tokenModel->where('hospital_id', $hid)
            ->where("DATE_FORMAT(token_date,'%Y-%m')", date('Y-m'))->countAllResults();
        $recentPatients = $profileModel->where('hospital_id', $hid)
            ->orderBy('last_verified_at', 'DESC')->limit(5)->findAll();

        return view('hospital/dashboard', [
            'hospital'       => $hospital,
            'totalPatients'  => $totalPatients,
            'todayTokens'    => $todayTokens,
            'monthTokens'    => $monthTokens,
            'recentPatients' => $recentPatients,
        ]);
    }

    // ─── ABHA Tools ───────────────────────────────────────────────────────────

    public function abhaTools()
    {
        if (!$this->guardHospital()) return $this->redirectUnauth();

        $hid          = $this->hospitalId();
        $profileModel = new AbdmAbhaProfile();

        return view('hospital/abha_tools', [
            'message'        => session()->getFlashdata('message'),
            'error'          => session()->getFlashdata('error'),
            'abhaUser'       => session()->getFlashdata('abhaUser'),
            'otpStep'        => (int)    (session()->getFlashdata('otp_step') ?: 1),
            'txnId'          => (string) (session()->getFlashdata('otp_txn_id') ?: ''),
            'otpType'        => (string) (session()->getFlashdata('otp_type') ?: 'aadhaar'),
            'otpInput'       => (string) (session()->getFlashdata('otp_input') ?: ''),
            'recentProfiles' => $profileModel->where('hospital_id', $hid)
                                ->orderBy('last_verified_at', 'DESC')->limit(10)->findAll(),
        ]);
    }

    public function abhaValidatePost()
    {
        if (!$this->guardHospital()) return $this->redirectUnauth();

        $abhaId = trim((string) $this->request->getPost('abha_id'));
        if ($abhaId === '') {
            return redirect()->to('/portal/abha-tools')->with('error', 'ABHA number is required.');
        }

        try {
            $response = $this->callPortalGatewayEndpoint('api/v3/abha/validate', ['abha_id' => $abhaId]);
            $decoded  = $response['decoded'];
            $body     = is_array($decoded['data'] ?? null) ? $decoded['data'] : $decoded;
            $this->saveAbhaProfilePortal($body, $abhaId, (string) ($decoded['request_id'] ?? ''));

            return redirect()->to('/portal/abha-tools')
                ->with('message', 'ABHA validated successfully.')
                ->with('abhaUser', $body);
        } catch (\Throwable $e) {
            return redirect()->to('/portal/abha-tools')->with('error', 'Validation failed: ' . $e->getMessage());
        }
    }

    public function abhaOtpGeneratePost()
    {
        if (!$this->guardHospital()) return $this->redirectUnauth();

        $otpType = $this->request->getPost('otp_type') === 'mobile' ? 'mobile' : 'aadhaar';
        $input   = trim((string) $this->request->getPost('otp_input'));

        if ($input === '') {
            return redirect()->to('/portal/abha-tools')
                ->with('error', 'Please enter ' . ($otpType === 'mobile' ? 'mobile number.' : 'Aadhaar number.'))
                ->with('otp_type', $otpType)->with('otp_step', 1);
        }

        $path  = $otpType === 'mobile' ? 'api/v3/abha/mobile/generate-otp' : 'api/v3/abha/aadhaar/generate-otp';
        $field = $otpType === 'mobile' ? 'mobile' : 'aadhaar';

        try {
            $response = $this->callPortalGatewayEndpoint($path, [$field => $input]);
            $decoded  = $response['decoded'];
            $txnId    = (string) ($decoded['txnId'] ?? $decoded['data']['txnId'] ?? $decoded['transaction_id'] ?? '');

            return redirect()->to('/portal/abha-tools')
                ->with('message', 'OTP sent. Enter the OTP you received.')
                ->with('otp_step', 2)->with('otp_txn_id', $txnId)
                ->with('otp_type', $otpType)->with('otp_input', $input);
        } catch (\Throwable $e) {
            return redirect()->to('/portal/abha-tools')
                ->with('error', 'Failed to generate OTP: ' . $e->getMessage())
                ->with('otp_type', $otpType)->with('otp_step', 1);
        }
    }

    public function abhaOtpVerifyPost()
    {
        if (!$this->guardHospital()) return $this->redirectUnauth();

        $otpType  = $this->request->getPost('otp_type') === 'mobile' ? 'mobile' : 'aadhaar';
        $txnId    = trim((string) $this->request->getPost('txn_id'));
        $otp      = trim((string) $this->request->getPost('otp'));
        $otpInput = trim((string) $this->request->getPost('otp_input'));
        $mobile   = trim((string) $this->request->getPost('mobile'));

        if ($otp === '') {
            return redirect()->to('/portal/abha-tools')
                ->with('error', 'OTP is required.')
                ->with('otp_step', 2)->with('otp_txn_id', $txnId)
                ->with('otp_type', $otpType)->with('otp_input', $otpInput);
        }

        $path = $otpType === 'mobile' ? 'api/v3/abha/mobile/verify-otp' : 'api/v3/abha/aadhaar/verify-otp';

        try {
            $response    = $this->callPortalGatewayEndpoint($path, ['txnId' => $txnId, 'otp' => $otp, 'mobile' => $mobile]);
            $decoded     = $response['decoded'];
            $profileData = is_array($decoded['data'] ?? null) ? $decoded['data']
                : (is_array($decoded['ABHAProfile'] ?? null) ? $decoded['ABHAProfile'] : $decoded);
            $abhaNumber  = (string) ($profileData['abhaNumber'] ?? $profileData['ABHANumber'] ?? '');

            if ($abhaNumber !== '' || $profileData !== []) {
                $this->saveAbhaProfilePortal($profileData, $abhaNumber, (string) ($decoded['request_id'] ?? ''));
            }

            return redirect()->to('/portal/abha-tools')
                ->with('message', 'ABHA created/verified successfully.')
                ->with('abhaUser', $profileData);
        } catch (\Throwable $e) {
            return redirect()->to('/portal/abha-tools')
                ->with('error', 'OTP verification failed: ' . $e->getMessage())
                ->with('otp_step', 2)->with('otp_txn_id', $txnId)
                ->with('otp_type', $otpType)->with('otp_input', $otpInput);
        }
    }

    // ─── OPD Queue ────────────────────────────────────────────────────────────

    public function opdQueue()
    {
        if (!$this->guardHospital()) return $this->redirectUnauth();

        $hid  = $this->hospitalId();
        $date = (string) ($this->request->getGet('date') ?: date('Y-m-d'));

        $tokenModel = new AbdmTokenQueue();
        $tokens     = $tokenModel->where('hospital_id', $hid)->where('token_date', $date)
            ->orderBy('token_number', 'ASC')->findAll();

        return view('hospital/opd_queue', [
            'tokens'  => $tokens,
            'date'    => $date,
            'message' => session()->getFlashdata('message'),
            'error'   => session()->getFlashdata('error'),
        ]);
    }

    public function opdQueueCreatePost()
    {
        if (!$this->guardHospital()) return $this->redirectUnauth();

        $hid        = $this->hospitalId();
        $tokenModel = new AbdmTokenQueue();
        $tokenDate  = date('Y-m-d');
        $tokenNumber = $tokenModel->where('hospital_id', $hid)->where('token_date', $tokenDate)->nextTokenNumber();

        $tokenModel->insert([
            'hospital_id'  => $hid,
            'patient_name' => trim((string) $this->request->getPost('patient_name')),
            'phone'        => trim((string) $this->request->getPost('phone')),
            'abha_number'  => trim((string) $this->request->getPost('abha_number')) ?: null,
            'gender'       => trim((string) $this->request->getPost('gender')) ?: null,
            'context'      => trim((string) $this->request->getPost('department')) ?: 'General OPD',
            'token_number' => $tokenNumber,
            'token_date'   => $tokenDate,
            'status'       => 'PENDING',
        ]);

        return redirect()->to('/portal/opd-queue')->with('message', 'Token #' . $tokenNumber . ' created.');
    }

    public function opdQueueUpdateStatusPost()
    {
        if (!$this->guardHospital()) return $this->redirectUnauth();

        $hid    = $this->hospitalId();
        $id     = (int) $this->request->getPost('token_id');
        $status = strtoupper(trim((string) $this->request->getPost('status')));

        if (!in_array($status, ['PENDING', 'CALLED', 'COMPLETED', 'CANCELLED'], true)) {
            return redirect()->to('/portal/opd-queue')->with('error', 'Invalid status.');
        }

        $tokenModel = new AbdmTokenQueue();
        $token = $tokenModel->where('id', $id)->where('hospital_id', $hid)->first();
        if ($token === null) {
            return redirect()->to('/portal/opd-queue')->with('error', 'Token not found.');
        }

        $tokenModel->update($id, ['status' => $status]);
        return redirect()->to('/portal/opd-queue')->with('message', 'Token status updated.');
    }

    // ─── Patients ─────────────────────────────────────────────────────────────

    public function patients()
    {
        if (!$this->guardHospital()) return $this->redirectUnauth();

        $hid    = $this->hospitalId();
        $search = trim((string) ($this->request->getGet('search') ?? ''));

        $profileModel = new AbdmAbhaProfile();
        $builder      = $profileModel->where('hospital_id', $hid);

        if ($search !== '') {
            $builder = $builder->groupStart()
                ->like('abha_number', $search)
                ->orLike('full_name', $search)
                ->orLike('mobile', $search)
                ->groupEnd();
        }

        $patients = $builder->orderBy('last_verified_at', 'DESC')->findAll(100);

        return view('hospital/patients', [
            'patients' => $patients,
            'search'   => $search,
        ]);
    }

    // ─── Reports ──────────────────────────────────────────────────────────────

    public function reports()
    {
        if (!$this->guardHospital()) return $this->redirectUnauth();

        $hid          = $this->hospitalId();
        $profileModel = new AbdmAbhaProfile();
        $tokenModel   = new AbdmTokenQueue();

        $totalProfiles = $profileModel->where('hospital_id', $hid)->countAllResults();
        $todayProfiles = $profileModel->where('hospital_id', $hid)
            ->where('DATE(last_verified_at)', date('Y-m-d'))->countAllResults();
        $monthProfiles = $profileModel->where('hospital_id', $hid)
            ->where("DATE_FORMAT(last_verified_at,'%Y-%m')", date('Y-m'))->countAllResults();

        $totalTokens = $tokenModel->where('hospital_id', $hid)->countAllResults();
        $todayTokens = $tokenModel->where('hospital_id', $hid)->where('token_date', date('Y-m-d'))->countAllResults();
        $monthTokens = $tokenModel->where('hospital_id', $hid)
            ->where("DATE_FORMAT(token_date,'%Y-%m')", date('Y-m'))->countAllResults();

        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-$i days"));
            $trend[] = [
                'date'     => $day,
                'label'    => date('d M', strtotime($day)),
                'profiles' => $profileModel->where('hospital_id', $hid)
                    ->where('DATE(last_verified_at)', $day)->countAllResults(),
                'tokens'   => $tokenModel->where('hospital_id', $hid)
                    ->where('token_date', $day)->countAllResults(),
            ];
        }

        return view('hospital/reports', [
            'totalProfiles' => $totalProfiles,
            'todayProfiles' => $todayProfiles,
            'monthProfiles' => $monthProfiles,
            'totalTokens'   => $totalTokens,
            'todayTokens'   => $todayTokens,
            'monthTokens'   => $monthTokens,
            'trend'         => $trend,
        ]);
    }

    // ─── API Documentation ───────────────────────────────────────────────────

    public function apiDocsPublic()
    {
        return view('hospital/api_docs_public', [
            'base_url' => 'https://abdm-bridge.e-atria.in',
        ]);
    }

    public function apiDocs()
    {
        if (!$this->guardHospital()) return $this->redirectUnauth();

        $hid        = $this->hospitalId();
        $credModel  = new HmsCredential();
        $credential = $credModel->getActiveByHospital($hid);

        $hospitalModel = new AbdmHospital();
        $hospital      = $hospitalModel->find($hid);

        return view('hospital/api_docs', [
            'hospital'    => $hospital,
            'credential'  => $credential,
            'base_url'    => 'https://abdm-bridge.e-atria.in',
        ]);
    }

    // ─── Profile (read-only) ─────────────────────────────────────────────────

    public function profile()
    {
        if (!$this->guardHospital()) return $this->redirectUnauth();

        $hid           = $this->hospitalId();
        $hospitalModel = new AbdmHospital();
        $hospital      = $hospitalModel->find($hid);

        $credModel  = new HmsCredential();
        $credential = $credModel->getActiveByHospital($hid);

        $masked_key   = '';
        $api_endpoint = 'https://abdm-bridge.e-atria.in/api';
        if ($credential !== null && !empty($credential->hms_api_key)) {
            $plain = $this->decryptCredential($credential->hms_api_key);
            if (strlen($plain) >= 12) {
                $masked_key = substr($plain, 0, 6) . str_repeat('*', 16) . substr($plain, -4);
            } else {
                $masked_key = str_repeat('*', strlen($plain));
            }
            if (!empty($credential->hms_api_endpoint)) {
                $api_endpoint = $credential->hms_api_endpoint;
            }
        }

        return view('hospital/profile', [
            'hospital'     => $hospital,
            'credential'   => $credential,
            'masked_key'   => $masked_key,
            'api_endpoint' => $api_endpoint,
        ]);
    }

    public function changePasswordPost()
    {
        if (!$this->guardHospital()) return $this->redirectUnauth();

        $currentPw  = (string) $this->request->getPost('current_password');
        $newPw      = (string) $this->request->getPost('new_password');
        $confirmPw  = (string) $this->request->getPost('confirm_password');

        if ($currentPw === '' || $newPw === '' || $confirmPw === '') {
            return redirect()->to('/portal/profile')->with('pw_error', 'All password fields are required.');
        }
        if (strlen($newPw) < 8) {
            return redirect()->to('/portal/profile')->with('pw_error', 'New password must be at least 8 characters.');
        }
        if ($newPw !== $confirmPw) {
            return redirect()->to('/portal/profile')->with('pw_error', 'New password and confirmation do not match.');
        }

        $uid       = (int) session()->get('user_id');
        $userModel = new \App\Models\AbdmHospitalUser();
        $user      = $userModel->find($uid);

        if ($user === null || !password_verify($currentPw, $user->password_hash)) {
            return redirect()->to('/portal/profile')->with('pw_error', 'Current password is incorrect.');
        }

        $userModel->update($uid, ['password_hash' => password_hash($newPw, PASSWORD_BCRYPT)]);
        return redirect()->to('/portal/profile')->with('pw_success', 'Password changed successfully.');
    }

    // ─── Logout ───────────────────────────────────────────────────────────────

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/')->with('message', 'Logged out successfully.');
    }

    // ─── Private Helpers ─────────────────────────────────────────────────────

    private function decryptCredential(string $data): string
    {
        $parts = explode(':', base64_decode($data));
        return $parts[0] ?? '';
    }

    // ─── Private Helpers ─────────────────────────────────────────────────────

    private function callPortalGatewayEndpoint(string $path, array $payload): array
    {
        if ((bool) config('AbdmGateway')->testMode) {
            return $this->mockPortalResponse($path, $payload);
        }

        $token = (string) env('GATEWAY_BEARER_TOKEN', '');
        if ($token === '') {
            throw new \RuntimeException('Gateway bearer token not configured (GATEWAY_BEARER_TOKEN).');
        }

        $client   = \Config\Services::curlrequest(['baseURI' => base_url()]);
        $response = $client->post($path, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ],
            'json'    => $payload,
            'timeout' => 30,
        ]);

        $decoded = json_decode($response->getBody(), true);
        return [
            'statusCode' => $response->getStatusCode(),
            'decoded'    => is_array($decoded) ? $decoded : [],
        ];
    }

    private function mockPortalResponse(string $path, array $payload): array
    {
        $requestId = 'MOCK-' . date('YmdHis') . '-' . substr(md5(uniqid('', true)), 0, 8);

        if (str_contains($path, 'generate-otp')) {
            $data = [
                'ok' => 1, 'mode' => 'test', 'request_id' => $requestId,
                'txnId' => 'TXN-MOCK-' . date('YmdHis'),
                'data'  => ['message' => 'Mock OTP dispatched (test mode). Enter any 6-digit OTP.'],
            ];
        } elseif (str_contains($path, 'verify-otp')) {
            $data = [
                'ok' => 1, 'mode' => 'test', 'request_id' => $requestId,
                'txnId' => 'TXN-MOCK-VERIFIED-' . date('YmdHis'),
                'data'  => [
                    'abhaNumber'  => '91-' . rand(1000, 9999) . '-' . rand(1000, 9999) . '-' . rand(1000, 9999),
                    'name'        => 'Test Patient (Mock)',
                    'gender'      => 'M',
                    'yearOfBirth' => '1990',
                    'mobile'      => $payload['mobile'] ?? '9999999999',
                    'message'     => 'Mock OTP verified in test mode.',
                ],
            ];
        } elseif (str_contains($path, 'validate')) {
            $data = [
                'ok' => 1, 'mode' => 'test', 'request_id' => $requestId,
                'data' => [
                    'abhaNumber'  => $payload['abha_id'] ?? '91-0000-0000-0000',
                    'name'        => 'Test Patient (Mock)',
                    'gender'      => 'M',
                    'yearOfBirth' => '1990',
                    'mobile'      => '9999999999',
                    'message'     => 'Mock ABHA validated in test mode.',
                ],
            ];
        } else {
            $data = ['ok' => 1, 'mode' => 'test', 'request_id' => $requestId,
                     'data' => ['message' => 'Mock response (test mode)']];
        }

        return ['statusCode' => 200, 'body' => json_encode($data), 'decoded' => $data];
    }

    private function saveAbhaProfilePortal(array $payload, string $fallbackAbhaId, string $requestId): void
    {
        $abhaNumber = trim((string) ($payload['ABHANumber'] ?? $payload['abhaNumber'] ?? $payload['abha_id'] ?? $fallbackAbhaId));
        if ($abhaNumber === '') return;

        $firstName  = trim((string) ($payload['firstName']  ?? $payload['first_name']  ?? ''));
        $middleName = trim((string) ($payload['middleName'] ?? $payload['middle_name'] ?? ''));
        $lastName   = trim((string) ($payload['lastName']   ?? $payload['last_name']   ?? ''));
        $fullName   = trim(implode(' ', array_filter([$firstName, $middleName, $lastName])));
        if ($fullName === '') {
            $fullName = trim((string) ($payload['name'] ?? $payload['fullName'] ?? $payload['full_name'] ?? ''));
        }

        $phrRaw  = $payload['phrAddress'] ?? $payload['abhaAddress'] ?? $payload['abha_address'] ?? null;
        $phrAddr = is_array($phrRaw) ? ($phrRaw[0] ?? '') : (string) ($phrRaw ?? '');

        $dob = trim((string) ($payload['dob'] ?? $payload['dateOfBirth'] ?? $payload['date_of_birth'] ?? ''));
        $yob = '';
        if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $dob, $m)) { $yob = $m[3]; }
        elseif (preg_match('/^(\d{4})/', $dob, $m)) { $yob = $m[1]; }
        if ($yob === '') { $yob = trim((string) ($payload['yearOfBirth'] ?? '')); }

        $row = [
            'hospital_id'      => $this->hospitalId(),
            'user_id'          => (int) session()->get('user_id'),
            'abha_number'      => $abhaNumber,
            'abha_address'     => $phrAddr,
            'phr_address'      => $phrAddr,
            'full_name'        => $fullName,
            'first_name'       => $firstName,
            'middle_name'      => $middleName,
            'last_name'        => $lastName,
            'gender'           => strtoupper(trim((string) ($payload['gender'] ?? ''))),
            'mobile'           => trim((string) ($payload['mobile'] ?? $payload['mobileNumber'] ?? '')),
            'email'            => trim((string) ($payload['email'] ?? '')) ?: null,
            'mobile_verified'  => !empty($payload['mobileVerified']) ? 1 : 0,
            'date_of_birth'    => $dob,
            'year_of_birth'    => $yob,
            'address'          => trim((string) ($payload['address'] ?? '')) ?: null,
            'pin_code'         => trim((string) ($payload['pinCode'] ?? '')) ?: null,
            'state_name'       => trim((string) ($payload['stateName'] ?? '')) ?: null,
            'district_name'    => trim((string) ($payload['districtName'] ?? '')) ?: null,
            'abha_status'      => trim((string) ($payload['abhaStatus'] ?? 'ACTIVE')),
            'status'           => 'verified',
            'last_request_id'  => $requestId,
            'last_verified_at' => date('Y-m-d H:i:s'),
            'profile_json'     => json_encode($payload),
        ];

        $profileModel = new AbdmAbhaProfile();
        $existing = $profileModel->where('abha_number', $abhaNumber)->first();
        if ($existing !== null) {
            $profileModel->update((int) $existing->id, $row);
        } else {
            $profileModel->insert($row);
        }
    }

    // ─── Support Tickets ─────────────────────────────────────────────────────

    public function tickets()
    {
        if (!$this->guardHospital()) return $this->redirectUnauth();

        $hid = $this->hospitalId();
        $ticketModel = new SupportTicket();

        $status = $this->request->getGet('status') ?? '';
        $q = $ticketModel->where('hospital_id', $hid)->orderBy('created_at', 'DESC');
        if ($status !== '') $q->where('status', $status);
        $tickets = $q->findAll(50);

        return view('hospital/tickets', [
            'tickets'     => $tickets,
            'filterStatus'=> $status,
        ]);
    }

    public function ticketNew()
    {
        if (!$this->guardHospital()) return $this->redirectUnauth();
        return view('hospital/ticket_new');
    }

    public function ticketNewPost()
    {
        if (!$this->guardHospital()) return $this->redirectUnauth();

        $subject  = trim((string) $this->request->getPost('subject'));
        $category = trim((string) $this->request->getPost('category'));
        $priority = trim((string) $this->request->getPost('priority'));
        $message  = $this->sanitizeRichText((string) $this->request->getPost('message'));

        if ($subject === '' || strip_tags($message) === '') {
            return redirect()->back()->with('error', 'Subject and message are required.');
        }

        $ticketModel  = new SupportTicket();
        $messageModel = new SupportMessage();
        $attachModel  = new SupportAttachment();

        $ticketNumber = $ticketModel->nextTicketNumber();
        $now   = date('Y-m-d H:i:s');
        $hid   = $this->hospitalId();
        $uid   = (int) session()->get('user_id');
        $uname = (string) session()->get('username');

        $ticketId = $ticketModel->insert([
            'ticket_number'      => $ticketNumber,
            'hospital_id'        => $hid,
            'subject'            => $subject,
            'category'           => in_array($category, ['general','technical','billing','abha','opd','other'], true) ? $category : 'general',
            'priority'           => in_array($priority, ['low','medium','high'], true) ? $priority : 'medium',
            'status'             => 'open',
            'created_by_user_id' => $uid,
            'message_count'      => 1,
            'last_reply_at'      => $now,
            'last_reply_by'      => 'hospital',
        ], true);

        $msgId = $messageModel->insert([
            'ticket_id'   => $ticketId,
            'message'     => $message,
            'sender_type' => 'hospital',
            'sender_id'   => $uid,
            'sender_name' => $uname,
            'created_at'  => $now,
        ], true);

        $this->handleAttachmentUploads((int)$ticketId, (int)$msgId, 'hospital', $attachModel);

        return redirect()->to('/portal/tickets/' . $ticketId)
            ->with('message', 'Ticket ' . $ticketNumber . ' created successfully.');
    }

    public function ticketView(int $id)
    {
        if (!$this->guardHospital()) return $this->redirectUnauth();

        $hid = $this->hospitalId();
        $ticketModel  = new SupportTicket();
        $messageModel = new SupportMessage();
        $attachModel  = new SupportAttachment();

        $ticket = $ticketModel->where('id', $id)->where('hospital_id', $hid)->first();
        if ($ticket === null) {
            return redirect()->to('/portal/tickets')->with('error', 'Ticket not found.');
        }

        $messages     = $messageModel->forTicket($id);
        $attachments  = [];
        foreach ($messages as $msg) {
            $attachments[(int)$msg->id] = $attachModel->forMessage((int)$msg->id);
        }

        return view('hospital/ticket_view', [
            'ticket'      => $ticket,
            'messages'    => $messages,
            'attachments' => $attachments,
        ]);
    }

    public function ticketReplyPost(int $id)
    {
        if (!$this->guardHospital()) return $this->redirectUnauth();

        $hid = $this->hospitalId();
        $ticketModel  = new SupportTicket();
        $messageModel = new SupportMessage();
        $attachModel  = new SupportAttachment();

        $ticket = $ticketModel->where('id', $id)->where('hospital_id', $hid)->first();
        if ($ticket === null) {
            return redirect()->to('/portal/tickets')->with('error', 'Ticket not found.');
        }

        if (in_array((string)$ticket->status, ['resolved','closed'], true)) {
            return redirect()->to('/portal/tickets/' . $id)->with('error', 'Cannot reply to a resolved/closed ticket.');
        }

        $message = $this->sanitizeRichText((string) $this->request->getPost('message'));
        $file    = $this->request->getFile('attachment');
        $hasFile = $file !== null && $file->isValid() && !$file->hasMoved();

        if (strip_tags($message) === '' && !$hasFile) {
            return redirect()->to('/portal/tickets/' . $id)->with('error', 'Message or attachment required.');
        }

        $now   = date('Y-m-d H:i:s');
        $uid   = (int) session()->get('user_id');
        $uname = (string) session()->get('username');

        $msgId = $messageModel->insert([
            'ticket_id'   => $id,
            'message'     => $message,
            'sender_type' => 'hospital',
            'sender_id'   => $uid,
            'sender_name' => $uname,
            'created_at'  => $now,
        ], true);

        $this->handleAttachmentUploads($id, (int)$msgId, 'hospital', $attachModel);

        $ticketModel->update($id, [
            'message_count' => (int)$ticket->message_count + 1,
            'last_reply_at' => $now,
            'last_reply_by' => 'hospital',
            'status'        => 'open',
        ]);

        return redirect()->to('/portal/tickets/' . $id)->with('message', 'Reply sent.');
    }

    public function ticketClosePost(int $id)
    {
        if (!$this->guardHospital()) return $this->redirectUnauth();

        $hid         = $this->hospitalId();
        $ticketModel = new SupportTicket();
        $ticket      = $ticketModel->where('id', $id)->where('hospital_id', $hid)->first();

        if ($ticket === null) {
            return redirect()->to('/portal/tickets')->with('error', 'Ticket not found.');
        }
        if ($ticket->status === 'closed') {
            return redirect()->to('/portal/tickets/' . $id)->with('error', 'Ticket is already closed.');
        }

        $ticketModel->update($id, ['status' => 'closed']);
        return redirect()->to('/portal/tickets/' . $id)->with('message', 'Ticket closed successfully.');
    }

    public function ticketAttachmentDownload(int $attachId)
    {
        if (!$this->guardHospital()) return $this->redirectUnauth();

        $attachModel = new SupportAttachment();
        $attach      = $attachModel->find($attachId);

        if ($attach === null) {
            return redirect()->back()->with('error', 'Attachment not found.');
        }

        // Ensure this hospital owns the ticket
        $ticketModel = new SupportTicket();
        $ticket = $ticketModel->where('id', $attach->ticket_id)
            ->where('hospital_id', $this->hospitalId())->first();
        if ($ticket === null) {
            return redirect()->back()->with('error', 'Access denied.');
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

    // ─── Attachment upload helper ─────────────────────────────────────────────

    private function handleAttachmentUploads(int $ticketId, int $msgId, string $uploaderType, SupportAttachment $attachModel): void
    {
        $files = $this->request->getFiles();
        $uploads = $files['attachments'] ?? [];
        if (!is_array($uploads)) $uploads = [$uploads];

        $allowed = ['pdf','doc','docx','xls','xlsx','jpg','jpeg','png','gif','txt','zip'];
        $maxSize = 5 * 1024 * 1024; // 5 MB

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

    // ─── Sanitize Quill HTML output ───────────────────────────────────────────

    private function sanitizeRichText(string $html): string
    {
        $allowed = '<p><br><b><strong><i><em><u><s><strike><ol><ul><li><h1><h2><h3><blockquote><pre><code><span><a>';
        return strip_tags($html, $allowed);
    }

    // ─── Health Facility QR ──────────────────────────────────────────────────

    public function facilityQr()
    {
        if (!$this->guardHospital()) return $this->redirectUnauth();

        $hid           = $this->hospitalId();
        $hospitalModel = new AbdmHospital();
        $hospital      = $hospitalModel->find($hid);

        return view('hospital/facility_qr', [
            'hospital' => $hospital,
            'message'  => session()->getFlashdata('message'),
            'error'    => session()->getFlashdata('error'),
        ]);
    }

    public function facilityQrUpload()
    {
        if (!$this->guardHospital()) return $this->redirectUnauth();

        $hid  = $this->hospitalId();
        $file = $this->request->getFile('facility_qr');

        if (!$file || !$file->isValid() || $file->hasMoved()) {
            return redirect()->to('/portal/facility-qr')->with('error', 'No file uploaded or invalid file.');
        }

        $mime = $file->getMimeType();
        if (!in_array($mime, ['image/png', 'image/jpeg', 'image/gif', 'image/webp'], true)) {
            return redirect()->to('/portal/facility-qr')->with('error', 'Only PNG/JPEG/GIF/WebP images are allowed.');
        }

        if ($file->getSize() > 2 * 1024 * 1024) {
            return redirect()->to('/portal/facility-qr')->with('error', 'File too large. Max 2 MB.');
        }

        $base64 = base64_encode((string) file_get_contents($file->getTempName()));
        $data   = 'data:' . $mime . ';base64,' . $base64;

        (new AbdmHospital())->update($hid, ['facility_qr_data' => $data]);

        return redirect()->to('/portal/facility-qr')->with('message', 'Facility QR uploaded successfully.');
    }

    // ─── Serve stored official ABHA card PNG ──────────────────────────────────

    public function patientAbhaCard(): \CodeIgniter\HTTP\ResponseInterface
    {
        if (!$this->guardHospital()) {
            return $this->response->setStatusCode(403)->setBody('Unauthorised');
        }

        $hid        = (int) session()->get('hospital_id');
        $abhaNumber = trim((string) $this->request->getGet('abha_number'));
        if ($abhaNumber === '') {
            return $this->response->setStatusCode(400)->setBody('Missing abha_number');
        }

        $profileModel = new AbdmAbhaProfile();
        $row = $profileModel->where('abha_number', $abhaNumber)
                            ->where('hospital_id', $hid)
                            ->first();

        if ($row === null) {
            return $this->response->setStatusCode(404)->setBody('Profile not found');
        }

        $pj     = is_string($row->profile_json ?? null) ? (json_decode($row->profile_json, true) ?? []) : [];
        $base64 = $pj['abha_card_base64'] ?? null;

        if (empty($base64)) {
            return $this->response->setStatusCode(404)->setBody('Card not yet stored. Verify ABHA again to download the official card.');
        }

        return $this->response
            ->setHeader('Content-Type', 'image/png')
            ->setHeader('Cache-Control', 'private, max-age=3600')
            ->setBody(base64_decode((string) $base64));
    }

    // ─── HPR Professionals ────────────────────────────────────────────────────

    public function hprProfessionals()
    {
        if (!$this->guardHospital()) return $this->redirectUnauth();

        $hid      = $this->hospitalId();
        $hprModel = new \App\Models\HospitalHprProfessional();

        return view('hospital/hpr_professionals', [
            'hospital'      => (new AbdmHospital())->find($hid),
            'professionals' => $hprModel->forHospital($hid),
            'message'       => session()->getFlashdata('message'),
            'error'         => session()->getFlashdata('error'),
        ]);
    }

    public function hprProfessionalCreate()
    {
        if (!$this->guardHospital()) return $this->redirectUnauth();

        $hid                = $this->hospitalId();
        $name               = trim((string) $this->request->getPost('name'));
        $hprId              = trim((string) $this->request->getPost('hpr_id'));
        $designation        = trim((string) $this->request->getPost('designation'));
        $specsArr           = json_decode(trim((string) $this->request->getPost('specializations_json')) ?: '[]', true);
        if (!is_array($specsArr)) $specsArr = [];
        $specsArr           = array_values(array_filter($specsArr, fn($s) => !empty($s['term'])));
        $specializationValue = !empty($specsArr) ? json_encode($specsArr, JSON_UNESCAPED_UNICODE) : null;
        $department         = trim((string) $this->request->getPost('department'));
        $registrationNumber = trim((string) $this->request->getPost('registration_number'));
        $back = '/portal/hpr-professionals';

        if ($name === '') {
            return redirect()->to($back)->with('error', 'Professional name is required.');
        }
        if ($hprId === '') {
            return redirect()->to($back)->with('error', 'HPR ID is required.');
        }
        if (!\App\Models\HospitalHprProfessional::validateHprId($hprId)) {
            return redirect()->to($back)->with('error', 'Invalid HPR ID format. Expected: name@hpr.abdm or 14-digit number.');
        }

        $hprModel = new \App\Models\HospitalHprProfessional();
        $existing = $hprModel->where('hospital_id', $hid)->where('hpr_id', $hprId)->first();
        if ($existing !== null) {
            return redirect()->to($back)->with('error', 'This HPR ID is already registered for your hospital.');
        }

        $hprModel->insert([
            'hospital_id'         => $hid,
            'name'                => $name,
            'hpr_id'              => $hprId,
            'designation'         => $designation ?: null,
            'specialization'      => $specializationValue,
            'specialization_code' => null,
            'department'          => $department ?: null,
            'registration_number' => $registrationNumber ?: null,
            'is_active'           => 1,
        ]);

        return redirect()->to($back)->with('message', '"' . $name . '" added to HPR professionals.');
    }

    public function hprProfessionalDelete(int $id)
    {
        if (!$this->guardHospital()) return $this->redirectUnauth();

        $hid      = $this->hospitalId();
        $hprModel = new \App\Models\HospitalHprProfessional();
        $prof     = $hprModel->find($id);

        if ($prof === null || (int) $prof['hospital_id'] !== $hid) {
            return redirect()->to('/portal/hpr-professionals')->with('error', 'Professional not found.');
        }

        $hprModel->delete($id);
        return redirect()->to('/portal/hpr-professionals')->with('message', 'Professional removed.');
    }
}

