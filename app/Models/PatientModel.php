<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * PatientModel
 *
 * Master data for patients.
 * abha_id is populated after successful ABHA creation via ABDM.
 */
class PatientModel extends Model
{
    protected $table            = 'patients';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'hospital_id',
        'name',
        'abha_id',
        'dob',
        'gender',
        'phone',
        'address',
        'blood_group',
        'status',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'hospital_id' => 'required|integer',
        'name'        => 'required|max_length[255]',
        'dob'         => 'required|valid_date',
        'gender'      => 'required|in_list[M,F,O]',
        'phone'       => 'required|max_length[20]',
        'status'      => 'in_list[active,inactive]',
    ];
}
