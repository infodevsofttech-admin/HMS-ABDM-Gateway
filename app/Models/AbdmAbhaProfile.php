<?php

namespace App\Models;

use CodeIgniter\Model;

class AbdmAbhaProfile extends Model
{
    protected $table = 'abdm_abha_profiles';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';

    protected $allowedFields = [
        'hospital_id',
        'user_id',
        'abha_number',
        'abha_address',
        'full_name',
        'gender',
        'mobile',
        'date_of_birth',
        'year_of_birth',
        'status',
        'last_request_id',
        'last_verified_at',
        'profile_json',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
