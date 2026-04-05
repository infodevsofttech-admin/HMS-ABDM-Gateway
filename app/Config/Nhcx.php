<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * NHCX (National Health Claims Exchange) Configuration
 *
 * Endpoint URLs, sender/recipient codes and API key for NHCX
 * insurance claim submission and status checks.  Override any
 * value via the .env file.
 */
class Nhcx extends BaseConfig
{
    /**
     * NHCX API base URL.
     */
    public string $baseUrl = 'https://api.nhcx.abdm.gov.in';

    /**
     * NHCX claim submission path.
     */
    public string $claimSubmitPath = '/api/v1/claim/submit';

    /**
     * NHCX claim status check path.
     */
    public string $claimStatusPath = '/api/v1/claim/status';

    /**
     * Sender code identifying this gateway on NHCX.
     */
    public string $senderCode = '';

    /**
     * Recipient code (TPA or insurer) on NHCX.
     */
    public string $recipientCode = '';

    /**
     * API key for NHCX authentication.
     */
    public string $apiKey = '';

    /**
     * Known insurer/TPA endpoint map.
     *
     * Format: ['insurer_code' => 'https://api.insurer.example/nhcx']
     *
     * @var array<string, string>
     */
    public array $insurerEndpoints = [];
}
