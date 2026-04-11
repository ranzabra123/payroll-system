<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class EmployeesSeeder extends Seeder
{
    public function run(): void
    {
        $employees = [
            [
                'employee_code'     => 'EMP-001',
                'full_name'         => 'Juan Dela Cruz',
                'position'          => 'Software Developer',
                'department'        => 'IT',
                'monthly_salary'    => 35000.00,
                'daily_rate'        => 35000 / 22,
                'date_hired'        => '2022-01-15',
                'sss_number'        => '33-1234567-8',
                'philhealth_number' => '12-345678901-2',
                'pagibig_number'    => '1234-5678-9012',
                'tin_number'        => '123-456-789-000',
                'status'            => 'active',
                'created_at'        => date('Y-m-d H:i:s'),
                'updated_at'        => date('Y-m-d H:i:s'),
            ],
            [
                'employee_code'     => 'EMP-002',
                'full_name'         => 'Maria Santos',
                'position'          => 'Accountant',
                'department'        => 'Finance',
                'monthly_salary'    => 28000.00,
                'daily_rate'        => 28000 / 22,
                'date_hired'        => '2021-06-01',
                'sss_number'        => '33-9876543-2',
                'philhealth_number' => '12-987654321-0',
                'pagibig_number'    => '9876-5432-1098',
                'tin_number'        => '987-654-321-000',
                'status'            => 'active',
                'created_at'        => date('Y-m-d H:i:s'),
                'updated_at'        => date('Y-m-d H:i:s'),
            ],
            [
                'employee_code'     => 'EMP-003',
                'full_name'         => 'Pedro Reyes',
                'position'          => 'HR Officer',
                'department'        => 'Human Resources',
                'monthly_salary'    => 25000.00,
                'daily_rate'        => 25000 / 22,
                'date_hired'        => '2023-03-10',
                'sss_number'        => '33-1122334-5',
                'philhealth_number' => '12-112233445-6',
                'pagibig_number'    => '1122-3344-5566',
                'tin_number'        => '112-233-445-000',
                'status'            => 'active',
                'created_at'        => date('Y-m-d H:i:s'),
                'updated_at'        => date('Y-m-d H:i:s'),
            ],
            [
                'employee_code'     => 'EMP-004',
                'full_name'         => 'Ana Lim',
                'position'          => 'Marketing Specialist',
                'department'        => 'Marketing',
                'monthly_salary'    => 22000.00,
                'daily_rate'        => 22000 / 22,
                'date_hired'        => '2023-08-20',
                'sss_number'        => '33-5566778-9',
                'philhealth_number' => '12-556677889-0',
                'pagibig_number'    => '5566-7788-9900',
                'tin_number'        => '556-677-889-000',
                'status'            => 'active',
                'created_at'        => date('Y-m-d H:i:s'),
                'updated_at'        => date('Y-m-d H:i:s'),
            ],
            [
                'employee_code'     => 'EMP-005',
                'full_name'         => 'Carlos Garcia',
                'position'          => 'Operations Manager',
                'department'        => 'Operations',
                'monthly_salary'    => 50000.00,
                'daily_rate'        => 50000 / 22,
                'date_hired'        => '2020-02-14',
                'sss_number'        => '33-9988776-1',
                'philhealth_number' => '12-998877665-3',
                'pagibig_number'    => '9988-7766-5544',
                'tin_number'        => '998-877-665-000',
                'status'            => 'active',
                'created_at'        => date('Y-m-d H:i:s'),
                'updated_at'        => date('Y-m-d H:i:s'),
            ],
        ];

        $this->db->table('employees')->insertBatch($employees);
    }
}
