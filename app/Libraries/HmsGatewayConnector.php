<?php

/**
 * HmsGatewayConnector
 *
 * Drop-in PHP client for the local HMS (devsofttech_hms_ci4) to communicate
 * with the HMS-ABDM Gateway.
 *
 * INSTALLATION
 * ─────────────────────────────────────────────────────────────────────────────
 * 1. Copy this file into your HMS project:
 *       cp HmsGatewayConnector.php /path/to/hms/app/Libraries/
 *
 * 2. Add to your HMS .env:
 *       ABDM_GATEWAY_URL    = http://your-gateway-server/
 *       ABDM_GATEWAY_API_KEY = <key from the gateway admin panel>
 *
 * 3. Use in any HMS controller or service:
 *       $gw = new \App\Libraries\HmsGatewayConnector();
 *       $result = $gw->syncHospital([...]);
 *
 * USAGE EXAMPLES
 * ─────────────────────────────────────────────────────────────────────────────
 *   $gw = new HmsGatewayConnector();
 *
 *   // Register the hospital in HFR
 *   $res = $gw->syncHospital([
 *       'hms_id'   => 'HOSP-001',
 *       'name'     => 'City General Hospital',
 *       'state'    => 'Maharashtra',
 *       'district' => 'Pune',
 *       'address'  => '123 MG Road, Pune',
 *       'pincode'  => '411001',
 *       'type'     => 'allopathy',
 *   ]);
 *
 *   // Register a doctor in HPR
 *   $res = $gw->syncDoctor([
 *       'hms_id'          => 'HOSP-001',
 *       'doctor_id'       => 'DOC-101',
 *       'name'            => 'Dr. Priya Sharma',
 *       'registration_no' => 'MH12345',
 *       'specialisation'  => 'General Medicine',
 *   ]);
 *
 *   // Create ABHA ID for a patient
 *   $res = $gw->syncPatient([
 *       'hms_id'     => 'HOSP-001',
 *       'patient_id' => 'PAT-501',
 *       'name'       => 'Ramesh Kumar',
 *       'dob'        => '1985-06-15',
 *       'gender'     => 'M',
 *       'mobile'     => '9876543210',
 *       'aadhaar'    => '1234-5678-9012',
 *   ]);
 *
 *   // Push an OPD health record
 *   $res = $gw->syncRecord('opd', [
 *       'hms_id'     => 'HOSP-001',
 *       'patient_id' => 'PAT-501',
 *       'doctor_id'  => 'DOC-101',
 *       'visit_date' => '2024-06-01',
 *       'chief_complaint' => 'Fever and headache',
 *       'diagnosis'  => 'Viral fever',
 *       'prescription' => [...],
 *   ]);
 *
 * RESPONSE FORMAT
 * ─────────────────────────────────────────────────────────────────────────────
 * All methods return an array:
 *   [
 *     'success'     => true|false,
 *     'status_code' => 200|202|400|401|...,
 *     'data'        => [...],     // on success (200)
 *     'queue_id'    => 42,        // on 202 (queued for retry)
 *     'message'     => '...',     // on error
 *   ]
 */
class HmsGatewayConnector
{
    private string $baseUrl;
    private string $apiKey;
    private int    $timeoutSeconds;

    public function __construct(
        string $baseUrl = '',
        string $apiKey = '',
        int    $timeoutSeconds = 30
    ) {
        // Fallback to .env / environment values
        $this->baseUrl        = rtrim($baseUrl ?: (env('ABDM_GATEWAY_URL') ?? ''), '/');
        $this->apiKey         = $apiKey        ?: (env('ABDM_GATEWAY_API_KEY') ?? '');
        $this->timeoutSeconds = $timeoutSeconds;

        if ($this->baseUrl === '') {
            throw new \RuntimeException('HmsGatewayConnector: ABDM_GATEWAY_URL is not configured.');
        }
        if ($this->apiKey === '') {
            throw new \RuntimeException('HmsGatewayConnector: ABDM_GATEWAY_API_KEY is not configured.');
        }
    }

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * POST /sync/hospital — Register the HMS facility in HFR.
     *
     * @param  array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function syncHospital(array $data): array
    {
        return $this->post('sync/hospital', $data);
    }

    /**
     * POST /sync/doctor — Register a doctor in HPR.
     *
     * @param  array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function syncDoctor(array $data): array
    {
        return $this->post('sync/doctor', $data);
    }

    /**
     * POST /sync/patient — Create an ABHA ID for a patient.
     *
     * @param  array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function syncPatient(array $data): array
    {
        return $this->post('sync/patient', $data);
    }

    /**
     * POST /sync/records/{type} — Push a health record.
     *
     * @param  string               $type  opd|ipd|lab|radiology|pharmacy
     * @param  array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function syncRecord(string $type, array $data): array
    {
        $allowed = ['opd', 'ipd', 'lab', 'radiology', 'pharmacy'];
        if (!in_array($type, $allowed, true)) {
            throw new \InvalidArgumentException("Invalid record type '$type'. Allowed: " . implode(', ', $allowed));
        }

        return $this->post("sync/records/{$type}", $data);
    }

    // -------------------------------------------------------------------------
    // HTTP helper
    // -------------------------------------------------------------------------

    /**
     * Execute an authenticated POST request to the gateway.
     *
     * @param  array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function post(string $endpoint, array $payload): array
    {
        $url  = $this->baseUrl . '/' . ltrim($endpoint, '/');
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeoutSeconds,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'X-API-Key: ' . $this->apiKey,
            ],
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $raw        = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError  = curl_error($ch);
        curl_close($ch);

        if ($raw === false || $curlError !== '') {
            return [
                'success'     => false,
                'status_code' => 0,
                'message'     => 'Gateway connection error: ' . $curlError,
            ];
        }

        $decoded = json_decode((string) $raw, true);

        if (!is_array($decoded)) {
            return [
                'success'     => false,
                'status_code' => $statusCode,
                'message'     => 'Invalid JSON response from gateway.',
            ];
        }

        return array_merge(['status_code' => $statusCode], $decoded);
    }
}
