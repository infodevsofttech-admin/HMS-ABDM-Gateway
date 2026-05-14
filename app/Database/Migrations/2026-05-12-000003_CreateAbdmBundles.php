<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAbdmBundles extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'constraint' => 20,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'bundle_id' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'consent_id' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'hi_type' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'bundle_hash' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'push_status' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'pending',
            ],
            'push_timestamp' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'response_status' => [
                'type' => 'INT',
                'null' => true,
            ],
            'response_body' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'retry_count' => [
                'type' => 'INT',
                'default' => 0,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
                'on_update' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('id', false, false, 'PRIMARY');
        $this->forge->addUniqueKey('bundle_id');
        $this->forge->addKey('consent_id');
        $this->forge->addKey('push_status');
        $this->forge->addKey('created_at');

        $this->forge->createTable('abdm_bundles', true);
    }

    public function down()
    {
        $this->forge->dropTable('abdm_bundles', true);
    }
}
