<?php

namespace App\Models;

use CodeIgniter\Model;

class HmsCredential extends Model
{
    protected $table = 'hms_credentials';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';

    protected $allowedFields = [
        'hospital_id',
        'hms_name',
        'hms_api_endpoint',
        'hms_api_key',
        'hms_api_key_hash',
        'hms_auth_type',
        'hms_username',
        'hms_password',
        'is_verified',
        'last_verified_at',
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Get HMS credentials for a specific hospital
     */
    public function getByHospital(int $hospitalId)
    {
        return $this->where('hospital_id', $hospitalId)->findAll();
    }

    /**
     * Get active HMS credential for a hospital
     */
    public function getActiveByHospital(int $hospitalId)
    {
        return $this->where('hospital_id', $hospitalId)
            ->where('is_active', 1)
            ->first();
    }

    /**
     * Test HMS connection
     */
    public function testConnection(object $credential): array
    {
        try {
            $client = new \GuzzleHttp\Client([
                'timeout' => 10,
                'verify' => false,
            ]);

            $headers = ['Content-Type' => 'application/json'];

            if ($credential->hms_auth_type === 'api_key') {
                $headers['Authorization'] = 'Bearer ' . $credential->hms_api_key;
            } elseif ($credential->hms_auth_type === 'basic') {
                $auth = [$credential->hms_username, $credential->hms_password];
                $headers['Authorization'] = 'Basic ' . base64_encode("{$auth[0]}:{$auth[1]}");
            }

            $response = $client->get($credential->hms_api_endpoint . '/health', ['headers' => $headers]);

            return [
                'success' => $response->getStatusCode() >= 200 && $response->getStatusCode() < 300,
                'status_code' => $response->getStatusCode(),
                'message' => 'Connection successful',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'status_code' => 0,
                'message' => $e->getMessage(),
            ];
        }
    }
}
