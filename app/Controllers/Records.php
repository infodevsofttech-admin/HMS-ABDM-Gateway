<?php

namespace App\Controllers;

use App\Services\FhirMappingService;

/**
 * Records Controller
 *
 * Handles all health-record sync endpoints:
 *
 *   POST /sync/records/opd       → OPD encounters + prescriptions
 *   POST /sync/records/ipd       → IPD admissions + discharge summaries
 *   POST /sync/records/lab       → Pathology/lab results
 *   POST /sync/records/radiology → Imaging reports
 *   POST /sync/records/pharmacy  → Dispensing records
 */
class Records extends ApiController
{
    protected FhirMappingService $fhirMapper;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ): void {
        parent::initController($request, $response, $logger);
        $this->fhirMapper = new FhirMappingService();
    }

    // -------------------------------------------------------------------------
    // OPD
    // -------------------------------------------------------------------------

    /**
     * Push an OPD encounter with prescriptions.
     *
     * Required fields: hms_id, patient_hms_id, visit_date
     */
    public function pushOpd(): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->pushRecord(
            recordType: 'opd',
            required: ['hms_id', 'patient_hms_id', 'visit_date'],
            buildFhir: fn (array $d) => $this->fhirMapper->buildOpdEncounterBundle($d),
            action: 'push_opd'
        );
    }

    // -------------------------------------------------------------------------
    // IPD
    // -------------------------------------------------------------------------

    /**
     * Push an IPD admission and discharge summary.
     *
     * Required fields: hms_id, patient_hms_id, admission_date
     */
    public function pushIpd(): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->pushRecord(
            recordType: 'ipd',
            required: ['hms_id', 'patient_hms_id', 'admission_date'],
            buildFhir: fn (array $d) => $this->fhirMapper->buildIpdBundle($d),
            action: 'push_ipd'
        );
    }

    // -------------------------------------------------------------------------
    // Lab
    // -------------------------------------------------------------------------

    /**
     * Push lab / pathology results.
     *
     * Required fields: hms_id, patient_hms_id, report_id, report_date
     */
    public function pushLab(): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->pushRecord(
            recordType: 'lab',
            required: ['hms_id', 'patient_hms_id', 'report_id', 'report_date'],
            buildFhir: fn (array $d) => $this->fhirMapper->buildLabBundle($d),
            action: 'push_lab'
        );
    }

    // -------------------------------------------------------------------------
    // Radiology
    // -------------------------------------------------------------------------

    /**
     * Push radiology / imaging reports.
     *
     * Required fields: hms_id, patient_hms_id, report_id, report_date, modality
     */
    public function pushRadiology(): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->pushRecord(
            recordType: 'radiology',
            required: ['hms_id', 'patient_hms_id', 'report_id', 'report_date', 'modality'],
            buildFhir: fn (array $d) => $this->fhirMapper->buildRadiologyBundle($d),
            action: 'push_radiology'
        );
    }

    // -------------------------------------------------------------------------
    // Pharmacy
    // -------------------------------------------------------------------------

    /**
     * Push pharmacy dispensing records.
     *
     * Required fields: hms_id, patient_hms_id, dispensed_date
     */
    public function pushPharmacy(): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->pushRecord(
            recordType: 'pharmacy',
            required: ['hms_id', 'patient_hms_id', 'dispensed_date'],
            buildFhir: fn (array $d) => $this->fhirMapper->buildPharmacyBundle($d),
            action: 'push_pharmacy'
        );
    }

    // -------------------------------------------------------------------------
    // Shared push helper
    // -------------------------------------------------------------------------

    /**
     * Validate input, convert to FHIR, push to ABDM (with queue fallback).
     *
     * @param string   $recordType
     * @param string[] $required
     * @param callable $buildFhir  Takes the raw data array, returns FHIR bundle array.
     * @param string   $action     Audit log action label.
     */
    protected function pushRecord(
        string $recordType,
        array $required,
        callable $buildFhir,
        string $action
    ): \CodeIgniter\HTTP\ResponseInterface {
        $data = $this->getJsonBody();

        $missing = $this->getMissingFields($required, $data);
        if ($missing !== []) {
            return $this->errorResponse(
                'Missing required fields: ' . implode(', ', $missing),
                422
            );
        }

        $hmsId = $data['hms_id'];

        try {
            $fhirBundle = $buildFhir($data);
            $result     = $this->abdmApi->pushHealthRecord($fhirBundle);

            $this->audit($action, $recordType, $data, $result['body'], $result['status'], $hmsId);

            return $this->successResponse([
                'hms_id'   => $hmsId,
                'message'  => strtoupper($recordType) . ' record pushed successfully to ABDM.',
                'abdm_ref' => $result['body']['transactionId'] ?? null,
            ]);
        } catch (\Exception $e) {
            $fhirBundle = $buildFhir($data);
            $queueId    = $this->syncQueue->enqueue($hmsId, $recordType, $fhirBundle);

            $this->audit($action . '_queued', $recordType, $data, ['error' => $e->getMessage(), 'queue_id' => $queueId], 202, $hmsId);

            return $this->successResponse([
                'hms_id'   => $hmsId,
                'queue_id' => $queueId,
                'message'  => strtoupper($recordType) . ' record queued for retry due to ABDM connectivity issue.',
            ], 202);
        }
    }
}
