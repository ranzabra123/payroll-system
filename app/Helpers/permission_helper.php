<?php

/**
 * Check if the currently logged-in user is allowed to perform an action on a module.
 *
 * Usage:
 *   can_do('employees', 'view')   → true/false
 *   can_do('payroll', 'delete')
 *
 * Admin always returns true. manager/staff check role_permissions cache in session.
 */
if (! function_exists('can_do')) {
    function can_do(string $module, string $action): bool
    {
        $role = session()->get('role');

        if ($role === 'admin') {
            return true;
        }

        // Load permissions into session cache on first call
        $permKey = 'role_perms_' . $role;
        $perms   = session()->get($permKey);

        if ($perms === null) {
            $perms = (new \App\Models\RolePermissionModel())->getForRole($role);
            session()->set($permKey, $perms);
        }

        $modulePerms = $perms[$module] ?? [];
        return (bool) ($modulePerms['can_' . $action] ?? false);
    }
}

/**
 * Convenience: redirect with 403 error if the user can't perform the action.
 * Call from controller methods.
 */
if (! function_exists('require_permission')) {
    function require_permission(string $module, string $action = 'view'): ?\CodeIgniter\HTTP\RedirectResponse
    {
        if (! can_do($module, $action)) {
            return redirect()->to(site_url('dashboard'))
                             ->with('error', 'You do not have permission to perform this action.');
        }
        return null;
    }
}

/**
 * Return the branch_id the logged-in user is restricted to, or null if unrestricted.
 * Admin is always unrestricted (returns null).
 */
if (! function_exists('user_branch_id')) {
    function user_branch_id(): ?int
    {
        if (session()->get('role') === 'admin') {
            return null;
        }
        $bid = session()->get('branch_id');
        return $bid ? (int) $bid : null;
    }
}

/**
 * Check if a given employee (or record) is accessible to the current user based on branch.
 * Pass the employee's branch_id. Returns true if allowed.
 */
if (! function_exists('can_access_branch')) {
    function can_access_branch(?int $employeeBranchId): bool
    {
        $restricted = user_branch_id();
        if ($restricted === null) {
            return true; // no restriction
        }
        return (int) $employeeBranchId === $restricted;
    }
}
