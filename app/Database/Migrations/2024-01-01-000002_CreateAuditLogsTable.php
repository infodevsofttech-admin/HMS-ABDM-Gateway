<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration: Create audit_logs table
 *
 * Compliance audit trail that records every inbound request and
 * outbound ABDM API call made by the gateway.
 */
class CreateAuditLogsTable extends Migration
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
                'null'       => true,
                'comment'    => 'Local HMS entity identifier (optional)',
            ],
            'action' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
                'comment'    => 'Action performed, e.g. register_hospital, push_opd',
            ],
            'record_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
            ],
            'request_payload' => [
                'type' => 'LONGTEXT',
                'null' => true,
                'comment' => 'JSON-encoded inbound request body',
            ],
            'response_payload' => [
                'type' => 'LONGTEXT',
                'null' => true,
                'comment' => 'JSON-encoded ABDM API response body',
            ],
            'status_code' => [
                'type'       => 'SMALLINT',
                'constraint' => 5,
                'unsigned'   => true,
                'null'       => false,
                'comment'    => 'HTTP status code returned (or 0 for internal events)',
            ],
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'null'       => true,
            ],
            'user_agent' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('hms_id');
        $this->forge->addKey('action');
        $this->forge->addKey('record_type');
        $this->forge->addKey('created_at');

        $this->forge->createTable('audit_logs', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('audit_logs', true);
    }
}
