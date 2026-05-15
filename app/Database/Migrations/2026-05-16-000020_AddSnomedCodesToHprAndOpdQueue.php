<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSnomedCodesToHprAndOpdQueue extends Migration
{
    public function up(): void
    {
        // SNOMED CT concept ID for the professional's specialization
        $this->forge->addColumn('hospital_hpr_professionals', [
            'specialization_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
                'after'      => 'specialization',
            ],
        ]);

        // SNOMED CT concept ID for the OPD token department/context
        $this->forge->addColumn('abdm_token_queue', [
            'department_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
                'after'      => 'context',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('hospital_hpr_professionals', 'specialization_code');
        $this->forge->dropColumn('abdm_token_queue', 'department_code');
    }
}
