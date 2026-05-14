<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAppSettings extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'setting_key'   => ['type' => 'VARCHAR', 'constraint' => 100],
            'setting_value' => ['type' => 'TEXT', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('setting_key', true);
        $this->forge->createTable('abdm_app_settings');
    }

    public function down(): void
    {
        $this->forge->dropTable('abdm_app_settings', true);
    }
}
