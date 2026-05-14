<?php

namespace App\Models;

use CodeIgniter\Model;

class AbdmHospitalUser extends Model
{
    protected $table = 'abdm_hospital_users';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';

    protected $allowedFields = [
        'hospital_id',
        'username',
        'password_hash',
        'api_token',
        'role',
        'is_active',
        'last_login_at',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
