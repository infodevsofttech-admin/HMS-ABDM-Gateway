<?php

use App\Config\AbdmConfig;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Tests for AbdmConfig
 *
 * Verifies that ABDM configuration values resolve correctly from environment
 * variables and that defaults are sensible for a sandbox environment.
 *
 * @internal
 */
final class AbdmConfigTest extends CIUnitTestCase
{
    public function testDefaultEnvironmentIsSandbox(): void
    {
        $config = new \Config\AbdmConfig();
        // Default should be 'sandbox' unless ABDM_ENVIRONMENT is set
        $this->assertSame('sandbox', $config->environment);
    }

    public function testDefaultBaseUrlIsAbdmSandbox(): void
    {
        $config = new \Config\AbdmConfig();
        $this->assertSame('https://dev.abdm.gov.in', $config->baseUrl);
    }

    public function testMaxRetryAttemptsIsPositive(): void
    {
        $config = new \Config\AbdmConfig();
        $this->assertGreaterThan(0, $config->maxRetryAttempts);
    }

    public function testRetryDelaySecondsIsPositive(): void
    {
        $config = new \Config\AbdmConfig();
        $this->assertGreaterThan(0, $config->retryDelaySeconds);
    }

    public function testTokenEndpointIsConfigured(): void
    {
        $config = new \Config\AbdmConfig();
        $this->assertNotEmpty($config->tokenEndpoint);
    }

    public function testHfrEndpointIsConfigured(): void
    {
        $config = new \Config\AbdmConfig();
        $this->assertNotEmpty($config->hfrEndpoint);
    }

    public function testHprEndpointIsConfigured(): void
    {
        $config = new \Config\AbdmConfig();
        $this->assertNotEmpty($config->hprEndpoint);
    }

    public function testAbhaEndpointIsConfigured(): void
    {
        $config = new \Config\AbdmConfig();
        $this->assertNotEmpty($config->abhaEndpoint);
    }
}
