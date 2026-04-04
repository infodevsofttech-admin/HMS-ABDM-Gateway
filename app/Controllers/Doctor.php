<?php

namespace App\Controllers;

use App\Models\MasterIdModel;
use App\Services\FhirMappingService;

/**
 * Doctor Controller
 *
 * POST /sync/doctor
 * Registers a health professional in the ABDM Health Professional Registry (HPR).
 */
class Doctor extends ApiController
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
     * Register a doctor in the HPR.
     *
     * Expected JSON body:
     * {
     *   "hms_id": "DOC-001",
     *   "first_name": "Rahul",
     *   "last_name": "Sharma",
     *   "gender": "male",
     *   "dob": "1980-05-15",
     *   "qualification": "MBBS, MD",
     *   "registration_number": "MH-12345",
     *   "council": "Maharashtra Medical Council",
     *   "speciality": "General Medicine"
     * }
     */
    public function register(): \CodeIgniter\HTTP\ResponseInterface
    {
        $data = $this->getJsonBody();

        $missing = $this->getMissingFields(['hms_id', 'first_name', 'last_name', 'registration_number'], $data);
        if ($missing !== []) {
            return $this->errorResponse(
                'Missing required fields: ' . implode(', ', $missing),
                422
            );
        }

        $hmsId = $data['hms_id'];

        try {
            $fhirPractitioner = $this->fhirMapper->buildPractitionerResource($data);
            $hprPayload       = [
                'professional'       => $fhirPractitioner,
                'registrationNumber' => $data['registration_number'],
                'council'            => $data['council'] ?? '',
                'speciality'         => $data['speciality'] ?? '',
            ];

            $result = $this->abdmApi->registerDoctor($hprPayload);
            $hprId  = $result['body']['hprId'] ?? null;

            if ($hprId !== null) {
                $this->masterIdModel->upsert($hmsId, 'doctor', $hprId, 'HPR_ID');
            }

            $this->audit('register_doctor', 'doctor', $data, $result['body'], $result['status'], $hmsId);

            return $this->successResponse([
                'hms_id' => $hmsId,
                'hpr_id' => $hprId,
                'message' => 'Doctor registered successfully in HPR.',
            ]);
        } catch (\Exception $e) {
            $queueId = $this->syncQueue->enqueue($hmsId, 'doctor', $data);

            $this->audit('register_doctor_queued', 'doctor', $data, ['error' => $e->getMessage(), 'queue_id' => $queueId], 202, $hmsId);

            return $this->successResponse([
                'hms_id'   => $hmsId,
                'queue_id' => $queueId,
                'message'  => 'Doctor registration queued for retry due to ABDM connectivity issue.',
            ], 202);
        }
    }
}
