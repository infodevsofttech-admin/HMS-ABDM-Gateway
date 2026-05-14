<?php

namespace App\Controllers;

use App\Models\AbdmHospitalUser;
use App\Models\AbdmHospital;
use CodeIgniter\Controller;

class Auth extends Controller
{
    protected $userModel;
    protected $hospitalModel;

    public function __construct()
    {
        $this->userModel = new AbdmHospitalUser();
        $this->hospitalModel = new AbdmHospital();
    }

    public function login()
    {
        return $this->handleLogin('/auth/login', 'hospital');
    }

    public function hospitalLogin()
    {
        return $this->handleLogin('/', 'hospital');
    }

    public function adminLogin()
    {
        return $this->handleLogin('/admin', 'admin');
    }

    public function register()
    {
        if ($this->request->is('post')) {
            $username = trim((string) $this->request->getPost('username'));
            $password = trim((string) $this->request->getPost('password'));
            $confirm_password = trim((string) $this->request->getPost('confirm_password'));
            $hospital_id = (int) $this->request->getPost('hospital_id');

            // Validation
            if (empty($username) || empty($password)) {
                return redirect()->back()->with('error', 'Username and password are required.');
            }

            if (strlen($password) < 6) {
                return redirect()->back()->with('error', 'Password must be at least 6 characters.');
            }

            if ($password !== $confirm_password) {
                return redirect()->back()->with('error', 'Passwords do not match.');
            }

            // Check if username already exists
            $existing = $this->userModel->where('username', $username)->first();
            if ($existing) {
                return redirect()->back()->with('error', 'Username already exists.');
            }

            // Check if hospital exists
            $hospitalModel = new \App\Models\AbdmHospital();
            if (!$hospitalModel->find($hospital_id)) {
                return redirect()->back()->with('error', 'Selected hospital does not exist.');
            }

            // Generate API token
            $apiToken = bin2hex(random_bytes(64));

            // Create user
            $insertData = [
                'hospital_id' => $hospital_id,
                'username' => $username,
                'password_hash' => password_hash($password, PASSWORD_BCRYPT),
                'api_token' => $apiToken,
                'role' => 'hospital_user',
                'is_active' => 1,
            ];

            if ($this->userModel->insert($insertData)) {
                return redirect()->to('/auth/login')->with('message', 'Registration successful! Please login.');
            } else {
                return redirect()->back()->with('error', 'Registration failed. Please try again.');
            }
        }

        $hospitalModel = new \App\Models\AbdmHospital();
        $hospitals = $hospitalModel->findAll();

        return view('auth/register', ['hospitals' => $hospitals]);
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/auth/login')->with('message', 'Logged out successfully.');
    }

    protected function handleLogin(string $formAction, string $portal)
    {
        $isAdminPortal = $portal === 'admin';

        if ($this->request->is('post')) {
            $username = trim((string) $this->request->getPost('username'));
            $password = trim((string) $this->request->getPost('password'));
            $hfrId = trim((string) $this->request->getPost('hfr_id'));

            if (empty($username) || empty($password)) {
                return redirect()->back()->with('error', 'Username and password are required.');
            }

            if (!$isAdminPortal && $hfrId === '') {
                return redirect()->back()->with('error', 'HFR ID or Hospital ID is required.');
            }

            $user = $this->userModel->where('username', $username)->where('is_active', 1)->first();

            if (!$user || !password_verify($password, $user->password_hash)) {
                return redirect()->back()->with('error', 'Invalid username or password.');
            }

            $role = (string) $user->role;
            if ($isAdminPortal && !$this->isAdminRole($role)) {
                return redirect()->back()->with('error', 'This account does not have admin portal access.');
            }

            if (!$isAdminPortal && $this->isAdminRole($role)) {
                return redirect()->back()->with('error', 'Please use the admin portal to login.');
            }

            if (!$isAdminPortal) {
                $hospital = $this->hospitalModel->find($user->hospital_id);
                $hospitalHfrId = '';
                if (is_object($hospital) && isset($hospital->hfr_id)) {
                    $hospitalHfrId = (string) $hospital->hfr_id;
                } elseif (is_array($hospital) && isset($hospital['hfr_id'])) {
                    $hospitalHfrId = (string) $hospital['hfr_id'];
                }

                if ($hospitalHfrId === '' || strcasecmp($hospitalHfrId, $hfrId) !== 0) {
                    return redirect()->back()->with('error', 'Invalid HFR ID or Hospital ID.');
                }
            }

            $this->userModel->update($user->id, ['last_login_at' => date('Y-m-d H:i:s')]);

            session()->set([
                'user_id' => $user->id,
                'username' => $user->username,
                'hospital_id' => $user->hospital_id,
                'role' => $user->role,
                'is_logged_in' => true,
            ]);

            return redirect()->to('/admin/dashboard')->with('message', 'Login successful!');
        }

        return view('auth/login', [
            'formAction' => $formAction,
            'portal' => $portal,
        ]);
    }

    protected function isAdminRole(string $role): bool
    {
        return in_array(strtolower($role), ['admin', 'super_admin', 'service_provider'], true);
    }
}
