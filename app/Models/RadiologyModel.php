<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * RadiologyModel
 *
 * Stores radiology imaging study records (X-Ray, CT, MRI, etc.).
 */
class RadiologyModel extends Model
{
    protected $table            = 'radiology';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'hospital_id',
        'patient_id',
        'doctor_id',
        'modality',
        'body_part',
        'study_date',
        'findings',
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
        'modality'    => 'required|max_length[50]',
        'body_part'   => 'required|max_length[100]',
        'study_date'  => 'required|valid_date',
        'sync_status' => 'in_list[pending,synced,failed]',
    ];
}
