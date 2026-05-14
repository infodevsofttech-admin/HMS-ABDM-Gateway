<?php

namespace App\Models;

use CodeIgniter\Model;

class AbdmAuditTrail extends Model
{
    protected $table = 'abdm_audit_trail';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';

    protected $allowedFields = [
        'request_id',
        'action',
        'patient_abha',
        'consent_id',
        'hi_types',
        'action_status',
        'details',
        'performed_by',
        'created_at',
    ];

    protected $useTimestamps = false;
    protected $createdField = 'created_at';

    // Indexes: patient_abha, consent_id, created_at
}
