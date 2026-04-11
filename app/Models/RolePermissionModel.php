<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Manages per-role module permissions for manager and staff roles.
 * Admin always has full access and is never stored here.
 */
class RolePermissionModel extends Model
{
    protected $table         = 'role_permissions';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'role', 'module', 'can_view', 'can_add', 'can_edit', 'can_delete', 'updated_at',
    ];

    protected $useTimestamps = false;

    /**
     * The full list of modules and their display labels.
     */
    public static function modules(): array
    {
        return [
            'dashboard'    => 'Dashboard',
            'employees'    => 'Employees',
            'attendance'   => 'Attendance',
            'payroll'      => 'Payroll',
            'deductions'   => 'Deductions',
            'benefits'     => 'Benefits',
            'special_days' => 'Special Days',
            'reports'      => 'Reports',
        ];
    }

    /**
     * Get all permissions for a role, keyed by module name.
     * Returns: ['employees' => ['can_view'=>1, 'can_add'=>0, ...], ...]
     */
    public function getForRole(string $role): array
    {
        $rows   = $this->where('role', $role)->findAll();
        $result = [];
        foreach ($rows as $row) {
            $result[$row['module']] = [
                'can_view'   => (bool) $row['can_view'],
                'can_add'    => (bool) $row['can_add'],
                'can_edit'   => (bool) $row['can_edit'],
                'can_delete' => (bool) $row['can_delete'],
            ];
        }
        return $result;
    }

    /**
     * Upsert all permissions for a role from a flat POST data array.
     * Expected format: perms[module][action] = '1'
     */
    public function saveForRole(string $role, array $perms): void
    {
        $now = date('Y-m-d H:i:s');
        foreach (array_keys(self::modules()) as $module) {
            $existing = $this->where('role', $role)->where('module', $module)->first();
            $data = [
                'role'       => $role,
                'module'     => $module,
                'can_view'   => ! empty($perms[$module]['can_view'])   ? 1 : 0,
                'can_add'    => ! empty($perms[$module]['can_add'])    ? 1 : 0,
                'can_edit'   => ! empty($perms[$module]['can_edit'])   ? 1 : 0,
                'can_delete' => ! empty($perms[$module]['can_delete']) ? 1 : 0,
                'updated_at' => $now,
            ];
            if ($existing) {
                $this->update($existing['id'], $data);
            } else {
                $this->db->table('role_permissions')->insert($data);
            }
        }
    }
}
