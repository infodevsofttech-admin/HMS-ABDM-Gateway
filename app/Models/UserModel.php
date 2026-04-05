<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * UserModel
 *
 * Represents HMS instance users who authenticate against this gateway.
 * Roles: admin | doctor | lab | pharmacy | insurance
 */
class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = [
        'username',
        'password',
        'role',
        'hospital_id',
        'api_token',
        'token_expires_at',
        'status',
    ];

    protected bool $allowEmptyInserts = false;

    // Dates
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules = [
        'username' => 'required|min_length[3]|max_length[100]|is_unique[users.username,id,{id}]',
        'password' => 'required|min_length[6]',
        'role'     => 'required|in_list[admin,doctor,lab,pharmacy,insurance]',
        'status'   => 'in_list[active,inactive]',
    ];

    protected $beforeInsert = ['hashPassword'];
    protected $beforeUpdate = ['hashPassword'];

    protected function hashPassword(array $data): array
    {
        if (isset($data['data']['password'])) {
            $data['data']['password'] = password_hash($data['data']['password'], PASSWORD_BCRYPT);
        }

        return $data;
    }

    /**
     * Find a user by username (case-insensitive).
     *
     * @return array<string, mixed>|null
     */
    public function findByUsername(string $username): ?array
    {
        return $this->where('LOWER(username)', strtolower($username))->first();
    }
}
