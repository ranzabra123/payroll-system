<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAbsentDeductionToPayrollDetails extends Migration
{
    public function up(): void
    {
        $fields = [
            'absent_deduction' => [
                'type'       => 'DECIMAL',
                'constraint' => '14,2',
                'default'    => 0.00,
                'null'       => false,
                'after'      => 'gross_pay',
            ],
        ];
        $this->forge->addColumn('payroll_details', $fields);
    }

    public function down(): void
    {
        $this->forge->dropColumn('payroll_details', 'absent_deduction');
    }
}
