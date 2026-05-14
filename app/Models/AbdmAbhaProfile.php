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
        'phr_address',
        'full_name',
        'first_name',
        'middle_name',
        'last_name',
        'gender',
        'mobile',
        'email',
        'mobile_verified',
        'date_of_birth',
        'year_of_birth',
        'address',
        'pin_code',
        'state_code',
        'state_name',
        'district_code',
        'district_name',
        'abha_type',
        'abha_status',
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
