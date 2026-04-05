<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * RoleFilter
 *
 * Restricts access to routes based on the authenticated user's role.
 *
 * Usage in Routes.php:
 *   $routes->group('admin', ['filter' => 'role:admin'], static function ($routes) { ... });
 *   $routes->group('insurance', ['filter' => 'role:admin,insurance'], static function ($routes) { ... });
 *
 * The ApiAuthFilter must run before this filter (user_role must be set on the request).
 */
class RoleFilter implements FilterInterface
{
    /**
     * @param list<string>|null $arguments  Comma-separated list of allowed roles, e.g. ['admin', 'doctor']
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // No role restriction configured – allow all authenticated users
        if (empty($arguments)) {
            return null;
        }

        $allowedRoles = [];
        foreach ($arguments as $arg) {
            foreach (explode(',', $arg) as $role) {
                $allowedRoles[] = trim($role);
            }
        }

        $userRole = $request->user_role ?? null;

        if ($userRole === null || ! in_array($userRole, $allowedRoles, true)) {
            return service('response')
                ->setStatusCode(403)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Access denied: insufficient role privileges',
                ]);
        }

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
