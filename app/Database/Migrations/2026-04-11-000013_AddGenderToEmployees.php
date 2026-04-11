<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddGenderToEmployees extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('employees', [
            'gender' => [
                'type'       => 'ENUM',
                'constraint' => ['Male', 'Female', 'Other'],
                'null'       => true,
                'default'    => null,
                'after'      => 'date_hired',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('employees', 'gender');
    }
}
