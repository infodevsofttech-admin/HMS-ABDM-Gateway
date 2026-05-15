<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddHmsApiKeyHash extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('hms_credentials', [
            'hms_api_key_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
                'default'    => null,
                'after'      => 'hms_api_key',
            ],
        ]);

        $this->db->query('ALTER TABLE hms_credentials ADD INDEX idx_hms_key_hash (hms_api_key_hash)');
    }

    public function down(): void
    {
        $this->db->query('ALTER TABLE hms_credentials DROP INDEX idx_hms_key_hash');
        $this->forge->dropColumn('hms_credentials', 'hms_api_key_hash');
    }
}
