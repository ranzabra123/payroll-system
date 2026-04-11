<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds the standard Philippine government mandatory benefits:
 * SSS, PhilHealth, and Pag-IBIG.
 *
 * Amounts are based on common 2025/2026 contribution ranges and can
 * be edited by HR via the Benefits management page after seeding.
 */
class BenefitsSeeder extends Seeder
{
    public function run(): void
    {
        $db  = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');

        $benefits = [
            [
                'name'           => 'SSS',
                'description'    => 'Social Security System — mandatory monthly contribution for private sector employees.',
                'employee_share' => 1125.00,   // employee contribution (4.5% of ~₱25k MSC)
                'employer_share' => 2375.00,   // employer contribution (9.5%)
                'is_active'      => 1,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                'name'           => 'PhilHealth',
                'description'    => 'Philippine Health Insurance Corporation — mandatory health insurance contribution.',
                'employee_share' => 500.00,    // 2.5% of ₱20k salary
                'employer_share' => 500.00,    // 2.5% employer share
                'is_active'      => 1,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                'name'           => 'Pag-IBIG',
                'description'    => 'Home Development Mutual Fund — mandatory savings and housing fund.',
                'employee_share' => 100.00,    // standard ₱100 max employee share
                'employer_share' => 100.00,    // matching employer contribution
                'is_active'      => 1,
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
        ];

        foreach ($benefits as $benefit) {
            $exists = $db->table('benefits')
                         ->where('name', $benefit['name'])
                         ->countAllResults();
            if (! $exists) {
                $db->table('benefits')->insert($benefit);
                echo "Inserted: {$benefit['name']}\n";
            } else {
                echo "Skipped (already exists): {$benefit['name']}\n";
            }
        }
    }
}
