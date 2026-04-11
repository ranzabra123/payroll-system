<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DeductionsConfigSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            // SSS – employee share ~4.5% of MSC, capped at PHP 900
            [
                'deduction_type' => 'sss',
                'rate'           => 0.0450,
                'max_amount'     => 900.00,
                'min_amount'     => 135.00,
                'effective_date' => '2024-01-01',
                'is_active'      => 1,
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ],
            // PhilHealth – 2.5% employee share of MBS, cap PHP 5,000
            [
                'deduction_type' => 'philhealth',
                'rate'           => 0.0250,
                'max_amount'     => 5000.00,
                'min_amount'     => null,
                'effective_date' => '2024-01-01',
                'is_active'      => 1,
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ],
            // Pag-IBIG – 2% of MCS, capped at PHP 200
            [
                'deduction_type' => 'pagibig',
                'rate'           => 0.0200,
                'max_amount'     => 200.00,
                'min_amount'     => 100.00,
                'effective_date' => '2024-01-01',
                'is_active'      => 1,
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ],
        ];

        $this->db->table('deductions_config')->insertBatch($data);
    }
}
