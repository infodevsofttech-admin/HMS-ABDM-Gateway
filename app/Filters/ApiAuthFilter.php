<?php

namespace App\Filters;

use App\Models\UserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * ApiAuthFilter
 *
 * Verifies the Bearer token supplied in the Authorization header.
 * Attaches the authenticated user to the request on success,
 * or returns a 401 JSON response on failure.
 */
class ApiAuthFilter implements FilterInterface
{
    /**
     * @param list<string>|null $arguments
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader) || ! str_starts_with($authHeader, 'Bearer ')) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON(['status' => 'error', 'message' => 'Missing or malformed Authorization header']);
        }

        $token = trim(substr($authHeader, 7));

        if ($token === '') {
            return service('response')
                ->setStatusCode(401)
                ->setJSON(['status' => 'error', 'message' => 'Empty token']);
        }

        $tokenHash = hash('sha256', $token);

        /** @var UserModel $userModel */
        $userModel = new UserModel();

        $user = $userModel
            ->where('api_token', $tokenHash)
            ->where('status', 'active')
            ->where('token_expires_at >', date('Y-m-d H:i:s'))
            ->first();

        if ($user === null) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON(['status' => 'error', 'message' => 'Invalid or expired token']);
        }

        // Attach user info to the request so controllers can read it
        $request->user_id    = $user['id'];
        $request->user_role  = $user['role'];
        $request->hospital_id = $user['hospital_id'] ?? null;

        return null;
    }

    /**
     * @param list<string>|null $arguments
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {
        // Nothing to do after the response
    }
}
