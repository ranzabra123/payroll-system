<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDeductionsConfigTable extends Migration
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
            'deduction_type' => [
                'type'       => 'ENUM',
                'constraint' => ['sss', 'philhealth', 'pagibig'],
            ],
            'rate' => [
                'type'       => 'DECIMAL',
                'constraint' => '6,4',
                'default'    => '0.0000',
                'comment'    => 'Percentage as decimal e.g. 0.045 = 4.5%',
            ],
            'max_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
                'default'    => null,
                'comment'    => 'Maximum monthly cap',
            ],
            'min_amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'null'       => true,
                'default'    => null,
                'comment'    => 'Minimum monthly contribution',
            ],
            'effective_date' => [
                'type' => 'DATE',
            ],
            'is_active' => [
                'type'    => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
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
        $this->forge->createTable('deductions_config');
    }

    public function down(): void
    {
        $this->forge->dropTable('deductions_config');
    }
}
