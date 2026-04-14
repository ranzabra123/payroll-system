<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Restructure benefits table to support per-employee contribution records.
 * Adds employee_id, amount, employer_contribution columns and removes the
 * unique constraint on `name` so multiple employees can share the same type.
 */
class RestructureBenefitsTable extends Migration
{
    public function up(): void
    {
        // Drop unique key on name (MySQL key is usually named 'name')
        try {
            $this->db->query('ALTER TABLE `benefits` DROP INDEX `name`');
        } catch (\Throwable $e) {
            // May already be dropped or named differently — continue
        }

        $fields = [
            'employee_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
                'default'    => null,
                'after'      => 'name',
            ],
            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => 0.00,
                'null'       => false,
                'after'      => 'employee_id',
            ],
            'employer_contribution' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
                'default'    => 0.00,
                'null'       => false,
                'after'      => 'amount',
            ],
        ];

        $this->forge->addColumn('benefits', $fields);

        // Index for fast per-employee lookups
        $this->db->query('ALTER TABLE `benefits` ADD INDEX `idx_benefits_employee` (`employee_id`)');
    }

    public function down(): void
    {
        try {
            $this->db->query('ALTER TABLE `benefits` DROP INDEX `idx_benefits_employee`');
        } catch (\Throwable $e) {}

        $this->forge->dropColumn('benefits', ['employee_id', 'amount', 'employer_contribution']);

        // Restore unique constraint on name (best-effort)
        try {
            $this->db->query('ALTER TABLE `benefits` ADD UNIQUE KEY `name` (`name`)');
        } catch (\Throwable $e) {}
    }
}
