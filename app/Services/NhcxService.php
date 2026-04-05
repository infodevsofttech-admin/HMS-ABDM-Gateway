<?php

namespace App\Services;

use Config\Nhcx;

/**
 * NhcxService
 *
 * Handles all outbound HTTP calls to the NHCX (National Health Claims Exchange)
 * for insurance claim submission and status retrieval.
 *
 * All methods return a normalized response array:
 *   ['success' => bool, 'nhcx_claim_id' => string|null, 'nhcx_status' => string|null,
 *    'details' => array|null, 'error' => string|null]
 */
class NhcxService
{
    private Nhcx $config;

    public function __construct()
    {
        $this->config = config('Nhcx');
    }

    // -----------------------------------------------------------------------
    // Claim Submission
    // -----------------------------------------------------------------------

    /**
     * Submit an insurance claim to NHCX.
     *
     * @param array<string, mixed> $fhirClaimBundle  FHIR Claim bundle from FhirMapper::mapClaim()
     * @param array<string, mixed> $claimMeta        Supplementary metadata (insurer, TPA, etc.)
     * @return array<string, mixed>
     */
    public function submitClaim(array $fhirClaimBundle, array $claimMeta): array
    {
        $payload = [
            'payload' => [
                'sender'    => $this->config->senderCode,
                'recipient' => $claimMeta['insurer_code'] ?? $this->config->recipientCode,
                'content'   => $fhirClaimBundle,
            ],
        ];

        $response = $this->post($this->config->baseUrl . $this->config->claimSubmitPath, $payload);

        if ($response['http_code'] === 200 || $response['http_code'] === 202) {
            return [
                'success'       => true,
                'nhcx_claim_id' => $response['body']['claimId']
                    ?? $response['body']['nhcxClaimId']
                    ?? null,
                'nhcx_status'   => $response['body']['status'] ?? 'submitted',
                'details'       => $response['body'],
            ];
        }

        return $this->errorResponse(
            $response['body']['message'] ?? 'NHCX claim submission failed'
        );
    }

    // -----------------------------------------------------------------------
    // Claim Status
    // -----------------------------------------------------------------------

    /**
     * Retrieve the current status of a claim from NHCX/TPA.
     *
     * @param string               $nhcxClaimId  The NHCX-assigned claim reference
     * @param array<string, mixed> $claimMeta    Local claim metadata
     * @return array<string, mixed>
     */
    public function getClaimStatus(string $nhcxClaimId, array $claimMeta): array
    {
        $url = $this->config->baseUrl
            . $this->config->claimStatusPath
            . '/' . urlencode($nhcxClaimId);

        $response = $this->get($url);

        if ($response['http_code'] === 200) {
            $body = $response['body'];

            return [
                'success'      => true,
                'nhcx_status'  => $body['status'] ?? $body['claimStatus'] ?? null,
                'details'      => $body,
            ];
        }

        return $this->errorResponse(
            $response['body']['message'] ?? 'NHCX status check failed'
        );
    }

    // -----------------------------------------------------------------------
    // HTTP helpers
    // -----------------------------------------------------------------------

    /**
     * Perform a POST request with JSON body to NHCX.
     *
     * @param array<string, mixed> $payload
     * @return array{http_code: int, body: array<string, mixed>}
     */
    private function post(string $url, array $payload): array
    {
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'X-NHCX-SenderCode: ' . $this->config->senderCode,
        ];

        if ($this->config->apiKey !== '') {
            $headers[] = 'Authorization: Bearer ' . $this->config->apiKey;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $raw      = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($raw === false || $error !== '') {
            return ['http_code' => 0, 'body' => ['message' => $error ?: 'cURL error']];
        }

        $body = json_decode($raw, true);

        return [
            'http_code' => $httpCode,
            'body'      => is_array($body) ? $body : ['raw' => $raw],
        ];
    }

    /**
     * Perform a GET request to NHCX.
     *
     * @return array{http_code: int, body: array<string, mixed>}
     */
    private function get(string $url): array
    {
        $headers = [
            'Accept: application/json',
            'X-NHCX-SenderCode: ' . $this->config->senderCode,
        ];

        if ($this->config->apiKey !== '') {
            $headers[] = 'Authorization: Bearer ' . $this->config->apiKey;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $raw      = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($raw === false || $error !== '') {
            return ['http_code' => 0, 'body' => ['message' => $error ?: 'cURL error']];
        }

        $body = json_decode($raw, true);

        return [
            'http_code' => $httpCode,
            'body'      => is_array($body) ? $body : ['raw' => $raw],
        ];
    }

    /**
     * Build a normalized error response.
     *
     * @return array<string, mixed>
     */
    private function errorResponse(string $message): array
    {
        return [
            'success'      => false,
            'nhcx_claim_id' => null,
            'nhcx_status'  => null,
            'details'      => null,
            'error'        => $message,
        ];
    }
}
