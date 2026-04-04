<?php

namespace App\Services;

use Config\AbdmConfig;
use CodeIgniter\HTTP\CURLRequest;
use RuntimeException;

/**
 * AbdmApiService
 *
 * Handles all outbound HTTP calls to the ABDM sandbox / production APIs.
 * Manages Bearer-token acquisition and transparent token refresh.
 */
class AbdmApiService
{
    protected AbdmConfig $config;
    protected CURLRequest $client;

    /** @var string|null In-memory access token cache */
    protected ?string $accessToken = null;

    /** @var int Unix timestamp when the token expires */
    protected int $tokenExpiresAt = 0;

    public function __construct()
    {
        $this->config = config('AbdmConfig');
        $this->client = \Config\Services::curlrequest([
            'baseURI' => $this->config->baseUrl,
            'timeout' => 30,
        ]);
    }

    // -------------------------------------------------------------------------
    // Token management
    // -------------------------------------------------------------------------

    /**
     * Return a valid Bearer token, refreshing it if necessary.
     *
     * @throws RuntimeException when token acquisition fails.
     */
    public function getAccessToken(): string
    {
        if ($this->accessToken !== null && time() < $this->tokenExpiresAt - 30) {
            return $this->accessToken;
        }

        return $this->refreshToken();
    }

    /**
     * Fetch a fresh access token from the ABDM gateway.
     */
    protected function refreshToken(): string
    {
        try {
            $response = $this->client->post($this->config->tokenEndpoint, [
                'headers' => ['Content-Type' => 'application/json'],
                'json'    => [
                    'clientId'     => $this->config->clientId,
                    'clientSecret' => $this->config->clientSecret,
                ],
            ]);

            $body = json_decode($response->getBody(), true);

            if (empty($body['accessToken'])) {
                throw new RuntimeException('ABDM token response missing accessToken: ' . $response->getBody());
            }

            $this->accessToken    = $body['accessToken'];
            $this->tokenExpiresAt = time() + (int) ($body['expiresIn'] ?? 1800);

            return $this->accessToken;
        } catch (\Exception $e) {
            throw new RuntimeException('Failed to obtain ABDM access token: ' . $e->getMessage(), 0, $e);
        }
    }

    // -------------------------------------------------------------------------
    // Generic request helper
    // -------------------------------------------------------------------------

    /**
     * Make an authenticated POST request to an ABDM endpoint.
     *
     * @throws RuntimeException on HTTP or network errors.
     */
    public function post(string $endpoint, array $payload): array
    {
        try {
            $token    = $this->getAccessToken();
            $response = $this->client->post($endpoint, [
                'headers' => [
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                    'X-CM-ID'       => 'sbx',
                ],
                'json' => $payload,
            ]);

            $statusCode = $response->getStatusCode();
            $body       = json_decode($response->getBody(), true) ?? [];

            if ($statusCode < 200 || $statusCode >= 300) {
                throw new RuntimeException(
                    "ABDM API error (HTTP {$statusCode}): " . $response->getBody()
                );
            }

            return ['status' => $statusCode, 'body' => $body];
        } catch (RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new RuntimeException('ABDM API request failed: ' . $e->getMessage(), 0, $e);
        }
    }

    // -------------------------------------------------------------------------
    // Domain-specific API wrappers
    // -------------------------------------------------------------------------

    /**
     * Register a hospital in the Health Facility Registry (HFR).
     */
    public function registerHospital(array $hfrPayload): array
    {
        return $this->post($this->config->hfrEndpoint, $hfrPayload);
    }

    /**
     * Register a doctor in the Health Professional Registry (HPR).
     */
    public function registerDoctor(array $hprPayload): array
    {
        return $this->post($this->config->hprEndpoint, $hprPayload);
    }

    /**
     * Create an ABHA ID for a patient.
     */
    public function createAbha(array $abhaPayload): array
    {
        return $this->post($this->config->abhaEndpoint, $abhaPayload);
    }

    /**
     * Push a FHIR bundle (health records) to ABDM.
     */
    public function pushHealthRecord(array $fhirBundle): array
    {
        return $this->post($this->config->hiuEndpoint, $fhirBundle);
    }
}
