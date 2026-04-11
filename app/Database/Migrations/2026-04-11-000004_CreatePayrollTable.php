<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePayrollTable extends Migration
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
            'payroll_month' => [
                'type'       => 'VARCHAR',
                'constraint' => 7, // e.g. 2026-04
            ],
            'cutoff' => [
                'type'       => 'TINYINT',
                'constraint' => 1,    // 1 = 1-15, 2 = 16-end
            ],
            'period_start' => [
                'type' => 'DATE',
            ],
            'period_end' => [
                'type' => 'DATE',
            ],
            'working_days' => [
                'type'    => 'TINYINT',
                'constraint' => 3,
                'default' => 0,
            ],
            'total_gross' => [
                'type'       => 'DECIMAL',
                'constraint' => '14,2',
                'default'    => '0.00',
            ],
            'total_deductions' => [
                'type'       => 'DECIMAL',
                'constraint' => '14,2',
                'default'    => '0.00',
            ],
            'total_net' => [
                'type'       => 'DECIMAL',
                'constraint' => '14,2',
                'default'    => '0.00',
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['draft', 'finalized'],
                'default'    => 'draft',
            ],
            'created_by' => [
                'type'     => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null'     => true,
                'default'  => null,
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
        $this->forge->addUniqueKey(['payroll_month', 'cutoff']);
        $this->forge->createTable('payroll');
    }

    public function down(): void
    {
        $this->forge->dropTable('payroll');
    }
}
