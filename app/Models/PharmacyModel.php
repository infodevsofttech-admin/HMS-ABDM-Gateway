<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * PharmacyModel
 *
 * Stores pharmacy dispensing records.
 * Medications list is stored as a JSON string.
 */
class PharmacyModel extends Model
{
    protected $table            = 'pharmacy';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'hospital_id',
        'patient_id',
        'doctor_id',
        'dispense_date',
        'medications',
        'total_amount',
        'abdm_document_id',
        'sync_status',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'hospital_id'   => 'required|integer',
        'patient_id'    => 'required|integer',
        'dispense_date' => 'required|valid_date',
        'medications'   => 'required',
        'sync_status'   => 'in_list[pending,synced,failed]',
    ];
}
