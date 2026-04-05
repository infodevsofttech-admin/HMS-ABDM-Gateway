<?php

namespace Tests\Unit;

use App\Models\AuditLogModel;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * AuditLogModelTest
 *
 * Unit tests for AuditLogModel's scrubSensitive logic.
 * Uses reflection to test the private helper without a database.
 */
class AuditLogModelTest extends CIUnitTestCase
{
    private AuditLogModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new AuditLogModel();
    }

    /**
     * Helper: invoke the private scrubSensitive method via reflection.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function callScrubSensitive(array $data): array
    {
        $ref    = new \ReflectionClass($this->model);
        $method = $ref->getMethod('scrubSensitive');
        $method->setAccessible(true);

        return $method->invoke($this->model, $data);
    }

    public function testScrubSensitiveRedactsPassword(): void
    {
        $data   = ['username' => 'admin', 'password' => 'secret123'];
        $result = $this->callScrubSensitive($data);

        $this->assertSame('admin', $result['username']);
        $this->assertSame('***', $result['password']);
    }

    public function testScrubSensitiveRedactsApiToken(): void
    {
        $data   = ['hospital_id' => 1, 'api_token' => 'abc123def456'];
        $result = $this->callScrubSensitive($data);

        $this->assertSame('***', $result['api_token']);
        $this->assertSame(1, $result['hospital_id']);
    }

    public function testScrubSensitiveRedactsToken(): void
    {
        $result = $this->callScrubSensitive(['token' => 'my_bearer_token', 'user_id' => 42]);
        $this->assertSame('***', $result['token']);
        $this->assertSame(42, $result['user_id']);
    }

    public function testScrubSensitiveRedactsSecret(): void
    {
        $result = $this->callScrubSensitive(['client_id' => 'xyz', 'secret' => 'supersecret']);
        $this->assertSame('***', $result['secret']);
    }

    public function testScrubSensitivePreservesNonSensitiveData(): void
    {
        $data   = ['action' => 'sync_hospital', 'entity_id' => 5, 'status' => 'success'];
        $result = $this->callScrubSensitive($data);

        $this->assertSame($data, $result);
    }

    public function testScrubSensitiveHandlesEmptyArray(): void
    {
        $result = $this->callScrubSensitive([]);
        $this->assertSame([], $result);
    }
}
