<?php

namespace App\Models;

use CodeIgniter\Model;

class AbdmTestSubmissionLog extends Model
{
    protected $table = 'abdm_test_submission_logs';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';

    protected $allowedFields = [
        'request_id',
        'hospital_id',
        'user_id',
        'event_type',
        'endpoint',
        'http_status',
        'request_payload',
        'response_payload',
        'created_at',
    ];

    protected $useTimestamps = false;
    protected $createdField = 'created_at';
}
