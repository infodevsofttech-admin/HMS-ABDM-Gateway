<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * AuditLogModel
 *
 * Compliance audit log for all significant gateway operations.
 * Immutable: rows are insert-only; updates and deletes are not permitted.
 */
class AuditLogModel extends Model
{
    protected $table            = 'audit_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'ip_address',
        'request_data',
        'response_data',
        'status',
    ];

    // Only created_at; no updated_at (immutable records)
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    /**
     * Write a single audit log entry.
     *
     * @param int|null             $userId
     * @param string               $action       e.g. 'login_success', 'sync_hospital'
     * @param string               $entityType   e.g. 'user', 'hospital', 'claim'
     * @param int|null             $entityId
     * @param string               $ipAddress
     * @param array<string, mixed>|null $requestData
     * @param array<string, mixed>|null $responseData
     * @param string               $status       'success' | 'failed'
     */
    public function record(
        ?int $userId,
        string $action,
        string $entityType,
        ?int $entityId,
        string $ipAddress,
        ?array $requestData,
        ?array $responseData,
        string $status
    ): int|string {
        // Scrub sensitive fields before storing
        $safeRequest = $this->scrubSensitive($requestData ?? []);

        return $this->insert([
            'user_id'       => $userId,
            'action'        => $action,
            'entity_type'   => $entityType,
            'entity_id'     => $entityId,
            'ip_address'    => $ipAddress,
            'request_data'  => json_encode($safeRequest),
            'response_data' => json_encode($responseData),
            'status'        => $status,
        ]);
    }

    /**
     * Remove sensitive fields from data before logging.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function scrubSensitive(array $data): array
    {
        $sensitiveKeys = ['password', 'api_token', 'token', 'secret', 'api_key'];
        foreach ($sensitiveKeys as $key) {
            if (array_key_exists($key, $data)) {
                $data[$key] = '***';
            }
        }

        return $data;
    }
}
