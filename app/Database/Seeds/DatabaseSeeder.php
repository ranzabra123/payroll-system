<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call('UsersSeeder');
        $this->call('EmployeesSeeder');
        $this->call('DeductionsConfigSeeder');
        $this->call('SettingsSeeder');
        $this->call('DepartmentsSeeder');
        $this->call('BranchesSeeder');
    }
}
