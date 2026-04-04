<?php

namespace App\Controllers;

use App\Models\MasterIdModel;
use App\Services\FhirMappingService;

/**
 * Patient Controller
 *
 * POST /sync/patient
 * Creates an ABHA (Ayushman Bharat Health Account) ID for a patient.
 */
class Patient extends ApiController
{
    protected MasterIdModel    $masterIdModel;
    protected FhirMappingService $fhirMapper;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ): void {
        parent::initController($request, $response, $logger);
        $this->masterIdModel = new MasterIdModel();
        $this->fhirMapper    = new FhirMappingService();
    }

    /**
     * Create an ABHA ID for a patient using Aadhaar-based registration.
     *
     * Expected JSON body:
     * {
     *   "hms_id": "PAT-001",
     *   "first_name": "Priya",
     *   "last_name": "Patel",
     *   "gender": "female",
     *   "dob": "1990-03-22",
     *   "phone": "9876543210",
     *   "aadhaar": "XXXX-XXXX-1234",
     *   "address": "45 Park Road",
     *   "city": "Pune",
     *   "state": "Maharashtra"
     * }
     */
    public function createAbha(): \CodeIgniter\HTTP\ResponseInterface
    {
        $data = $this->getJsonBody();

        $missing = $this->getMissingFields(['hms_id', 'first_name', 'last_name', 'dob', 'gender'], $data);
        if ($missing !== []) {
            return $this->errorResponse(
                'Missing required fields: ' . implode(', ', $missing),
                422
            );
        }

        $hmsId = $data['hms_id'];

        try {
            $fhirPatient = $this->fhirMapper->buildPatientResource($data);
            $abhaPayload = [
                'patient'      => $fhirPatient,
                'aadhaar'      => $data['aadhaar'] ?? '',
                'mobileNumber' => $data['phone'] ?? '',
            ];

            $result = $this->abdmApi->createAbha($abhaPayload);
            $abhaId = $result['body']['ABHANumber'] ?? $result['body']['abhaId'] ?? null;

            if ($abhaId !== null) {
                $this->masterIdModel->upsert($hmsId, 'patient', (string) $abhaId, 'ABHA_ID');
            }

            $this->audit('create_abha', 'patient', $data, $result['body'], $result['status'], $hmsId);

            return $this->successResponse([
                'hms_id'  => $hmsId,
                'abha_id' => $abhaId,
                'message' => 'ABHA ID created successfully.',
            ]);
        } catch (\Exception $e) {
            $queueId = $this->syncQueue->enqueue($hmsId, 'patient', $data);

            $this->audit('create_abha_queued', 'patient', $data, ['error' => $e->getMessage(), 'queue_id' => $queueId], 202, $hmsId);

            return $this->successResponse([
                'hms_id'   => $hmsId,
                'queue_id' => $queueId,
                'message'  => 'ABHA creation queued for retry due to ABDM connectivity issue.',
            ], 202);
        }
    }
}
