<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration: Create master_ids table
 *
 * Stores mappings between local HMS entity identifiers and the external
 * ABDM master IDs: HFR ID (hospitals), HPR ID (doctors), ABHA ID (patients).
 */
class CreateMasterIdsTable extends Migration
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
            'entity_type' => [
                'type'       => 'ENUM',
                'constraint' => ['hospital', 'doctor', 'patient'],
                'null'       => false,
            ],
            'abdm_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
                'comment'    => 'ABDM-assigned identifier (HFR ID, HPR ID, or ABHA ID)',
            ],
            'abdm_id_type' => [
                'type'       => 'ENUM',
                'constraint' => ['HFR_ID', 'HPR_ID', 'ABHA_ID'],
                'null'       => false,
            ],
            'metadata' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'JSON-encoded additional data from ABDM registration response',
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
        $this->forge->addUniqueKey(['hms_id', 'entity_type']);
        $this->forge->addKey('abdm_id');
        $this->forge->addKey('entity_type');

        $this->forge->createTable('master_ids', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('master_ids', true);
    }
}
