<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class FixTestSubmissionLogsSchema extends Migration
{
    public function up()
    {
        // Rename old columns to match current model/code expectations.
        // The table was created with legacy column names; the migration and
        // model both use the newer names below.

        $db = \Config\Database::connect();

        // Only rename if the old column exists (idempotent)
        $fields = $db->getFieldNames('abdm_test_submission_logs');

        if (in_array('test_type', $fields) && !in_array('event_type', $fields)) {
            $this->forge->modifyColumn('abdm_test_submission_logs', [
                'test_type' => [
                    'name'       => 'event_type',
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                ],
            ]);
        }

        if (in_array('test_data', $fields) && !in_array('request_payload', $fields)) {
            $this->forge->modifyColumn('abdm_test_submission_logs', [
                'test_data' => [
                    'name' => 'request_payload',
                    'type' => 'LONGTEXT',
                    'null' => true,
                ],
            ]);
        }

        if (in_array('status', $fields) && !in_array('http_status', $fields)) {
            $this->forge->modifyColumn('abdm_test_submission_logs', [
                'status' => [
                    'name'       => 'http_status',
                    'type'       => 'INT',
                    'null'       => true,
                    'default'    => 200,
                ],
            ]);
        }

        if (in_array('response', $fields) && !in_array('response_payload', $fields)) {
            $this->forge->modifyColumn('abdm_test_submission_logs', [
                'response' => [
                    'name' => 'response_payload',
                    'type' => 'LONGTEXT',
                    'null' => true,
                ],
            ]);
        }

        if (!in_array('endpoint', $fields)) {
            $this->forge->addColumn('abdm_test_submission_logs', [
                'endpoint' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                    'after'      => 'event_type',
                ],
            ]);
        }
    }

    public function down()
    {
        // Reverse: rename back to old names (best effort)
        $db = \Config\Database::connect();
        $fields = $db->getFieldNames('abdm_test_submission_logs');

        if (in_array('event_type', $fields)) {
            $this->forge->modifyColumn('abdm_test_submission_logs', [
                'event_type' => ['name' => 'test_type', 'type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            ]);
        }
        if (in_array('request_payload', $fields)) {
            $this->forge->modifyColumn('abdm_test_submission_logs', [
                'request_payload' => ['name' => 'test_data', 'type' => 'LONGTEXT', 'null' => true],
            ]);
        }
        if (in_array('http_status', $fields)) {
            $this->forge->modifyColumn('abdm_test_submission_logs', [
                'http_status' => ['name' => 'status', 'type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            ]);
        }
        if (in_array('response_payload', $fields)) {
            $this->forge->modifyColumn('abdm_test_submission_logs', [
                'response_payload' => ['name' => 'response', 'type' => 'TEXT', 'null' => true],
            ]);
        }
    }
}
