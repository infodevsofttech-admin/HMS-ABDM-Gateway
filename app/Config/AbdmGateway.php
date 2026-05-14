<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class AbdmGateway extends BaseConfig
{
    /**
     * Gateway Configuration
     */
    
    /**
     * Source Code / Client ID
     * @var string
     */
    public string $sourceCode = 'SBXID_033661';

    /**
     * Bearer Token for HMS authentication
     * @var string
     */
    public string $bearerToken = '';

    /**
     * ABDM M3 API Configuration
     */
    
    /**
     * ABDM M3 Base URL
     * @var string
     */
    public string $m3Url = 'https://dev.abdm.gov.in/api/v3';

    /**
     * ABDM M3 Bearer Token
     * @var string
     */
    public string $m3Token = '';

    /**
     * ABDM Sandbox Client ID
     * @var string
     */
    public string $abdmClientId = '';

    /**
     * ABDM Sandbox Client Secret
     * @var string
     */
    public string $abdmClientSecret = '';

    /**
     * ABDM auth token endpoint for client credentials flow
     * @var string
     */
    public string $abdmAuthUrl = 'https://dev.abdm.gov.in/api/hiecm/gateway/v3/sessions';

    /**
     * ABDM M1 Base URL (ABHA OTP and registration flow endpoints)
     * @var string
     */
    public string $m1BaseUrl = 'https://abhasbx.abdm.gov.in';

    /**
     * M1 endpoint paths for ABHA Aadhaar/Mobile OTP flows.
     */
    public string $m1AadhaarGenerateOtpPath = '/abha/api/v3/enrollment/request/otp';
    public string $m1AadhaarVerifyOtpPath = '/abha/api/v3/enrollment/enrol/byAadhaar';
    public string $m1MobileGenerateOtpPath = '/abha/api/v3/enrollment/request/otp';
    public string $m1MobileVerifyOtpPath = '/abha/api/v3/enrollment/enrol/byMobile';

    /**
     * ABDM M3 Timeout (seconds)
     * @var int
     */
    public int $m3Timeout = 30;

    /**
     * SNOMED CT Service Configuration
     */
    
    /**
     * SNOMED Service Base URL
     * @var string
     */
    public string $snomedUrl = 'https://csnotk.e-atria.in/csnoserv';

    /**
     * SNOMED Service Timeout (seconds)
     * @var int
     */
    public int $snomedTimeout = 10;

    /**
     * Rate Limiting Configuration
     */
    
    /**
     * Rate limit: number of requests
     * @var int
     */
    public int $rateLimitRequests = 100;

    /**
     * Rate limit: time window in minutes
     * @var int
     */
    public int $rateLimitWindowMinutes = 15;

    /**
     * Logging Configuration
     */
    
    /**
     * Log all requests to database
     * @var bool
     */
    public bool $logDatabase = true;

    /**
     * Log level: debug, info, warning, error
     * @var string
     */
    public string $logLevel = 'info';

    /**
     * Log request body
     * @var bool
     */
    public bool $logRequestBody = true;

    /**
     * Log response body (be careful with sensitive data)
     * @var bool
     */
    public bool $logResponseBody = false;

    /**
     * Facility Configuration
     */
    
    /**
     * HFR ID (Health Facility Registry ID)
     * @var string
     */
    public string $hfrId = '';

    /**
     * NPI (National Practitioner Identifier)
     * @var string
     */
    public string $npiId = '';

    /**
     * Internal/public base URL used by bridge ingress dispatching.
     * @var string
     */
    public string $publicUrl = 'http://127.0.0.1';

    /**
     * Local test mode: do not call external ABDM/SNOMED and skip DB writes.
     * @var bool
     */
    public bool $testMode = true;

    // ==================== Constructor ====================

    public function __construct()
    {
        parent::__construct();

        // Load from environment variables
        $this->bearerToken = env('GATEWAY_BEARER_TOKEN', '');
        $this->m3Token = env('ABDM_TOKEN', '');
        $this->m3Url = env('ABDM_M3_URL', env('ABDM_URL', $this->m3Url));
        $this->abdmClientId = env('ABDM_CLIENT_ID', $this->abdmClientId);
        $this->abdmClientSecret = env('ABDM_CLIENT_SECRET', $this->abdmClientSecret);
        $this->abdmAuthUrl = env('ABDM_AUTH_URL', $this->abdmAuthUrl);
        $this->m1BaseUrl = env('ABDM_M1_BASE_URL', $this->m1BaseUrl);
        $this->m1AadhaarGenerateOtpPath = env('ABDM_M1_AADHAAR_GENERATE_OTP_PATH', $this->m1AadhaarGenerateOtpPath);
        $this->m1AadhaarVerifyOtpPath = env('ABDM_M1_AADHAAR_VERIFY_OTP_PATH', $this->m1AadhaarVerifyOtpPath);
        $this->m1MobileGenerateOtpPath = env('ABDM_M1_MOBILE_GENERATE_OTP_PATH', $this->m1MobileGenerateOtpPath);
        $this->m1MobileVerifyOtpPath = env('ABDM_M1_MOBILE_VERIFY_OTP_PATH', $this->m1MobileVerifyOtpPath);
        $this->snomedUrl = env('SNOMED_SERVICE_URL', $this->snomedUrl);
        $this->sourceCode = env('GATEWAY_SOURCE_CODE', $this->sourceCode);
        $this->hfrId = env('ABDM_HFR_ID', '');
        $this->npiId = env('ABDM_NPI_ID', '');
        $this->publicUrl = env('GATEWAY_PUBLIC_URL', $this->publicUrl);
        $this->testMode = filter_var(env('GATEWAY_TEST_MODE', 'true'), FILTER_VALIDATE_BOOLEAN);
    }
}
