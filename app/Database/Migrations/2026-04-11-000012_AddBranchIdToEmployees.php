<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBranchIdToEmployees extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('employees', [
            'branch_id' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'null'       => true,
                'default'    => null,
                'after'      => 'department',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('employees', 'branch_id');
    }
}
