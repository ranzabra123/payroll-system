<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddWorkingDaysToDepartments extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('departments', [
            'working_days' => [
                'type'       => 'TINYINT',
                'constraint' => 2,
                'unsigned'   => true,
                'default'    => 26,
                'after'      => 'description',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('departments', 'working_days');
    }
}
