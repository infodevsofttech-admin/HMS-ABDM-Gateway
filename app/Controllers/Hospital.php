<?php

namespace App\Controllers;

use App\Models\MasterIdModel;
use App\Services\FhirMappingService;

/**
 * Hospital Controller
 *
 * POST /sync/hospital
 * Registers a hospital facility in the ABDM Health Facility Registry (HFR).
 */
class Hospital extends ApiController
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
     * Register a hospital in the HFR.
     *
     * Expected JSON body:
     * {
     *   "hms_id": "HOSP-001",
     *   "name": "City General Hospital",
     *   "address": "123 Main St",
     *   "city": "Mumbai",
     *   "state": "Maharashtra",
     *   "phone": "022-12345678",
     *   "facility_type": "HOSPITAL",
     *   "ownership_type": "PRIVATE"
     * }
     */
    public function register(): \CodeIgniter\HTTP\ResponseInterface
    {
        $data = $this->getJsonBody();

        $missing = $this->getMissingFields(['hms_id', 'name', 'state'], $data);
        if ($missing !== []) {
            return $this->errorResponse(
                'Missing required fields: ' . implode(', ', $missing),
                422
            );
        }

        $hmsId = $data['hms_id'];

        try {
            $fhirOrg   = $this->fhirMapper->buildOrganizationResource($data);
            $hfrPayload = [
                'facility'     => $fhirOrg,
                'facilityType' => $data['facility_type'] ?? 'HOSPITAL',
                'ownership'    => $data['ownership_type'] ?? 'PRIVATE',
            ];

            $result = $this->abdmApi->registerHospital($hfrPayload);
            $hfrId  = $result['body']['facilityId'] ?? null;

            if ($hfrId !== null) {
                $this->masterIdModel->upsert($hmsId, 'hospital', $hfrId, 'HFR_ID');
            }

            $this->audit('register_hospital', 'hospital', $data, $result['body'], $result['status'], $hmsId);

            return $this->successResponse([
                'hms_id' => $hmsId,
                'hfr_id' => $hfrId,
                'message' => 'Hospital registered successfully in HFR.',
            ]);
        } catch (\Exception $e) {
            $queueId = $this->syncQueue->enqueue($hmsId, 'hospital', $data);

            $this->audit('register_hospital_queued', 'hospital', $data, ['error' => $e->getMessage(), 'queue_id' => $queueId], 202, $hmsId);

            return $this->successResponse([
                'hms_id'   => $hmsId,
                'queue_id' => $queueId,
                'message'  => 'Hospital registration queued for retry due to ABDM connectivity issue.',
            ], 202);
        }
    }
}
