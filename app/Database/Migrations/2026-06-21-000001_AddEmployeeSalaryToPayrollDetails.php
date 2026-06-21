<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEmployeeSalaryToPayrollDetails extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('payroll_details', [
            'employee_salary' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => 0.00,
                'null'       => false,
                'after'      => 'employee_id',
            ],
        ]);

        // Backfill existing rows: basic_pay = round(monthly_salary / 2), so
        // monthly_salary ≈ basic_pay * 2. This reconstructs the salary that was
        // actually in effect when each row was generated, far more accurately
        // than pulling the employee's current (possibly since-edited) salary.
        $this->db->query('UPDATE payroll_details SET employee_salary = basic_pay * 2 WHERE employee_salary = 0');
    }

    public function down(): void
    {
        $this->forge->dropColumn('payroll_details', 'employee_salary');
    }
}
