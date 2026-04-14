<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIsEnabledToEmployeeDeductions extends Migration
{
    public function up()
    {
        $this->db->query("ALTER TABLE employee_deductions ADD COLUMN is_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER status");
    }

    public function down()
    {
        $this->db->query("ALTER TABLE employee_deductions DROP COLUMN is_enabled");
    }
}
