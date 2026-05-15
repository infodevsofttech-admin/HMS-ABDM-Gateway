<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFacilityQrToHospitals extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('abdm_hospitals', [
            'facility_qr_data' => [
                'type'    => 'MEDIUMTEXT',
                'null'    => true,
                'after'   => 'hfr_id',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('abdm_hospitals', 'facility_qr_data');
    }
}
