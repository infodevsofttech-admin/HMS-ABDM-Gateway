<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Create the sync_queue table.
 *
 * Holds pending/failed ABDM API calls for asynchronous retry.
 */
class CreateSyncQueueTable extends Migration
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
            'entity_type' => ['type' => 'VARCHAR', 'constraint' => 50],
            'entity_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'action'      => ['type' => 'VARCHAR', 'constraint' => 100],
            'payload'     => ['type' => 'JSON', 'null' => true],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'processing', 'completed', 'failed'],
                'default'    => 'pending',
            ],
            'attempts'   => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'default' => 0],
            'last_error' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['entity_type', 'entity_id']);
        $this->forge->addKey('status');
        $this->forge->createTable('sync_queue');
    }

    public function down(): void
    {
        $this->forge->dropTable('sync_queue');
    }
}
