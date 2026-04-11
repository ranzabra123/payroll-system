<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAttendanceTable extends Migration
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
            'employee_id' => [
                'type'     => 'INT',
                'constraint' => 11,
                'unsigned'  => true,
            ],
            'attendance_date' => [
                'type' => 'DATE',
            ],
            // whole_day | half_am | half_pm | absent
            'attendance_type' => [
                'type'       => 'ENUM',
                'constraint' => ['whole_day', 'half_am', 'half_pm', 'absent'],
                'default'    => 'whole_day',
            ],
            'overtime_hours' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => '0.00',
            ],
            'is_holiday' => [
                'type'    => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'is_special_holiday' => [
                'type'    => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'remarks' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
            ],
            'created_by' => [
                'type'     => 'INT',
                'constraint' => 11,
                'unsigned'  => true,
                'null'      => true,
                'default'   => null,
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
        $this->forge->addKey(['employee_id', 'attendance_date']);
        $this->forge->addForeignKey('employee_id', 'employees', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('attendance');
    }

    public function down(): void
    {
        $this->forge->dropTable('attendance');
    }
}
