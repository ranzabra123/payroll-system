<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEmployeeDeductionsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'employee_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'type'             => ['type' => 'ENUM', 'constraint' => ['Cash Advance', 'Debt'], 'default' => 'Cash Advance'],
            'description'      => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'total_amount'     => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'amount_per_cutoff'=> ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'cutoff'           => ['type' => 'ENUM', 'constraint' => ['15', '30'], 'default' => '30',
                                   'comment' => 'Deduct on every 15th (1st) or 30th (2nd) cutoff'],
            'remaining_balance'=> ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'status'           => ['type' => 'ENUM', 'constraint' => ['active', 'completed', 'cancelled'], 'default' => 'active'],
            'start_date'       => ['type' => 'DATE'],
            'notes'            => ['type' => 'TEXT', 'null' => true],
            'created_at'       => ['type' => 'DATETIME', 'null' => true],
            'updated_at'       => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('employee_id');
        $this->forge->createTable('employee_deductions');
    }

    public function down(): void
    {
        $this->forge->dropTable('employee_deductions');
    }
}
