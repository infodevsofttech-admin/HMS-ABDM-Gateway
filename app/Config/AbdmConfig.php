<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class AbdmConfig extends BaseConfig
{
    /**
     * ABDM API Base URL (sandbox or production).
     */
    public string $baseUrl = '';

    /**
     * ABDM Client ID.
     */
    public string $clientId = '';

    /**
     * ABDM Client Secret.
     */
    public string $clientSecret = '';

    /**
     * Token endpoint path.
     */
    public string $tokenEndpoint = '/api/v1/sessions';

    /**
     * HFR (Health Facility Registry) endpoint path.
     */
    public string $hfrEndpoint = '/api/v1/facility';

    /**
     * HPR (Health Professional Registry) endpoint path.
     */
    public string $hprEndpoint = '/api/v1/professional';

    /**
     * ABHA (Ayushman Bharat Health Account) endpoint path.
     */
    public string $abhaEndpoint = '/api/v1/registration/aadhaar';

    /**
     * Health Information (FHIR records) endpoint path.
     */
    public string $hiuEndpoint = '/api/v1/health-information';

    /**
     * Maximum number of retry attempts for failed sync requests.
     */
    public int $maxRetryAttempts = 3;

    /**
     * Retry delay in seconds (base value; exponential back-off applied).
     */
    public int $retryDelaySeconds = 60;

    /**
     * Environment: 'sandbox' or 'production'.
     */
    public string $environment = 'sandbox';

    public function __construct()
    {
        parent::__construct();

        $this->baseUrl      = env('ABDM_BASE_URL', 'https://dev.abdm.gov.in');
        $this->clientId     = env('ABDM_CLIENT_ID', '');
        $this->clientSecret = env('ABDM_CLIENT_SECRET', '');
        $this->environment  = env('ABDM_ENVIRONMENT', 'sandbox');
    }
}
