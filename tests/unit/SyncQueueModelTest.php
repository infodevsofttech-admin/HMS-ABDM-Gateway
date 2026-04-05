<?php

namespace Tests\Unit;

use App\Models\SyncQueueModel;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * SyncQueueModelTest
 *
 * Unit tests for SyncQueueModel helper methods that do not require
 * a live database connection.
 */
class SyncQueueModelTest extends CIUnitTestCase
{
    private SyncQueueModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new SyncQueueModel();
    }

    public function testTableIsCorrect(): void
    {
        $ref   = new \ReflectionClass($this->model);
        $prop  = $ref->getProperty('table');
        $prop->setAccessible(true);

        $this->assertSame('sync_queue', $prop->getValue($this->model));
    }

    public function testAllowedFieldsContainRequiredColumns(): void
    {
        $ref   = new \ReflectionClass($this->model);
        $prop  = $ref->getProperty('allowedFields');
        $prop->setAccessible(true);
        $fields = $prop->getValue($this->model);

        $required = ['entity_type', 'entity_id', 'action', 'payload', 'status', 'attempts', 'last_error'];
        foreach ($required as $field) {
            $this->assertContains($field, $fields, "Expected '$field' in allowedFields");
        }
    }
}
