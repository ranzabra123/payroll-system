<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'username'   => 'admin',
                'password'   => password_hash('admin123', PASSWORD_BCRYPT),
                'full_name'  => 'System Administrator',
                'role'       => 'admin',
                'status'     => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            [
                'username'   => 'hrstaff',
                'password'   => password_hash('hr1234', PASSWORD_BCRYPT),
                'full_name'  => 'HR Staff User',
                'role'       => 'hr',
                'status'     => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
        ];

        $this->db->table('users')->insertBatch($data);
    }
}
