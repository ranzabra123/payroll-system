<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDeductionHistoryTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'employee_deduction_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'payroll_id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'payroll_cutoff'        => ['type' => 'TINYINT', 'constraint' => 1],
            'period_start'          => ['type' => 'DATE'],
            'period_end'            => ['type' => 'DATE'],
            'amount_deducted'       => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'balance_before'        => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'balance_after'         => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'created_at'            => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('employee_deduction_id');
        $this->forge->addKey('payroll_id');
        $this->forge->createTable('deduction_history');
    }

    public function down()
    {
        $this->forge->dropTable('deduction_history');
    }
}
