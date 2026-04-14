<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddContributionsToEmployees extends Migration
{
    public function up(): void
    {
        $fields = [
            'sss_contribution' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => 0.00,
                'null'       => false,
                'after'      => 'sss_number',
            ],
            'philhealth_contribution' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => 0.00,
                'null'       => false,
                'after'      => 'philhealth_number',
            ],
            'pagibig_contribution' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => 0.00,
                'null'       => false,
                'after'      => 'pagibig_number',
            ],
        ];

        $this->forge->addColumn('employees', $fields);
    }

    public function down(): void
    {
        $this->forge->dropColumn('employees', ['sss_contribution', 'philhealth_contribution', 'pagibig_contribution']);
    }
}
