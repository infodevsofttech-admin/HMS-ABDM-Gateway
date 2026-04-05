<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * OpdModel
 *
 * Stores OPD (Out-Patient Department) encounter records.
 * Prescription and vitals are stored as JSON strings.
 */
class OpdModel extends Model
{
    protected $table            = 'opd';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'hospital_id',
        'doctor_id',
        'patient_id',
        'visit_date',
        'chief_complaint',
        'diagnosis',
        'prescription',
        'vitals',
        'abdm_encounter_id',
        'sync_status',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'hospital_id'     => 'required|integer',
        'doctor_id'       => 'required|integer',
        'patient_id'      => 'required|integer',
        'visit_date'      => 'required|valid_date',
        'chief_complaint' => 'required',
        'diagnosis'       => 'required',
        'sync_status'     => 'in_list[pending,synced,failed]',
    ];
}
