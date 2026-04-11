<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBenefitsDeductionToPayrollDetails extends Migration
{
    public function up(): void
    {
        $fields = [
            'benefits_deduction' => [
                'type'       => 'DECIMAL',
                'constraint' => '14,2',
                'default'    => 0.00,
                'null'       => false,
                'after'      => 'other_deductions',
            ],
        ];
        $this->forge->addColumn('payroll_details', $fields);
    }

    public function down(): void
    {
        $this->forge->dropColumn('payroll_details', 'benefits_deduction');
    }
}
