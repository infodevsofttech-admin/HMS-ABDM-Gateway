<?php

namespace App\Filters;

use App\Models\ApiKeyModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * ApiKeyFilter
 *
 * Validates the X-API-Key header on all /sync/* routes.
 * Returns HTTP 401 if the key is missing or not recognised.
 */
class ApiKeyFilter implements FilterInterface
{
    /**
     * @param  array<string>|null $arguments
     * @return ResponseInterface|null
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $key = $request->getHeaderLine('X-API-Key');

        if ($key === '') {
            return service('response')
                ->setStatusCode(401)
                ->setContentType('application/json')
                ->setJSON([
                    'success' => false,
                    'message' => 'Missing X-API-Key header. Register your hospital at the gateway admin panel to obtain a key.',
                ]);
        }

        $model    = new ApiKeyModel();
        $facility = $model->findByKey($key);

        if ($facility === null) {
            return service('response')
                ->setStatusCode(401)
                ->setContentType('application/json')
                ->setJSON([
                    'success' => false,
                    'message' => 'Invalid or inactive API key.',
                ]);
        }

        // Make facility info available to controllers via request attribute
        $request->setGlobal('hms_facility', $facility);

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
