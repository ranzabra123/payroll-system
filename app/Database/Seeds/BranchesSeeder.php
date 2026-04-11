<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class BranchesSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            ['name' => 'Main Store',          'address' => 'Zarraga, Iloilo'],
            ['name' => 'Pototan Branch',       'address' => 'Pototan, Iloilo'],
            ['name' => 'Sta. Barbara Branch',  'address' => 'Sta. Barbara, Iloilo'],
            ['name' => 'San Miguel Branch',    'address' => 'San Miguel, Iloilo'],
        ];

        foreach ($branches as $branch) {
            $exists = $this->db->table('branches')
                ->where('name', $branch['name'])
                ->get()->getRowArray();
            if (! $exists) {
                $this->db->table('branches')->insert(array_merge($branch, [
                    'is_active'  => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]));
            }
        }
    }
}
