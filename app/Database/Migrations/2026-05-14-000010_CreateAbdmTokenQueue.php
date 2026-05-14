<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAbdmTokenQueue extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'hospital_id'         => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'abha_number'         => ['type' => 'VARCHAR', 'constraint' => 20,  'null' => true],
            'abha_address'        => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'patient_name'        => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'gender'              => ['type' => 'VARCHAR', 'constraint' => 10,  'null' => true],
            'day_of_birth'        => ['type' => 'VARCHAR', 'constraint' => 5,   'null' => true],
            'month_of_birth'      => ['type' => 'VARCHAR', 'constraint' => 5,   'null' => true],
            'year_of_birth'       => ['type' => 'VARCHAR', 'constraint' => 6,   'null' => true],
            'phone'               => ['type' => 'VARCHAR', 'constraint' => 20,  'null' => true],
            'hip_id'              => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'context'             => ['type' => 'VARCHAR', 'constraint' => 50,  'null' => true],
            'hpr_id'              => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'token_number'        => ['type' => 'INT', 'constraint' => 6, 'unsigned' => true, 'null' => true],
            'token_date'          => ['type' => 'DATE', 'null' => true],
            'status'              => ['type' => 'VARCHAR', 'constraint' => 30,  'default' => 'PENDING'],
            'request_id'          => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'on_share_sent'       => ['type' => 'TINYINT', 'constraint' => 1,   'default' => 0],
            'share_request_json'  => ['type' => 'TEXT', 'null' => true],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
            'updated_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['token_date', 'token_number']);
        $this->forge->addKey('abha_number');
        $this->forge->addKey('created_at');

        $this->forge->createTable('abdm_token_queue', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('abdm_token_queue', true);
    }
}
