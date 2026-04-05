<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * ABDM Sandbox Configuration
 *
 * Credentials and endpoint URLs for the ABDM (Ayushman Bharat Digital Mission)
 * sandbox environment.  Override any value via the .env file.
 */
class Abdm extends BaseConfig
{
    /**
     * ABDM Client ID (issued by NHA).
     */
    public string $clientId = '';

    /**
     * ABDM Client Secret.
     */
    public string $clientSecret = '';

    /**
     * ABDM sandbox base URL.
     */
    public string $baseUrl = 'https://dev.abdm.gov.in';

    /**
     * ABDM session / auth URL used to obtain access tokens.
     */
    public string $authUrl = 'https://dev.abdm.gov.in/api/v1/sessions';

    /**
     * HFR (Health Facility Registry) API base path.
     */
    public string $hfrApiPath = '/api/v2/facility';

    /**
     * HPR (Health Professional Registry) API base path.
     */
    public string $hprApiPath = '/api/v2/professional';

    /**
     * ABHA (Ayushman Bharat Health Account) creation API path.
     */
    public string $abhaApiPath = '/api/v1/account';

    /**
     * FHIR health information exchange base path.
     */
    public string $fhirApiPath = '/api/v1/health-information';

    /**
     * Cached ABDM access token (populated at runtime).
     */
    public string $accessToken = '';

    /**
     * Token expiry timestamp (Unix epoch, populated at runtime).
     */
    public int $tokenExpiry = 0;
}
