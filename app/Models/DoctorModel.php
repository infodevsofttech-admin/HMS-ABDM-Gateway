<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * DoctorModel
 *
 * Master data for doctors/health professionals.
 * hpr_id is populated after successful HPR registration via ABDM.
 */
class DoctorModel extends Model
{
    protected $table            = 'doctors';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'hospital_id',
        'name',
        'hpr_id',
        'specialization',
        'qualification',
        'phone',
        'email',
        'status',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'hospital_id'    => 'required|integer',
        'name'           => 'required|max_length[255]',
        'specialization' => 'required|max_length[100]',
        'status'         => 'in_list[active,inactive]',
    ];
}
