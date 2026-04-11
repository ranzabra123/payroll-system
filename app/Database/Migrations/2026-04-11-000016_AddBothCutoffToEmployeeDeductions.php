<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBothCutoffToEmployeeDeductions extends Migration
{
    public function up(): void
    {
        $this->db->query("ALTER TABLE employee_deductions MODIFY COLUMN cutoff ENUM('15','30','both') NOT NULL DEFAULT '30'");
    }

    public function down(): void
    {
        $this->db->query("ALTER TABLE employee_deductions MODIFY COLUMN cutoff ENUM('15','30') NOT NULL DEFAULT '30'");
    }
}
