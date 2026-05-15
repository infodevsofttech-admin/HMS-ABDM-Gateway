<?php

namespace App\Models;

use CodeIgniter\Model;

class AbdmRequestLog extends Model
{
    protected $table = 'abdm_request_logs';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';

    protected $allowedFields = [
        'request_id',
        'method',
        'endpoint',
        'status_code',
        'response_time_ms',
        'ip_address',
        'authorization_status',
        'error_message',
        'response_body',
        'created_at',
    ];

    protected $useTimestamps = false;
    protected $createdField = 'created_at';

    // Indexes: request_id (unique), endpoint, status_code, created_at
}
