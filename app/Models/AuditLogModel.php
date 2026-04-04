<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * AuditLog Model
 *
 * Compliance audit trail for every inbound request and outbound ABDM call.
 */
class AuditLogModel extends Model
{
    protected $table      = 'audit_logs';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'hms_id',
        'action',
        'record_type',
        'request_payload',
        'response_payload',
        'status_code',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = false;

    protected $validationRules = [
        'action'      => 'required|max_length[100]',
        'record_type' => 'required|max_length[50]',
        'status_code' => 'required|integer',
    ];

    protected $validationMessages = [];
    protected $skipValidation     = false;

    /**
     * Record a new audit entry.
     */
    public function record(
        string $action,
        string $recordType,
        array $requestPayload,
        array|string $responsePayload,
        int $statusCode,
        string $hmsId = '',
        string $ipAddress = '',
        string $userAgent = ''
    ): bool {
        return $this->insert([
            'hms_id'           => $hmsId,
            'action'           => $action,
            'record_type'      => $recordType,
            'request_payload'  => json_encode($requestPayload),
            'response_payload' => is_array($responsePayload)
                ? json_encode($responsePayload)
                : $responsePayload,
            'status_code'      => $statusCode,
            'ip_address'       => $ipAddress,
            'user_agent'       => $userAgent,
        ]);
    }
}
