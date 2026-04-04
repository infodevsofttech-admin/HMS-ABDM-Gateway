<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * AdminAuthFilter
 *
 * Protects /admin routes with a simple session-based login.
 * The admin password is set in GATEWAY_ADMIN_TOKEN in .env.
 */
class AdminAuthFilter implements FilterInterface
{
    /**
     * @param  array<string>|null $arguments
     * @return ResponseInterface|null
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // Allow the login page itself through
        $uri = $request->getUri()->getPath();
        if (str_ends_with($uri, '/admin/login') || str_ends_with($uri, '/admin/logout')) {
            return null;
        }

        if (session()->get('admin_logged_in') !== true) {
            return redirect()->to(base_url('admin/login'));
        }

        return null;
    }

    /**
     * @param  array<string>|null $arguments
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {
        // nothing to do after
    }
}
