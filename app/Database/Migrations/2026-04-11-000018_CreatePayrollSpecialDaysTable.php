<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePayrollSpecialDaysTable extends Migration
{
    public function up(): void
    {
        // Special day payroll adjustments
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'employee_id'     => ['type' => 'INT', 'unsigned' => true],
            'date'            => ['type' => 'DATE'],
            'adjustment_type' => ['type' => 'ENUM', 'constraint' => ['fixed_amount', 'double_salary'], 'default' => 'fixed_amount'],
            'amount'          => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'reason'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status'          => ['type' => 'ENUM', 'constraint' => ['pending', 'applied'], 'default' => 'pending'],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('employee_id');
        $this->forge->addKey('date');
        $this->forge->addKey('status');
        $this->forge->createTable('payroll_special_days');

        // Add special_adjustments column to payroll_details
        $this->forge->addColumn('payroll_details', [
            'special_adjustments' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => 0.00,
                'after'      => 'gross_pay',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('payroll_special_days', true);
        $this->forge->dropColumn('payroll_details', 'special_adjustments');
    }
}
