<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ExpandSpecializationToText extends Migration
{
    public function up(): void
    {
        // Expand specialization to TEXT so multiple specializations can be stored as JSON
        $this->forge->modifyColumn('hospital_hpr_professionals', [
            'specialization' => [
                'name' => 'specialization',
                'type' => 'TEXT',
                'null' => true,
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->modifyColumn('hospital_hpr_professionals', [
            'specialization' => [
                'name'       => 'specialization',
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'null'       => true,
            ],
        ]);
    }
}
