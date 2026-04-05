<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * IpdModel
 *
 * Stores IPD (In-Patient Department) admission/discharge records.
 * Treatment details are stored as a JSON string.
 */
class IpdModel extends Model
{
    protected $table            = 'ipd';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'hospital_id',
        'doctor_id',
        'patient_id',
        'admission_date',
        'discharge_date',
        'ward',
        'bed_number',
        'admission_reason',
        'diagnosis',
        'treatment',
        'discharge_summary',
        'abdm_encounter_id',
        'sync_status',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'hospital_id'      => 'required|integer',
        'doctor_id'        => 'required|integer',
        'patient_id'       => 'required|integer',
        'admission_date'   => 'required|valid_date',
        'admission_reason' => 'required',
        'sync_status'      => 'in_list[pending,synced,failed]',
    ];
}
