<?php

namespace App\Services;

use Config\Abdm;

/**
 * AbdmgatewayService
 *
 * Handles all outbound HTTP calls to the ABDM (Ayushman Bharat Digital Mission)
 * sandbox API including HFR registration, HPR registration, ABHA creation,
 * encounter push, and document push.
 *
 * All methods return a normalized response array:
 *   ['success' => bool, 'data' => array|null, 'error' => string|null, ...]
 */
class AbdmgatewayService
{
    private Abdm $config;
    private string $accessToken = '';

    public function __construct()
    {
        $this->config = config('Abdm');
    }

    // -----------------------------------------------------------------------
    // HFR – Health Facility Registry
    // -----------------------------------------------------------------------

    /**
     * Register or update a hospital in the HFR.
     *
     * @param array<string, mixed> $hospital
     * @return array<string, mixed>
     */
    public function registerHospital(array $hospital): array
    {
        $token = $this->getAccessToken();

        if ($token === null) {
            return $this->errorResponse('Failed to obtain ABDM access token');
        }

        $payload = [
            'facilityName'    => $hospital['name'] ?? '',
            'facilityAddress' => [
                'address'  => $hospital['address'] ?? '',
                'city'     => $hospital['city'] ?? '',
                'state'    => $hospital['state'] ?? '',
                'pincode'  => $hospital['pincode'] ?? '',
            ],
            'facilityContact' => [
                'phone' => $hospital['phone'] ?? '',
                'email' => $hospital['email'] ?? '',
            ],
        ];

        $response = $this->post(
            $this->config->baseUrl . $this->config->hfrApiPath,
            $payload,
            $token
        );

        if ($response['http_code'] === 200 || $response['http_code'] === 201) {
            return [
                'success' => true,
                'hfr_id'  => $response['body']['facilityId'] ?? $response['body']['hfrId'] ?? null,
                'data'    => $response['body'],
            ];
        }

        return $this->errorResponse($response['body']['message'] ?? 'HFR registration failed');
    }

    // -----------------------------------------------------------------------
    // HPR – Health Professional Registry
    // -----------------------------------------------------------------------

    /**
     * Register or update a doctor in the HPR.
     *
     * @param array<string, mixed> $doctor
     * @return array<string, mixed>
     */
    public function registerDoctor(array $doctor): array
    {
        $token = $this->getAccessToken();

        if ($token === null) {
            return $this->errorResponse('Failed to obtain ABDM access token');
        }

        $payload = [
            'name'           => $doctor['name'] ?? '',
            'specialization' => $doctor['specialization'] ?? '',
            'qualification'  => $doctor['qualification'] ?? '',
            'contact'        => [
                'phone' => $doctor['phone'] ?? '',
                'email' => $doctor['email'] ?? '',
            ],
        ];

        $response = $this->post(
            $this->config->baseUrl . $this->config->hprApiPath,
            $payload,
            $token
        );

        if ($response['http_code'] === 200 || $response['http_code'] === 201) {
            return [
                'success' => true,
                'hpr_id'  => $response['body']['hprId'] ?? $response['body']['professionalId'] ?? null,
                'data'    => $response['body'],
            ];
        }

        return $this->errorResponse($response['body']['message'] ?? 'HPR registration failed');
    }

    // -----------------------------------------------------------------------
    // ABHA – Ayushman Bharat Health Account
    // -----------------------------------------------------------------------

    /**
     * Create an ABHA (health account) for a patient.
     *
     * @param array<string, mixed> $patient
     * @return array<string, mixed>
     */
    public function createAbha(array $patient): array
    {
        $token = $this->getAccessToken();

        if ($token === null) {
            return $this->errorResponse('Failed to obtain ABDM access token');
        }

        $payload = [
            'name'    => $patient['name'] ?? '',
            'dob'     => $patient['dob'] ?? '',
            'gender'  => $patient['gender'] ?? '',
            'mobile'  => $patient['phone'] ?? '',
            'address' => $patient['address'] ?? '',
        ];

        $response = $this->post(
            $this->config->baseUrl . $this->config->abhaApiPath,
            $payload,
            $token
        );

        if ($response['http_code'] === 200 || $response['http_code'] === 201) {
            return [
                'success' => true,
                'abha_id' => $response['body']['abhaNumber'] ?? $response['body']['healthId'] ?? null,
                'data'    => $response['body'],
            ];
        }

        return $this->errorResponse($response['body']['message'] ?? 'ABHA creation failed');
    }

