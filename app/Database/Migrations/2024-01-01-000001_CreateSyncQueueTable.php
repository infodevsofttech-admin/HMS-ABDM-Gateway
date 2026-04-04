<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration: Create sync_queue table
 *
 * Stores outbound ABDM requests that are pending, in-process, or have failed
 * and need to be retried.
 */
class CreateSyncQueueTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'hms_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
                'comment'    => 'Local HMS entity identifier',
            ],
            'record_type' => [
                'type'       => 'ENUM',
                'constraint' => ['hospital', 'doctor', 'patient', 'opd', 'ipd', 'lab', 'radiology', 'pharmacy'],
                'null'       => false,
            ],
            'payload' => [
                'type' => 'LONGTEXT',
                'null' => false,
                'comment' => 'JSON-encoded FHIR payload or domain data',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'processing', 'success', 'failed'],
                'default'    => 'pending',
                'null'       => false,
            ],
            'attempts' => [
                'type'       => 'TINYINT',
                'constraint' => 3,
                'unsigned'   => true,
                'default'    => 0,
            ],
            'last_attempted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'next_retry_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'abdm_response' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'error_message' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['status', 'next_retry_at']);
        $this->forge->addKey('hms_id');
        $this->forge->addKey('record_type');

        $this->forge->createTable('sync_queue', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('sync_queue', true);
    }
}
