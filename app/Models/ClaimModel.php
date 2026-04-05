<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * ClaimModel
 *
 * Stores insurance claim data including FHIR bundle JSON,
 * NHCX claim reference, patient/hospital/doctor identifiers,
 * policy details, itemized bill, and discharge summary.
 */
class ClaimModel extends Model
{
    protected $table            = 'claims';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'hospital_id',
        'doctor_id',
        'patient_id',
        'claim_number',
        'policy_number',
        'insurer_name',
        'tpa_name',
        'claim_type',
        'admission_date',
        'discharge_date',
        'diagnosis_codes',
        'procedure_codes',
        'itemized_bill',
        'total_amount',
        'claim_amount',
        'discharge_summary',
        'status',
        'nhcx_claim_id',
        'fhir_bundle',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'hospital_id'   => 'required|integer',
        'doctor_id'     => 'required|integer',
        'patient_id'    => 'required|integer',
        'policy_number' => 'required|max_length[100]',
        'insurer_name'  => 'required|max_length[255]',
        'claim_type'    => 'required|in_list[cashless,reimbursement]',
        'admission_date'=> 'required|valid_date',
        'total_amount'  => 'required|decimal',
        'claim_amount'  => 'required|decimal',
        'status'        => 'in_list[pending,submitted,approved,rejected,failed]',
    ];

    /**
     * Return all claims for a given patient (by ABHA ID or patient_id).
     *
     * @return array<int, array<string, mixed>>
     */
    public function findByPatient(int $patientId): array
    {
        return $this->where('patient_id', $patientId)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }

    /**
     * Return all claims for a given hospital.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findByHospital(int $hospitalId): array
    {
        return $this->where('hospital_id', $hospitalId)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }
}
