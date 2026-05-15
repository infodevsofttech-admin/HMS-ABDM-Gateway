<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class HospitalHprProfessional extends Model
{
    protected $table      = 'hospital_hpr_professionals';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'hospital_id',
        'name',
        'hpr_id',
        'registration_number',
        'specialization',
        'specialization_code',
        'department',
        'designation',
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Return all active professionals for a hospital, ordered by name.
     */
    public function forHospital(int $hospitalId): array
    {
        return $this->where('hospital_id', $hospitalId)
            ->orderBy('name', 'ASC')
            ->findAll();
    }

    /**
     * Validate HPR ID format.
     * Sandbox:    anything@hpr.abdm
     * Production: 14-digit number or handle@hpr.abdm
     */
    public static function validateHprId(string $hprId): bool
    {
        $hprId = trim($hprId);
        if ($hprId === '') {
            return false;
        }
        // @hpr.abdm handle format
        if (preg_match('/^[a-zA-Z0-9._\-]+@hpr\.abdm$/i', $hprId)) {
            return true;
        }
        // 14-digit HPR number
        if (preg_match('/^\d{14}$/', $hprId)) {
            return true;
        }
        return false;
    }
}
