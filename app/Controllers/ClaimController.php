<?php

namespace App\Controllers;

use App\Models\AuditLogModel;
use App\Models\ClaimModel;
use App\Services\FhirMapper;
use App\Services\NhcxService;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * ClaimController
 *
 * Handles insurance claim submission and status inquiry via NHCX.
 *
 * POST /api/v1/claims/submit  – package and push a claim to NHCX
 * GET  /api/v1/claims/status/{id} – fetch claim status from NHCX/TPA
 */
class ClaimController extends BaseController
{
    protected ClaimModel $claimModel;
    protected NhcxService $nhcx;
    protected FhirMapper $fhirMapper;
    protected AuditLogModel $auditLog;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ): void {
        parent::initController($request, $response, $logger);

        $this->claimModel  = new ClaimModel();
        $this->nhcx        = new NhcxService();
        $this->fhirMapper  = new FhirMapper();
        $this->auditLog    = new AuditLogModel();
    }

    /**
     * POST /api/v1/claims/submit
     *
     * Expected JSON body:
     * {
     *   "hospital_id":       int,
     *   "doctor_id":         int,
     *   "patient_id":        int,
     *   "policy_number":     string,
     *   "insurer_name":      string,
     *   "tpa_name":          string|null,
     *   "claim_type":        "cashless"|"reimbursement",
     *   "admission_date":    "YYYY-MM-DD",
     *   "discharge_date":    "YYYY-MM-DD"|null,
     *   "diagnosis_codes":   [string, ...],
     *   "procedure_codes":   [string, ...],
     *   "itemized_bill":     [{...}, ...],
     *   "total_amount":      float,
     *   "claim_amount":      float,
     *   "discharge_summary": string|null
     * }
     */
    public function submit(): ResponseInterface
    {
        $rules = [
            'hospital_id'    => 'required|integer',
            'doctor_id'      => 'required|integer',
            'patient_id'     => 'required|integer',
            'policy_number'  => 'required|max_length[100]',
            'insurer_name'   => 'required|max_length[255]',
            'claim_type'     => 'required|in_list[cashless,reimbursement]',
            'admission_date' => 'required|valid_date',
            'total_amount'   => 'required|decimal',
            'claim_amount'   => 'required|decimal',
        ];

        if (! $this->validate($rules)) {
            return $this->jsonError(422, $this->validator->getErrors());
        }

        $data = $this->request->getJSON(true) ?? $this->request->getPost();

        // Encode array columns to JSON strings for storage
        foreach (['diagnosis_codes', 'procedure_codes', 'itemized_bill'] as $col) {
            if (isset($data[$col]) && is_array($data[$col])) {
                $data[$col] = json_encode($data[$col]);
            }
        }

        // Build FHIR Claim resource JSON
        $fhirClaim          = $this->fhirMapper->mapClaim($data);
        $data['fhir_bundle'] = json_encode($fhirClaim);
        $data['status']      = 'pending';
        $data['claim_number'] = $this->generateClaimNumber();

        $claimId = $this->claimModel->insert($data);

        // Submit to NHCX
        $response = $this->nhcx->submitClaim($fhirClaim, $data);

        if ($response['success']) {
            $this->claimModel->update($claimId, [
                'nhcx_claim_id' => $response['nhcx_claim_id'] ?? null,
                'status'        => 'submitted',
            ]);
        } else {
            $this->claimModel->update($claimId, ['status' => 'failed']);
        }

        $this->auditLog->record(
            null,
            'claim_submit',
            'claim',
            $claimId,
            $this->request->getIPAddress(),
            $data,
            $response,
            $response['success'] ? 'success' : 'failed'
        );

        return $this->response
            ->setStatusCode($response['success'] ? 200 : 202)
            ->setJSON([
                'status'        => $response['success'] ? 'submitted' : 'queued',
                'claim_id'      => $claimId,
                'claim_number'  => $data['claim_number'],
                'nhcx_claim_id' => $response['nhcx_claim_id'] ?? null,
            ]);
    }

    /**
     * GET /api/v1/claims/status/{id}
     *
     * Fetches the current status of a claim from NHCX/TPA.
     */
    public function status(int $id): ResponseInterface
    {
        $claim = $this->claimModel->find($id);

        if ($claim === null) {
            return $this->jsonError(404, 'Claim not found');
        }

        if (empty($claim['nhcx_claim_id'])) {
            return $this->response->setJSON([
                'status'       => $claim['status'],
                'claim_id'     => $id,
                'claim_number' => $claim['claim_number'],
                'nhcx_status'  => null,
                'message'      => 'Claim has not been submitted to NHCX yet',
            ]);
        }

        $response = $this->nhcx->getClaimStatus($claim['nhcx_claim_id'], $claim);

        if ($response['success']) {
            $this->claimModel->update($id, ['status' => $response['nhcx_status'] ?? $claim['status']]);
        }

        $this->auditLog->record(
            null,
            'claim_status_check',
            'claim',
            $id,
            $this->request->getIPAddress(),
            ['claim_id' => $id],
            $response,
            $response['success'] ? 'success' : 'failed'
        );

        return $this->response->setJSON([
            'status'        => $response['success'] ? 'success' : 'error',
            'claim_id'      => $id,
            'claim_number'  => $claim['claim_number'],
            'nhcx_claim_id' => $claim['nhcx_claim_id'],
            'nhcx_status'   => $response['nhcx_status'] ?? null,
            'details'       => $response['details'] ?? null,
        ]);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------
    private function generateClaimNumber(): string
    {
        return 'CLM-' . strtoupper(bin2hex(random_bytes(6))) . '-' . date('Ymd');
    }

    private function jsonError(int $code, array|string $errors): ResponseInterface
    {
        return $this->response
            ->setStatusCode($code)
            ->setJSON(['status' => 'error', 'errors' => $errors]);
    }
}
