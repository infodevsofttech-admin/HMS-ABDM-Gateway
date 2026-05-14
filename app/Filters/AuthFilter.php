<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (!session()->has('is_logged_in')) {
            // Redirect to the appropriate login page based on the requested URL
            $uri = (string) $request->getUri()->getPath();
            if (str_starts_with($uri, '/admin') || str_starts_with($uri, 'admin')) {
                return redirect()->to('/admin')->with('error', 'Please login to access the admin panel.');
            }
            return redirect()->to('/')->with('error', 'Please login first.');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
