<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * PathlabModel
 *
 * Stores pathology / laboratory test results.
 * Results are stored as a JSON string.
 */
class PathlabModel extends Model
{
    protected $table            = 'pathlab';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'hospital_id',
        'patient_id',
        'doctor_id',
        'test_name',
        'test_date',
        'results',
        'report_url',
        'abdm_document_id',
        'sync_status',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'hospital_id' => 'required|integer',
        'patient_id'  => 'required|integer',
        'test_name'   => 'required|max_length[255]',
        'test_date'   => 'required|valid_date',
        'sync_status' => 'in_list[pending,synced,failed]',
    ];
}
