<?php

namespace App\Controllers;

use App\Models\AbdmHospitalUser;
use App\Models\AbdmHospital;
use App\Models\AdminUser;
use App\Models\HospitalRegistration;
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
            $hospitalName    = trim((string) $this->request->getPost('hospital_name'));
            $hfrId           = trim((string) $this->request->getPost('hfr_id'));
            $contactName     = trim((string) $this->request->getPost('contact_name'));
            $contactEmail    = trim((string) $this->request->getPost('contact_email'));
            $contactPhone    = trim((string) $this->request->getPost('contact_phone'));
            $city            = trim((string) $this->request->getPost('city'));
            $state           = trim((string) $this->request->getPost('state'));
            $description     = trim((string) $this->request->getPost('description'));
            $username        = trim((string) $this->request->getPost('username'));
            $password        = (string) $this->request->getPost('password');
            $confirmPassword = (string) $this->request->getPost('confirm_password');

            // Required fields
            if (!$hospitalName || !$contactName || !$contactEmail || !$contactPhone || !$username || !$password) {
                return redirect()->back()->withInput()->with('error', 'Please fill all required fields.');
            }
            if (!filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
                return redirect()->back()->withInput()->with('error', 'Invalid email address.');
            }
            if (strlen($password) < 8) {
                return redirect()->back()->withInput()->with('error', 'Password must be at least 8 characters.');
            }
            if ($password !== $confirmPassword) {
                return redirect()->back()->withInput()->with('error', 'Passwords do not match.');
            }

            $regModel = new HospitalRegistration();

            // Prevent duplicate pending applications
            $dup = $regModel->where('desired_username', $username)->where('status', 'pending')->first();
            if ($dup) {
                return redirect()->back()->withInput()->with('error', 'An application with this username is already pending review.');
            }
            $dupEmail = $regModel->where('contact_email', $contactEmail)->where('status', 'pending')->first();
            if ($dupEmail) {
                return redirect()->back()->withInput()->with('error', 'An application with this email is already pending review.');
            }

            $regModel->insert([
                'hospital_name'    => $hospitalName,
                'hfr_id'           => $hfrId ?: null,
                'contact_name'     => $contactName,
                'contact_email'    => $contactEmail,
                'contact_phone'    => $contactPhone,
                'city'             => $city ?: null,
                'state'            => $state ?: null,
                'description'      => $description ?: null,
                'desired_username' => $username,
                'password_hash'    => password_hash($password, PASSWORD_BCRYPT),
                'status'           => 'pending',
            ]);

            return view('auth/register', ['submitted' => true]);
        }

        return view('auth/register');
    }

    public function logout()
    {
        $portal = session()->get('portal');
        session()->destroy();
        $target = ($portal === 'hospital') ? '/' : '/admin';
        return redirect()->to($target)->with('message', 'Logged out successfully.');
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
