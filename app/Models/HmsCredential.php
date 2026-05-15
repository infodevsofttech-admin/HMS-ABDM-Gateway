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
        $url = rtrim((string) $credential->hms_api_endpoint, '/') . '/health';

        $curlHeaders = ['Content-Type: application/json'];

        if ($credential->hms_auth_type === 'api_key') {
            $curlHeaders[] = 'Authorization: Bearer ' . $credential->hms_api_key;
        } elseif ($credential->hms_auth_type === 'basic') {
            $curlHeaders[] = 'Authorization: Basic ' . base64_encode($credential->hms_username . ':' . $credential->hms_password);
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => $curlHeaders,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        curl_exec($ch);
        $httpCode  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError !== '') {
            return ['success' => false, 'status_code' => 0, 'message' => $curlError];
        }

        $success = $httpCode >= 200 && $httpCode < 300;
        return [
            'success'     => $success,
            'status_code' => $httpCode,
            'message'     => $success ? 'Connection successful' : "HTTP {$httpCode}",
        ];
    }
}
