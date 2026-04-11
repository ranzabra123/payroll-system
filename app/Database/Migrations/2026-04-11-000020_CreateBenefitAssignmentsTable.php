<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBenefitAssignmentsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 10,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'benefit_id' => [
                'type'     => 'INT',
                'constraint' => 10,
                'unsigned' => true,
                'null'     => false,
            ],
            // 'department' or 'employee'
            'scope' => [
                'type'       => 'ENUM',
                'constraint' => ['department', 'employee'],
                'default'    => 'employee',
                'null'       => false,
            ],
            'department' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'employee_id' => [
                'type'     => 'INT',
                'constraint' => 10,
                'unsigned' => true,
                'null'     => true,
            ],
            // both = every payroll run (halved each time)
            // 15   = only 1st cutoff
            // 30   = only 2nd cutoff / end of month
            'cutoff' => [
                'type'       => 'ENUM',
                'constraint' => ['both', '15', '30'],
                'default'    => 'both',
                'null'       => false,
            ],
            'effective_date' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['active', 'inactive'],
                'default'    => 'active',
                'null'       => false,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('benefit_id');
        $this->forge->addKey('employee_id');
        $this->forge->addForeignKey('benefit_id', 'benefits', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('benefit_assignments');
    }

    public function down(): void
    {
        $this->forge->dropTable('benefit_assignments', true);
    }
}
