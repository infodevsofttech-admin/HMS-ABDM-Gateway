<?php

namespace App\Controllers;

use App\Models\AuditLogModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * AuthController
 *
 * Authenticates HMS instances via username/password and issues
 * an API bearer token.
 */
class AuthController extends BaseController
{
    protected UserModel $userModel;
    protected AuditLogModel $auditLog;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ): void {
        parent::initController($request, $response, $logger);

        $this->userModel = new UserModel();
        $this->auditLog  = new AuditLogModel();
    }

    /**
     * POST /api/v1/auth/login
     *
     * Accepts { "username": "...", "password": "..." } and returns
     * { "token": "...", "role": "...", "expires_at": "..." }.
     */
    public function login(): ResponseInterface
    {
        $rules = [
            'username' => 'required|min_length[3]|max_length[100]',
            'password' => 'required|min_length[6]',
        ];

        if (! $this->validate($rules)) {
            return $this->response
                ->setStatusCode(422)
                ->setJSON(['status' => 'error', 'errors' => $this->validator->getErrors()]);
        }

        $username = $this->request->getVar('username');
        $password = $this->request->getVar('password');

        $user = $this->userModel->findByUsername($username);

        if ($user === null || ! password_verify($password, $user['password'])) {
            $this->auditLog->record(
                null,
                'login_failed',
                'user',
                null,
                $this->request->getIPAddress(),
                ['username' => $username],
                null,
                'failed'
            );

            return $this->response
                ->setStatusCode(401)
                ->setJSON(['status' => 'error', 'message' => 'Invalid credentials']);
        }

        if ($user['status'] !== 'active') {
            return $this->response
                ->setStatusCode(403)
                ->setJSON(['status' => 'error', 'message' => 'Account is inactive']);
        }

        // Generate a new API token
        $token     = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $this->userModel->update($user['id'], [
            'api_token'         => hash('sha256', $token),
            'token_expires_at'  => $expiresAt,
        ]);

        $this->auditLog->record(
            $user['id'],
            'login_success',
            'user',
            $user['id'],
            $this->request->getIPAddress(),
            ['username' => $username],
            null,
            'success'
        );

        return $this->response->setJSON([
            'status'     => 'success',
            'token'      => $token,
            'role'       => $user['role'],
            'expires_at' => $expiresAt,
        ]);
    }
}
