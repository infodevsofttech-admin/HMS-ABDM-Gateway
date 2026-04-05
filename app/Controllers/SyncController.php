<?php

namespace App\Controllers;

use App\Models\AuditLogModel;
use App\Models\DoctorModel;
use App\Models\HospitalModel;
use App\Models\IpdModel;
use App\Models\PathlabModel;
use App\Models\PatientModel;
use App\Models\PharmacyModel;
use App\Models\RadiologyModel;
use App\Models\OpdModel;
use App\Models\SyncQueueModel;
use App\Services\AbdmgatewayService;
use App\Services\FhirMapper;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * SyncController
 *
 * Handles all /api/v1/sync/* endpoints.  Each method validates the
 * incoming HMS payload, persists it locally, enqueues an async ABDM
 * call, and returns the result.
 */
class SyncController extends BaseController
{
    protected HospitalModel $hospitalModel;
    protected DoctorModel $doctorModel;
    protected PatientModel $patientModel;
    protected OpdModel $opdModel;
    protected IpdModel $ipdModel;
    protected PathlabModel $pathlabModel;
    protected RadiologyModel $radiologyModel;
    protected PharmacyModel $pharmacyModel;
    protected SyncQueueModel $syncQueue;
    protected AuditLogModel $auditLog;
    protected AbdmgatewayService $abdm;
    protected FhirMapper $fhirMapper;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ): void {
        parent::initController($request, $response, $logger);

        $this->hospitalModel   = new HospitalModel();
        $this->doctorModel     = new DoctorModel();
        $this->patientModel    = new PatientModel();
        $this->opdModel        = new OpdModel();
        $this->ipdModel        = new IpdModel();
        $this->pathlabModel    = new PathlabModel();
        $this->radiologyModel  = new RadiologyModel();
        $this->pharmacyModel   = new PharmacyModel();
        $this->syncQueue       = new SyncQueueModel();
        $this->auditLog        = new AuditLogModel();
        $this->abdm            = new AbdmgatewayService();
        $this->fhirMapper      = new FhirMapper();
    }

    // -----------------------------------------------------------------------
    // POST /api/v1/sync/hospital
    // -----------------------------------------------------------------------
    public function hospital(): ResponseInterface
    {
        $rules = [
            'name'    => 'required|max_length[255]',
            'address' => 'required',
            'city'    => 'required|max_length[100]',
            'state'   => 'required|max_length[100]',
            'pincode' => 'required|max_length[10]',
            'phone'   => 'required|max_length[20]',
        ];

        if (! $this->validate($rules)) {
            return $this->jsonError(422, $this->validator->getErrors());
        }

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        // Upsert hospital record
        $existing = $this->hospitalModel->where('name', $data['name'])->first();
        if ($existing) {
            $this->hospitalModel->update($existing['id'], $data);
            $hospitalId = $existing['id'];
        } else {
            $hospitalId = $this->hospitalModel->insert($data);
        }

        // Push to ABDM HFR via queue
        $queueId = $this->syncQueue->enqueue('hospital', $hospitalId, 'register', $data);

        // Attempt immediate registration
        $response = $this->abdm->registerHospital($data);

        if ($response['success']) {
            $this->hospitalModel->update($hospitalId, ['hfr_id' => $response['hfr_id'] ?? null]);
            $this->syncQueue->markCompleted($queueId, $response);
        } else {
            $this->syncQueue->markFailed($queueId, $response['error'] ?? 'ABDM call failed');
        }

        $this->auditLog->record(
            $this->request->hospital_id ?? null,
            'sync_hospital',
            'hospital',
            $hospitalId,
            $this->request->getIPAddress(),
            $data,
            $response,
            $response['success'] ? 'success' : 'failed'
        );

        return $this->response->setJSON([
            'status'      => $response['success'] ? 'success' : 'queued',
            'hospital_id' => $hospitalId,
            'hfr_id'      => $response['hfr_id'] ?? null,
            'queue_id'    => $queueId,
        ]);
    }

    // -----------------------------------------------------------------------
    // POST /api/v1/sync/doctor
    // -----------------------------------------------------------------------
    public function doctor(): ResponseInterface
    {
        $rules = [
            'hospital_id'     => 'required|integer',
            'name'            => 'required|max_length[255]',
            'specialization'  => 'required|max_length[100]',
            'qualification'   => 'required|max_length[255]',
            'phone'           => 'required|max_length[20]',
        ];

        if (! $this->validate($rules)) {
            return $this->jsonError(422, $this->validator->getErrors());
        }

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        $doctorId = $this->doctorModel->insert($data);
        $queueId  = $this->syncQueue->enqueue('doctor', $doctorId, 'register', $data);

        $response = $this->abdm->registerDoctor($data);

        if ($response['success']) {
            $this->doctorModel->update($doctorId, ['hpr_id' => $response['hpr_id'] ?? null]);
            $this->syncQueue->markCompleted($queueId, $response);
        } else {
            $this->syncQueue->markFailed($queueId, $response['error'] ?? 'ABDM call failed');
        }

        $this->auditLog->record(
            null,
            'sync_doctor',
            'doctor',
            $doctorId,
            $this->request->getIPAddress(),
            $data,
            $response,
            $response['success'] ? 'success' : 'failed'
        );

        return $this->response->setJSON([
            'status'    => $response['success'] ? 'success' : 'queued',
            'doctor_id' => $doctorId,
            'hpr_id'    => $response['hpr_id'] ?? null,
            'queue_id'  => $queueId,
        ]);
    }

    // -----------------------------------------------------------------------
    // POST /api/v1/sync/patient
    // -----------------------------------------------------------------------
    public function patient(): ResponseInterface
    {
        $rules = [
            'hospital_id' => 'required|integer',
            'name'        => 'required|max_length[255]',
            'dob'         => 'required|valid_date',
            'gender'      => 'required|in_list[M,F,O]',
            'phone'       => 'required|max_length[20]',
        ];

        if (! $this->validate($rules)) {
            return $this->jsonError(422, $this->validator->getErrors());
        }

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        $patientId = $this->patientModel->insert($data);
        $queueId   = $this->syncQueue->enqueue('patient', $patientId, 'create_abha', $data);

        $response = $this->abdm->createAbha($data);

        if ($response['success']) {
            $this->patientModel->update($patientId, ['abha_id' => $response['abha_id'] ?? null]);
            $this->syncQueue->markCompleted($queueId, $response);
        } else {
            $this->syncQueue->markFailed($queueId, $response['error'] ?? 'ABDM call failed');
        }

        $this->auditLog->record(
            null,
            'sync_patient',
            'patient',
            $patientId,
            $this->request->getIPAddress(),
            $data,
            $response,
            $response['success'] ? 'success' : 'failed'
        );

        return $this->response->setJSON([
            'status'     => $response['success'] ? 'success' : 'queued',
            'patient_id' => $patientId,
            'abha_id'    => $response['abha_id'] ?? null,
            'queue_id'   => $queueId,
        ]);
    }

    // -----------------------------------------------------------------------
    // POST /api/v1/sync/opd
    // -----------------------------------------------------------------------
    public function opd(): ResponseInterface
    {
        $rules = [
            'hospital_id'     => 'required|integer',
            'doctor_id'       => 'required|integer',
            'patient_id'      => 'required|integer',
            'visit_date'      => 'required|valid_date',
            'chief_complaint' => 'required',
            'diagnosis'       => 'required',
        ];

        if (! $this->validate($rules)) {
            return $this->jsonError(422, $this->validator->getErrors());
        }

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        // Encode JSON columns
        foreach (['prescription', 'vitals'] as $col) {
            if (isset($data[$col]) && is_array($data[$col])) {
                $data[$col] = json_encode($data[$col]);
            }
        }

        $opdId   = $this->opdModel->insert($data);
        $queueId = $this->syncQueue->enqueue('opd', $opdId, 'push_encounter', $data);

        $fhirBundle = $this->fhirMapper->mapOpd($data);
        $response   = $this->abdm->pushEncounter($fhirBundle);

        if ($response['success']) {
            $this->opdModel->update($opdId, [
                'abdm_encounter_id' => $response['encounter_id'] ?? null,
                'sync_status'       => 'synced',
            ]);
            $this->syncQueue->markCompleted($queueId, $response);
        } else {
            $this->opdModel->update($opdId, ['sync_status' => 'failed']);
            $this->syncQueue->markFailed($queueId, $response['error'] ?? 'ABDM call failed');
        }

        return $this->response->setJSON([
            'status'        => $response['success'] ? 'success' : 'queued',
            'opd_id'        => $opdId,
            'encounter_id'  => $response['encounter_id'] ?? null,
            'queue_id'      => $queueId,
        ]);
    }

    // -----------------------------------------------------------------------
    // POST /api/v1/sync/ipd
    // -----------------------------------------------------------------------
    public function ipd(): ResponseInterface
    {
        $rules = [
            'hospital_id'      => 'required|integer',
            'doctor_id'        => 'required|integer',
            'patient_id'       => 'required|integer',
            'admission_date'   => 'required|valid_date',
            'admission_reason' => 'required',
        ];

        if (! $this->validate($rules)) {
            return $this->jsonError(422, $this->validator->getErrors());
        }

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        if (isset($data['treatment']) && is_array($data['treatment'])) {
            $data['treatment'] = json_encode($data['treatment']);
        }

        $ipdId   = $this->ipdModel->insert($data);
        $queueId = $this->syncQueue->enqueue('ipd', $ipdId, 'push_encounter', $data);

        $fhirBundle = $this->fhirMapper->mapIpd($data);
        $response   = $this->abdm->pushEncounter($fhirBundle);

        if ($response['success']) {
            $this->ipdModel->update($ipdId, [
                'abdm_encounter_id' => $response['encounter_id'] ?? null,
                'sync_status'       => 'synced',
            ]);
            $this->syncQueue->markCompleted($queueId, $response);
        } else {
            $this->ipdModel->update($ipdId, ['sync_status' => 'failed']);
            $this->syncQueue->markFailed($queueId, $response['error'] ?? 'ABDM call failed');
        }

        return $this->response->setJSON([
            'status'       => $response['success'] ? 'success' : 'queued',
            'ipd_id'       => $ipdId,
            'encounter_id' => $response['encounter_id'] ?? null,
            'queue_id'     => $queueId,
        ]);
    }

    // -----------------------------------------------------------------------
    // POST /api/v1/sync/pathlab
    // -----------------------------------------------------------------------
    public function pathlab(): ResponseInterface
    {
        $rules = [
            'hospital_id' => 'required|integer',
            'patient_id'  => 'required|integer',
            'test_name'   => 'required|max_length[255]',
            'test_date'   => 'required|valid_date',
        ];

        if (! $this->validate($rules)) {
            return $this->jsonError(422, $this->validator->getErrors());
        }

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        if (isset($data['results']) && is_array($data['results'])) {
            $data['results'] = json_encode($data['results']);
        }

        $labId   = $this->pathlabModel->insert($data);
        $queueId = $this->syncQueue->enqueue('pathlab', $labId, 'push_lab_result', $data);

        $fhirBundle = $this->fhirMapper->mapPathlab($data);
        $response   = $this->abdm->pushDocument($fhirBundle);

        if ($response['success']) {
            $this->pathlabModel->update($labId, [
                'abdm_document_id' => $response['document_id'] ?? null,
                'sync_status'      => 'synced',
            ]);
            $this->syncQueue->markCompleted($queueId, $response);
        } else {
            $this->pathlabModel->update($labId, ['sync_status' => 'failed']);
            $this->syncQueue->markFailed($queueId, $response['error'] ?? 'ABDM call failed');
        }

        return $this->response->setJSON([
            'status'      => $response['success'] ? 'success' : 'queued',
            'lab_id'      => $labId,
            'document_id' => $response['document_id'] ?? null,
            'queue_id'    => $queueId,
        ]);
    }

    // -----------------------------------------------------------------------
    // POST /api/v1/sync/radiology
    // -----------------------------------------------------------------------
    public function radiology(): ResponseInterface
    {
        $rules = [
            'hospital_id' => 'required|integer',
            'patient_id'  => 'required|integer',
            'modality'    => 'required|max_length[50]',
            'body_part'   => 'required|max_length[100]',
            'study_date'  => 'required|valid_date',
        ];

        if (! $this->validate($rules)) {
            return $this->jsonError(422, $this->validator->getErrors());
        }

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        $radId   = $this->radiologyModel->insert($data);
        $queueId = $this->syncQueue->enqueue('radiology', $radId, 'push_imaging_report', $data);

        $fhirBundle = $this->fhirMapper->mapRadiology($data);
        $response   = $this->abdm->pushDocument($fhirBundle);

        if ($response['success']) {
            $this->radiologyModel->update($radId, [
                'abdm_document_id' => $response['document_id'] ?? null,
                'sync_status'      => 'synced',
            ]);
            $this->syncQueue->markCompleted($queueId, $response);
        } else {
            $this->radiologyModel->update($radId, ['sync_status' => 'failed']);
            $this->syncQueue->markFailed($queueId, $response['error'] ?? 'ABDM call failed');
        }

        return $this->response->setJSON([
            'status'      => $response['success'] ? 'success' : 'queued',
            'rad_id'      => $radId,
            'document_id' => $response['document_id'] ?? null,
            'queue_id'    => $queueId,
        ]);
    }

    // -----------------------------------------------------------------------
    // POST /api/v1/sync/pharmacy
    // -----------------------------------------------------------------------
    public function pharmacy(): ResponseInterface
    {
        $rules = [
            'hospital_id'   => 'required|integer',
            'patient_id'    => 'required|integer',
            'dispense_date' => 'required|valid_date',
            'medications'   => 'required',
        ];

        if (! $this->validate($rules)) {
            return $this->jsonError(422, $this->validator->getErrors());
        }

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        if (isset($data['medications']) && is_array($data['medications'])) {
            $data['medications'] = json_encode($data['medications']);
        }

        $rxId    = $this->pharmacyModel->insert($data);
        $queueId = $this->syncQueue->enqueue('pharmacy', $rxId, 'push_dispense', $data);

        $fhirBundle = $this->fhirMapper->mapPharmacy($data);
        $response   = $this->abdm->pushDocument($fhirBundle);

        if ($response['success']) {
            $this->pharmacyModel->update($rxId, [
                'abdm_document_id' => $response['document_id'] ?? null,
                'sync_status'      => 'synced',
            ]);
            $this->syncQueue->markCompleted($queueId, $response);
        } else {
            $this->pharmacyModel->update($rxId, ['sync_status' => 'failed']);
            $this->syncQueue->markFailed($queueId, $response['error'] ?? 'ABDM call failed');
        }

        return $this->response->setJSON([
            'status'      => $response['success'] ? 'success' : 'queued',
            'rx_id'       => $rxId,
            'document_id' => $response['document_id'] ?? null,
            'queue_id'    => $queueId,
        ]);
    }

    // -----------------------------------------------------------------------
    // Helper
    // -----------------------------------------------------------------------
    private function jsonError(int $code, array|string $errors): ResponseInterface
    {
        return $this->response
            ->setStatusCode($code)
            ->setJSON(['status' => 'error', 'errors' => $errors]);
    }
}
