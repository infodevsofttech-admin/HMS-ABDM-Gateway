<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Create the audit_logs table.
 *
 * Immutable compliance log for all significant gateway operations.
 */
class CreateAuditLogsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'action'     => ['type' => 'VARCHAR', 'constraint' => 100],
            'entity_type'=> ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'entity_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'request_data'  => ['type' => 'JSON', 'null' => true],
            'response_data' => ['type' => 'JSON', 'null' => true],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['success', 'failed'],
                'default'    => 'success',
            ],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey('action');
        $this->forge->addKey(['entity_type', 'entity_id']);
        $this->forge->createTable('audit_logs');
    }

    public function down(): void
    {
        $this->forge->dropTable('audit_logs');
    }
}
