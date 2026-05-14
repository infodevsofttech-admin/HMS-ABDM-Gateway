<?php

namespace App\Models;

use CodeIgniter\Model;

class AbdmBundle extends Model
{
    protected $table = 'abdm_bundles';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';

    protected $allowedFields = [
        'bundle_id',
        'consent_id',
        'hi_type',
        'bundle_hash',
        'push_status',
        'push_timestamp',
        'response_status',
        'response_body',
        'retry_count',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Indexes: bundle_id (unique), consent_id, push_status, created_at
}
