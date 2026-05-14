<?php

namespace App\Models;

use CodeIgniter\Model;

class HospitalRegistration extends Model
{
    protected $table         = 'abdm_hospital_registrations';
    protected $primaryKey    = 'id';
    protected $returnType    = 'object';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'hospital_name', 'hfr_id', 'contact_name', 'contact_email',
        'contact_phone', 'city', 'state', 'description',
        'desired_username', 'password_hash',
        'status', 'admin_notes', 'reviewed_by', 'reviewed_at',
    ];

    public function pending(): array
    {
        return $this->where('status', 'pending')->orderBy('created_at', 'DESC')->findAll();
    }

    public function countPending(): int
    {
        return $this->where('status', 'pending')->countAllResults();
    }
}
