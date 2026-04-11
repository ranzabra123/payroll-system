<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RolePermissionsSeeder extends Seeder
{
    public function run()
    {
        $defaults = [
            // Managers can view, add, edit most things; no delete
            'manager' => [
                'dashboard'   => [1, 0, 0, 0],
                'employees'   => [1, 1, 1, 0],
                'attendance'  => [1, 1, 1, 0],
                'payroll'     => [1, 1, 1, 0],
                'deductions'  => [1, 1, 1, 0],
                'benefits'    => [1, 1, 1, 0],
                'special_days'=> [1, 1, 1, 0],
                'reports'     => [1, 0, 0, 0],
            ],
            // Staff can only view attendance and dashboard
            'staff' => [
                'dashboard'   => [1, 0, 0, 0],
                'employees'   => [1, 0, 0, 0],
                'attendance'  => [1, 1, 0, 0],
                'payroll'     => [0, 0, 0, 0],
                'deductions'  => [0, 0, 0, 0],
                'benefits'    => [0, 0, 0, 0],
                'special_days'=> [0, 0, 0, 0],
                'reports'     => [0, 0, 0, 0],
            ],
            // Employee can only view their own dashboard and payroll info
            'employee' => [
                'dashboard'   => [1, 0, 0, 0],
                'employees'   => [0, 0, 0, 0],
                'attendance'  => [1, 0, 0, 0],
                'payroll'     => [1, 0, 0, 0],
                'deductions'  => [1, 0, 0, 0],
                'benefits'    => [1, 0, 0, 0],
                'special_days'=> [0, 0, 0, 0],
                'reports'     => [0, 0, 0, 0],
            ],
        ];

        $db = \Config\Database::connect();

        foreach ($defaults as $role => $modules) {
            foreach ($modules as $module => [$view, $add, $edit, $delete]) {
                $db->table('role_permissions')->replace([
                    'role'       => $role,
                    'module'     => $module,
                    'can_view'   => $view,
                    'can_add'    => $add,
                    'can_edit'   => $edit,
                    'can_delete' => $delete,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        echo "Role permissions seeded.\n";
    }
}
