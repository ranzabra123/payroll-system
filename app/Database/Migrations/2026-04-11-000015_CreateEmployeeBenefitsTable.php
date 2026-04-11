<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEmployeeBenefitsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'employee_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'benefit_type'   => ['type' => 'VARCHAR', 'constraint' => 100,
                                 'comment' => 'SSS, PhilHealth, Pag-IBIG, HMO, Life Insurance, Other'],
            'cutoff'         => ['type' => 'ENUM', 'constraint' => ['15', '30'], 'default' => '30',
                                 'comment' => 'Deduct on 15th or 30th cutoff'],
            'employee_share' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],
            'employer_share' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],
            'effective_date' => ['type' => 'DATE'],
            'status'         => ['type' => 'ENUM', 'constraint' => ['active', 'inactive'], 'default' => 'active'],
            'notes'          => ['type' => 'TEXT', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('employee_id');
        $this->forge->createTable('employee_benefits');
    }

    public function down(): void
    {
        $this->forge->dropTable('employee_benefits');
    }
}
