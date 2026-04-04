<?php

namespace App\Controllers;

use App\Models\AuditLogModel;
use App\Services\AbdmApiService;
use App\Services\SyncQueueService;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * ApiController
 *
 * Base class for all HMS-ABDM Gateway API controllers.
 * Provides shared helpers for JSON responses, input validation, and audit logging.
 */
abstract class ApiController extends BaseController
{
    protected AuditLogModel  $auditLog;
    protected AbdmApiService  $abdmApi;
    protected SyncQueueService $syncQueue;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger): void
    {
        parent::initController($request, $response, $logger);

        $this->auditLog  = new AuditLogModel();
        $this->abdmApi   = new AbdmApiService();
        $this->syncQueue = new SyncQueueService();
    }

    // -------------------------------------------------------------------------
    // Response helpers
    // -------------------------------------------------------------------------

    /**
     * Return a JSON success response.
     */
    protected function successResponse(array $data, int $statusCode = 200): ResponseInterface
    {
        return $this->response
            ->setStatusCode($statusCode)
            ->setContentType('application/json')
            ->setJSON(['success' => true, 'data' => $data]);
    }

    /**
     * Return a JSON error response.
     */
    protected function errorResponse(string $message, int $statusCode = 400, array $errors = []): ResponseInterface
    {
        $body = ['success' => false, 'message' => $message];

        if ($errors !== []) {
            $body['errors'] = $errors;
        }

        return $this->response
            ->setStatusCode($statusCode)
            ->setContentType('application/json')
            ->setJSON($body);
    }

    // -------------------------------------------------------------------------
    // Input helpers
    // -------------------------------------------------------------------------

    /**
     * Parse and return the JSON body from the incoming request.
     *
     * @return array<string, mixed>
     */
    protected function getJsonBody(): array
    {
        $body = $this->request->getJSON(true);

        return is_array($body) ? $body : [];
    }

    /**
     * Validate that required keys are present in the payload.
     *
     * @param  array<string>       $required
     * @param  array<string,mixed> $data
     * @return array<string>       Missing field names (empty = all present).
     */
    protected function getMissingFields(array $required, array $data): array
    {
        return array_filter($required, static fn (string $field) => empty($data[$field]));
    }

    // -------------------------------------------------------------------------
    // Audit helpers
    // -------------------------------------------------------------------------

    /**
     * Write an audit log entry for an inbound API call.
     */
    protected function audit(
        string $action,
        string $recordType,
        array $requestPayload,
        array|string $responsePayload,
        int $statusCode,
        string $hmsId = ''
    ): void {
        $this->auditLog->record(
            $action,
            $recordType,
            $requestPayload,
            $responsePayload,
            $statusCode,
            $hmsId,
            $this->request->getIPAddress(),
            (string) $this->request->getUserAgent()
        );
    }
}
