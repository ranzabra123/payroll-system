<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Extend the users.role ENUM to include manager and staff.
 */
class AddRolesToUsers extends Migration
{
    public function up(): void
    {
        $this->db->query("ALTER TABLE `users` MODIFY COLUMN `role` ENUM('admin','manager','staff','employee') NOT NULL DEFAULT 'staff'");
    }

    public function down(): void
    {
        $this->db->query("ALTER TABLE `users` MODIFY COLUMN `role` ENUM('admin','hr') NOT NULL DEFAULT 'hr'");
    }
}
