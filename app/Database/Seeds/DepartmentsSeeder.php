<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DepartmentsSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'Information Technology', 'description' => 'IT and software development'],
            ['name' => 'Finance',                 'description' => 'Accounting and financial services'],
            ['name' => 'Human Resources',         'description' => 'HR, recruitment, and employee relations'],
            ['name' => 'Marketing',               'description' => 'Marketing and communications'],
            ['name' => 'Operations',              'description' => 'Operations and logistics'],
            ['name' => 'Administration',          'description' => 'General administration and support'],
        ];

        foreach ($departments as $dept) {
            $exists = $this->db->table('departments')
                ->where('name', $dept['name'])
                ->get()->getRowArray();
            if (! $exists) {
                $this->db->table('departments')->insert(array_merge($dept, [
                    'is_active'  => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]));
            }
        }
    }
}
