<?php

namespace App\Models;

use CodeIgniter\Model;

class AbdmHospital extends Model
{
    protected $table = 'abdm_hospitals';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';

    protected $allowedFields = [
        'hospital_name',
        'hfr_id',
        'gateway_mode',
        'contact_name',
        'contact_email',
        'contact_phone',
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