    // -----------------------------------------------------------------------
    // Health Information Exchange
    // -----------------------------------------------------------------------

    /**
     * Push a FHIR encounter bundle to ABDM HIE.
     *
     * @param array<string, mixed> $fhirBundle
     * @return array<string, mixed>
     */
    public function pushEncounter(array $fhirBundle): array
    {
        $token = $this->getAccessToken();

        if ($token === null) {
            return $this->errorResponse('Failed to obtain ABDM access token');
        }

        $response = $this->post(
            $this->config->baseUrl . $this->config->fhirApiPath . '/encounter',
            $fhirBundle,
            $token
        );

        if ($response['http_code'] === 200 || $response['http_code'] === 201) {
            return [
                'success'      => true,
                'encounter_id' => $response['body']['id'] ?? $response['body']['encounterId'] ?? null,
                'data'         => $response['body'],
            ];
        }

        return $this->errorResponse($response['body']['message'] ?? 'Encounter push failed');
    }

    /**
     * Push a FHIR document bundle (lab, radiology, pharmacy) to ABDM HIE.
     *
     * @param array<string, mixed> $fhirBundle
     * @return array<string, mixed>
     */
    public function pushDocument(array $fhirBundle): array
    {
        $token = $this->getAccessToken();

        if ($token === null) {
            return $this->errorResponse('Failed to obtain ABDM access token');
        }

        $response = $this->post(
            $this->config->baseUrl . $this->config->fhirApiPath . '/document',
            $fhirBundle,
            $token
        );

        if ($response['http_code'] === 200 || $response['http_code'] === 201) {
            return [
                'success'     => true,
                'document_id' => $response['body']['id'] ?? $response['body']['documentId'] ?? null,
                'data'        => $response['body'],
            ];
        }

        return $this->errorResponse($response['body']['message'] ?? 'Document push failed');
    }

    // -----------------------------------------------------------------------
    // Auth – ABDM Session Token
    // -----------------------------------------------------------------------

    /**
     * Obtain a session token from ABDM.  Returns null on failure.
     */
    private function getAccessToken(): ?string
    {
        // Return cached token if not expired
        if ($this->accessToken !== '' && $this->config->tokenExpiry > time()) {
            return $this->accessToken;
        }

        $response = $this->post($this->config->authUrl, [
            'clientId'     => $this->config->clientId,
            'clientSecret' => $this->config->clientSecret,
        ]);

        if ($response['http_code'] === 200 && ! empty($response['body']['accessToken'])) {
            $this->accessToken          = $response['body']['accessToken'];
            $this->config->accessToken  = $this->accessToken;
            $this->config->tokenExpiry  = time() + ($response['body']['expiresIn'] ?? 1800);

            return $this->accessToken;
        }

        return null;
    }

    // -----------------------------------------------------------------------
    // HTTP helpers
    // -----------------------------------------------------------------------

    /**
     * Perform a POST request with JSON body.
     *
     * @param array<string, mixed> $payload
     * @param string|null          $bearerToken
     * @return array{http_code: int, body: array<string, mixed>}
     */
    private function post(string $url, array $payload, ?string $bearerToken = null): array
    {
        $headers = ['Content-Type: application/json', 'Accept: application/json'];

        if ($bearerToken !== null) {
            $headers[] = 'Authorization: Bearer ' . $bearerToken;
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
     * Build a normalized error response array.
     *
     * @return array<string, mixed>
     */
    private function errorResponse(string $message): array
    {
        return ['success' => false, 'error' => $message, 'data' => null];
    }
}
