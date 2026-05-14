<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSupportTables extends Migration
{
    public function up()
    {
        // ── Tickets ──────────────────────────────────────────
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'ticket_number' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => false],
            'hospital_id'   => ['type' => 'BIGINT',  'constraint' => 20, 'unsigned' => true],
            'subject'       => ['type' => 'VARCHAR', 'constraint' => 200],
            'category'      => ['type' => 'ENUM',    'constraint' => ['general','technical','billing','abha','opd','other'], 'default' => 'general'],
            'priority'      => ['type' => 'ENUM',    'constraint' => ['low','medium','high'], 'default' => 'medium'],
            'status'        => ['type' => 'ENUM',    'constraint' => ['open','in_progress','resolved','closed'], 'default' => 'open'],
            'created_by_user_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
            'message_count' => ['type' => 'INT',     'constraint' => 6,  'unsigned' => true, 'default' => 1],
            'last_reply_at' => ['type' => 'DATETIME','null' => true],
            'last_reply_by' => ['type' => 'VARCHAR', 'constraint' => 10,  'null' => true],
            'created_at'    => ['type' => 'DATETIME','null' => true],
            'updated_at'    => ['type' => 'DATETIME','null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('ticket_number');
        $this->forge->addKey('hospital_id');
        $this->forge->addKey('status');
        $this->forge->createTable('abdm_support_tickets');

        // ── Messages ─────────────────────────────────────────
        $this->forge->addField([
            'id'          => ['type' => 'BIGINT',  'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'ticket_id'   => ['type' => 'BIGINT',  'constraint' => 20, 'unsigned' => true],
            'message'     => ['type' => 'TEXT'],
            'sender_type' => ['type' => 'ENUM',    'constraint' => ['hospital','admin'], 'default' => 'hospital'],
            'sender_id'   => ['type' => 'BIGINT',  'constraint' => 20, 'unsigned' => true, 'null' => true],
            'sender_name' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'created_at'  => ['type' => 'DATETIME','null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('ticket_id');
        $this->forge->createTable('abdm_support_messages');
    }

    public function down()
    {
        $this->forge->dropTable('abdm_support_messages', true);
        $this->forge->dropTable('abdm_support_tickets', true);
    }
}
