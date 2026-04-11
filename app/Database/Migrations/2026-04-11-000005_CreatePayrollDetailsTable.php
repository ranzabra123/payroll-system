<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePayrollDetailsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'payroll_id' => [
                'type'     => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'employee_id' => [
                'type'     => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'working_days' => [
                'type'    => 'TINYINT',
                'constraint' => 3,
                'default' => 0,
            ],
            'days_worked' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => '0.00',
            ],
            'whole_days' => [
                'type'    => 'TINYINT',
                'constraint' => 3,
                'default' => 0,
            ],
            'half_days' => [
                'type'    => 'TINYINT',
                'constraint' => 3,
                'default' => 0,
            ],
            'absent_days' => [
                'type'    => 'TINYINT',
                'constraint' => 3,
                'default' => 0,
            ],
            'overtime_hours' => [
                'type'       => 'DECIMAL',
                'constraint' => '6,2',
                'default'    => '0.00',
            ],
            'basic_pay' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => '0.00',
            ],
            'overtime_pay' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => '0.00',
            ],
            'gross_pay' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => '0.00',
            ],
            'sss_deduction' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => '0.00',
            ],
            'philhealth_deduction' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => '0.00',
            ],
            'pagibig_deduction' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => '0.00',
            ],
            'other_deductions' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => '0.00',
            ],
            'total_deductions' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => '0.00',
            ],
            'net_pay' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => '0.00',
            ],
            'created_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],
            'updated_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['payroll_id', 'employee_id']);
        $this->forge->addForeignKey('payroll_id', 'payroll', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('employee_id', 'employees', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('payroll_details');
    }

    public function down(): void
    {
        $this->forge->dropTable('payroll_details');
    }
}
