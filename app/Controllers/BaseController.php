<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\HTTP\RequestInterface;
use Psr\Log\LoggerInterface;

abstract class BaseController extends Controller
{
    protected $request;

    /**
     * An array of helpers to be loaded automatically upon
     * class instantiation. These helpers will be available
     * to all other controllers that extend BaseController.
     *
     * @var array
     */
    protected $helpers = [];

    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     *
     * @var array
     */
    protected $data = [];

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
        // E.g.: $this->session = \Config\Services::session();
    }

    /**
     * Helper method to return JSON response
     */
    protected function respondJSON($data, int $statusCode = 200, string $message = null)
    {
        return $this->response
            ->setStatusCode($statusCode)
            ->setContentType('application/json')
            ->setBody(json_encode([
                'status' => $statusCode >= 400 ? 'error' : 'success',
                'message' => $message,
                'data' => $data,
                'timestamp' => date('c'),
            ]));
    }

    /**
     * Helper method to return error response
     */
    protected function respondError(string $message, int $statusCode = 400, $data = null)
    {
        return $this->response
            ->setStatusCode($statusCode)
            ->setContentType('application/json')
            ->setBody(json_encode([
                'status' => 'error',
                'message' => $message,
                'data' => $data,
                'timestamp' => date('c'),
            ]));
    }
}
