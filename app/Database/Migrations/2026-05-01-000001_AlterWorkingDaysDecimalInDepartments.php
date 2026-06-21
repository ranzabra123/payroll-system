<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterWorkingDaysDecimalInDepartments extends Migration
{
    public function up(): void
    {
        $this->forge->modifyColumn('departments', [
            'working_days' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => 26.00,
                'null'       => false,
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->modifyColumn('departments', [
            'working_days' => [
                'type'       => 'TINYINT',
                'constraint' => 2,
                'unsigned'   => true,
                'default'    => 26,
                'null'       => false,
            ],
        ]);
    }
}
