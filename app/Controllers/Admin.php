<?php

namespace App\Controllers;

use App\Models\ApiKeyModel;

/**
 * Admin Controller
 *
 * Web UI for registering local HMS facilities and managing their API keys.
 * Protected by session-based login (GATEWAY_ADMIN_TOKEN in .env).
 */
class Admin extends BaseController
{
    private ApiKeyModel $keyModel;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ): void {
        parent::initController($request, $response, $logger);
        $this->keyModel = new ApiKeyModel();
    }

    // -------------------------------------------------------------------------
    // Auth
    // -------------------------------------------------------------------------

    public function login(): string
    {
        return view('admin/login');
    }

    public function doLogin(): \CodeIgniter\HTTP\RedirectResponse
    {
        $token     = $this->request->getPost('token');
        $adminToken = env('GATEWAY_ADMIN_TOKEN', '');

        if ($adminToken === '') {
            return redirect()->back()->with('error', 'GATEWAY_ADMIN_TOKEN is not configured in .env');
        }

        if (!hash_equals($adminToken, (string) $token)) {
            return redirect()->back()->with('error', 'Invalid admin token.');
        }

        session()->set('admin_logged_in', true);

        return redirect()->to(base_url('admin'));
    }

    public function logout(): \CodeIgniter\HTTP\RedirectResponse
    {
        session()->destroy();

        return redirect()->to(base_url('admin/login'));
    }

    // -------------------------------------------------------------------------
    // Dashboard
    // -------------------------------------------------------------------------

    public function index(): string
    {
        $data = [
            'facilities' => $this->keyModel->orderBy('created_at', 'DESC')->findAll(),
        ];

        return view('admin/index', $data);
    }

    // -------------------------------------------------------------------------
    // Register hospital
    // -------------------------------------------------------------------------

    public function showRegisterForm(): string
    {
        return view('admin/register');
    }

    public function registerHospital(): \CodeIgniter\HTTP\RedirectResponse
    {
        $rules = [
            'hospital_name' => 'required|max_length[255]',
            'hms_id'        => 'required|max_length[100]|is_unique[api_keys.hms_id]',
            'contact_email' => 'permit_empty|valid_email|max_length[255]',
            'contact_phone' => 'permit_empty|max_length[20]',
            'state'         => 'permit_empty|max_length[100]',
            'district'      => 'permit_empty|max_length[100]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'hospital_name' => $this->request->getPost('hospital_name'),
            'hms_id'        => $this->request->getPost('hms_id'),
            'contact_email' => $this->request->getPost('contact_email'),
            'contact_phone' => $this->request->getPost('contact_phone'),
            'state'         => $this->request->getPost('state'),
            'district'      => $this->request->getPost('district'),
        ];

        $apiKey = $this->keyModel->registerFacility($data);

        // Flash the key once — it will not be shown again in full
        session()->setFlashdata('new_api_key', $apiKey);
        session()->setFlashdata('new_hospital_name', $data['hospital_name']);

        return redirect()->to(base_url('admin'))->with('success', 'Hospital registered successfully. Copy the API key below — it will not be shown again.');
    }

    // -------------------------------------------------------------------------
    // Key management
    // -------------------------------------------------------------------------

    public function regenerateKey(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $facility = $this->keyModel->find($id);

        if ($facility === null) {
            return redirect()->to(base_url('admin'))->with('error', 'Facility not found.');
        }

        $newKey = $this->keyModel->regenerateKey($id);

        session()->setFlashdata('new_api_key', $newKey);
        session()->setFlashdata('new_hospital_name', $facility['hospital_name']);

        return redirect()->to(base_url('admin'))->with('success', 'API key regenerated for ' . esc($facility['hospital_name']) . '. Copy it now — it will not be shown again.');
    }

    public function toggleActive(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $facility = $this->keyModel->find($id);

        if ($facility === null) {
            return redirect()->to(base_url('admin'))->with('error', 'Facility not found.');
        }

        $this->keyModel->toggleActive($id);
        $status = $facility['is_active'] ? 'disabled' : 'enabled';

        return redirect()->to(base_url('admin'))->with('success', esc($facility['hospital_name']) . ' has been ' . $status . '.');
    }

    public function delete(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $facility = $this->keyModel->find($id);

        if ($facility === null) {
            return redirect()->to(base_url('admin'))->with('error', 'Facility not found.');
        }

        $this->keyModel->delete($id);

        return redirect()->to(base_url('admin'))->with('success', esc($facility['hospital_name']) . ' has been removed.');
    }
}
