<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * HospitalModel
 *
 * Master data for hospitals registered in this gateway.
 * hfr_id is populated after successful HFR registration via ABDM.
 */
class HospitalModel extends Model
{
    protected $table            = 'hospitals';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'name',
        'hfr_id',
        'address',
        'city',
        'state',
        'pincode',
        'phone',
        'email',
        'status',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'name'   => 'required|max_length[255]',
        'phone'  => 'required|max_length[20]',
        'status' => 'in_list[active,inactive]',
    ];
}
