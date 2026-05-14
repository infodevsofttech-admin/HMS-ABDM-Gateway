<?php

namespace App\Controllers;

use App\Models\AbdmHospitalUser;
use App\Models\AbdmHospital;
use App\Models\AdminUser;
use CodeIgniter\Controller;

class Auth extends Controller
{
    protected $userModel;        // hospital/clinic users
    protected $adminUserModel;   // admin panel users
    protected $hospitalModel;

    public function __construct()
    {
        $this->userModel      = new AbdmHospitalUser();
        $this->adminUserModel = new AdminUser();
        $this->hospitalModel  = new AbdmHospital();
    }

    public function login()
    {
        return $this->handleHospitalLogin('/auth/login');
    }

    public function hospitalLogin()
    {
        return $this->handleHospitalLogin('/');
    }

    public function adminLogin()
    {
        return $this->handleAdminLogin();
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
        return redirect()->to('/admin')->with('message', 'Logged out successfully.');
    }

    // ----------------------------------------------------------------
    // Admin panel login — uses admin_users table
    // ----------------------------------------------------------------
    protected function handleAdminLogin()
    {
        if ($this->request->is('post')) {
            $username = trim((string) $this->request->getPost('username'));
            $password = trim((string) $this->request->getPost('password'));

            if (empty($username) || empty($password)) {
                return redirect()->back()->with('error', 'Username and password are required.');
            }

            $user = $this->adminUserModel
                ->where('username', $username)
                ->where('is_active', 1)
                ->first();

            if (!$user || !password_verify($password, $user->password_hash)) {
                return redirect()->back()->with('error', 'Invalid username or password.');
            }

            $this->adminUserModel->update($user->id, ['last_login_at' => date('Y-m-d H:i:s')]);

            session()->set([
                'user_id'      => $user->id,
                'username'     => $user->username,
                'hospital_id'  => null,
                'role'         => $user->role,
                'portal'       => 'admin',
                'is_logged_in' => true,
            ]);

            return redirect()->to('/admin/dashboard')->with('message', 'Welcome, ' . $user->username . '!');
        }

        return view('auth/admin_login');
    }

    // ----------------------------------------------------------------
    // Hospital / Clinic portal login — uses abdm_hospital_users table
    // ----------------------------------------------------------------
    protected function handleHospitalLogin(string $formAction)
    {
        if ($this->request->is('post')) {
            $username = trim((string) $this->request->getPost('username'));
            $password = trim((string) $this->request->getPost('password'));
            $hfrId    = trim((string) $this->request->getPost('hfr_id'));

            if (empty($username) || empty($password)) {
                return redirect()->back()->with('error', 'Username and password are required.');
            }

            if ($hfrId === '') {
                return redirect()->back()->with('error', 'HFR ID or Hospital ID is required.');
            }

            $user = $this->userModel
                ->where('username', $username)
                ->where('is_active', 1)
                ->first();

            if (!$user || !password_verify($password, $user->password_hash)) {
                return redirect()->back()->with('error', 'Invalid username or password.');
            }

            // Verify HFR ID matches the hospital this user belongs to
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

            $this->userModel->update($user->id, ['last_login_at' => date('Y-m-d H:i:s')]);

            session()->set([
                'user_id'      => $user->id,
                'username'     => $user->username,
                'hospital_id'  => $user->hospital_id,
                'role'         => $user->role,
                'portal'       => 'hospital',
                'is_logged_in' => true,
            ]);

            return redirect()->to('/dashboard')->with('message', 'Login successful!');
        }

        return view('auth/login', [
            'formAction' => $formAction,
            'portal'     => 'hospital',
        ]);
    }
}
