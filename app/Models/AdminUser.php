<?php

namespace App\Models;

use CodeIgniter\Model;

class AdminUser extends Model
{
    protected $table      = 'admin_users';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'username',
        'password_hash',
        'full_name',
        'email',
        'role',
        'is_active',
        'last_login_at',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
