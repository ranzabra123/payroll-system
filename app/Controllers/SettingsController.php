<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\SettingModel;
use App\Models\DepartmentModel;
use App\Models\BranchModel;
use App\Models\AuditLogModel;
use App\Models\RolePermissionModel;

class SettingsController extends Controller
{
    protected SettingModel    $settings;
    protected DepartmentModel $deptModel;
    protected BranchModel     $branchModel;
    protected AuditLogModel   $audit;

    public function __construct()
    {
        $this->settings    = new SettingModel();
        $this->deptModel   = new DepartmentModel();
        $this->branchModel = new BranchModel();
        $this->audit       = new AuditLogModel();

        // Admin only
        if (session()->get('role') !== 'admin') {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
    }

    /** Control Panel – show all settings tabs. */
    public function index()
    {
        $permModel = new RolePermissionModel();
        return view('settings/index', [
            'title'          => 'Control Panel',
            'cfg'            => $this->settings->getAllKeyed(),
            'departments'    => $this->deptModel->orderBy('name', 'ASC')->findAll(),
            'branches'       => $this->branchModel->orderBy('name', 'ASC')->findAll(),
            'permModules'    => $permModel->modules(),
            'managerPerms'   => $permModel->getForRole('manager'),
            'staffPerms'     => $permModel->getForRole('staff'),
            'employeePerms'  => $permModel->getForRole('employee'),
        ]);
    }

    /** Save role permissions. */
    public function savePermissions()
    {
        $role  = $this->request->getPost('role');
        $perms = $this->request->getPost('perms') ?? [];

        if (! in_array($role, ['manager', 'staff', 'employee'], true)) {
            return redirect()->to(site_url('settings') . '#permissions')->with('error', 'Invalid role.');
        }

        (new RolePermissionModel())->saveForRole($role, $perms);
        $this->audit->logAction('Settings', 'update_permissions_' . $role, null, null, null,
            "Updated permissions for role: " . ucfirst($role));

        return redirect()->to(site_url('settings') . '#permissions')
            ->with('success', ucfirst($role) . ' permissions updated.');
    }

    /** Save General settings (company name, tagline). */
    public function saveGeneral()
    {
        $name    = $this->request->getPost('company_name', FILTER_SANITIZE_SPECIAL_CHARS);
        $tagline = $this->request->getPost('company_tagline', FILTER_SANITIZE_SPECIAL_CHARS);

        if (empty($name)) {
            return redirect()->to(site_url('settings') . '#general')
                ->with('error', 'Company name cannot be empty.');
        }

        $this->settings->setValue('company_name',    $name);
        $this->settings->setValue('company_tagline', $tagline);
        $this->audit->logAction('Settings', 'update_general', null, null, null,
            "Updated general settings: company name set to '{$name}'");

        return redirect()->to(site_url('settings'))->with('success', 'General settings saved.');
    }

    /** Upload / replace company logo. */
    public function uploadLogo()
    {
        $file = $this->request->getFile('logo');

        if (! $file || ! $file->isValid() || $file->hasMoved()) {
            return redirect()->to(site_url('settings') . '#logo')
                ->with('error', 'No valid file uploaded.');
        }

        // Validate: image only, max 2 MB
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        if (! in_array($file->getMimeType(), $allowed, true)) {
            return redirect()->to(site_url('settings') . '#logo')
                ->with('error', 'Only image files are allowed (JPG, PNG, GIF, WEBP, SVG).');
        }
        if ($file->getSizeByUnit('mb') > 2) {
            return redirect()->to(site_url('settings') . '#logo')
                ->with('error', 'Logo must be under 2 MB.');
        }

        // Remove old logo if it exists
        $oldPath = $this->settings->getValue('logo_path');
        if ($oldPath && file_exists(FCPATH . $oldPath)) {
            @unlink(FCPATH . $oldPath);
        }

        // Move to public/assets/uploads/
        $uploadDir  = FCPATH . 'assets/uploads/';
        if (! is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $newName = 'logo_' . time() . '.' . $file->getExtension();
        $file->move($uploadDir, $newName);

        $relativePath = 'assets/uploads/' . $newName;
        $this->settings->setValue('logo_path', $relativePath);
        $this->audit->logAction('Settings', 'upload_logo', null, null, null, 'Uploaded company logo');

        return redirect()->to(site_url('settings'))->with('success', 'Logo updated successfully.');
    }

    /** Remove the current logo. */
    public function removeLogo()
    {
        $oldPath = $this->settings->getValue('logo_path');
        if ($oldPath && file_exists(FCPATH . $oldPath)) {
            @unlink(FCPATH . $oldPath);
        }
        $this->settings->setValue('logo_path', null);
        $this->audit->logAction('Settings', 'remove_logo', null, null, null, 'Removed company logo');

        return redirect()->to(site_url('settings'))->with('success', 'Logo removed.');
    }

    /** Save theme colors. */
    public function saveColors()
    {
        $keys = ['sidebar_bg', 'sidebar_text', 'accent_color', 'topbar_bg'];
        foreach ($keys as $key) {
            $val = $this->request->getPost($key, FILTER_SANITIZE_SPECIAL_CHARS);
            if ($val && preg_match('/^#[0-9a-fA-F]{3,8}$/', $val)) {
                $this->settings->setValue($key, $val);
            }
        }
        $this->audit->logAction('Settings', 'update_colors', null, null, null, 'Updated theme/sidebar colors');

        return redirect()->to(site_url('settings'))->with('success', 'Theme colors saved.');
    }

    /** Add a new department. */
    public function addDepartment()
    {
        $name = trim($this->request->getPost('name', FILTER_SANITIZE_SPECIAL_CHARS));
        $desc = trim($this->request->getPost('description', FILTER_SANITIZE_SPECIAL_CHARS));

        if (empty($name)) {
            return redirect()->to(site_url('settings') . '#departments')
                ->with('error', 'Department name is required.');
        }

        // Check duplicate
        $exists = $this->deptModel->where('name', $name)->first();
        if ($exists) {
            return redirect()->to(site_url('settings') . '#departments')
                ->with('error', "Department '{$name}' already exists.");
        }

        $newId = $this->deptModel->insert(['name' => $name, 'description' => $desc, 'is_active' => 1, 'working_days' => max(1, (int) $this->request->getPost('working_days'))]);
        $this->audit->logAction('Departments', 'create', $newId, null, ['name' => $name, 'description' => $desc], "Added department '{$name}'");

        return redirect()->to(site_url('settings') . '#departments')
            ->with('success', "Department '{$name}' added.");
    }

    /** Edit an existing department. */
    public function editDepartment(int $id)
    {
        $dept = $this->deptModel->find($id);
        if (! $dept) {
            return redirect()->to(site_url('settings') . '#departments')->with('error', 'Department not found.');
        }

        $name = trim($this->request->getPost('name', FILTER_SANITIZE_SPECIAL_CHARS));
        $desc = trim($this->request->getPost('description', FILTER_SANITIZE_SPECIAL_CHARS));

        if (empty($name)) {
            return redirect()->to(site_url('settings') . '#departments')->with('error', 'Department name is required.');
        }

        // Check duplicate against other records
        $exists = $this->deptModel->where('name', $name)->where('id !=', $id)->first();
        if ($exists) {
            return redirect()->to(site_url('settings') . '#departments')
                ->with('error', "Another department named '{$name}' already exists.");
        }

        $oldDept = $this->deptModel->find($id);
        $this->deptModel->update($id, ['name' => $name, 'description' => $desc, 'working_days' => max(1, (int) $this->request->getPost('working_days'))]);
        $this->audit->logAction('Departments', 'update', $id,
            ['name' => $oldDept['name'] ?? ''],
            ['name' => $name, 'description' => $desc],
            "Updated department: '{$name}'");

        return redirect()->to(site_url('settings') . '#departments')
            ->with('success', "Department updated to '{$name}'.");
    }

    /** Toggle department active/inactive. */
    public function toggleDepartment(int $id)
    {
        $dept = $this->deptModel->find($id);
        if (! $dept) {
            return redirect()->to(site_url('settings') . '#departments')->with('error', 'Department not found.');
        }
        $newStatus = $dept['is_active'] ? 0 : 1;
        $this->deptModel->update($id, ['is_active' => $newStatus]);
        $this->audit->logAction('Departments', 'toggle', $id, null, null,
            "Toggled department '{$dept['name']}' to " . ($newStatus ? 'active' : 'inactive'));

        return redirect()->to(site_url('settings') . '#departments')
            ->with('success', 'Department status updated.');
    }

    /** Delete a department. */
    public function deleteDepartment(int $id)
    {
        $dept = $this->deptModel->find($id);
        if (! $dept) {
            return redirect()->to(site_url('settings') . '#departments')->with('error', 'Department not found.');
        }
        $this->deptModel->delete($id);
        $this->audit->logAction('Departments', 'delete', $id, $dept, null, "Deleted department '{$dept['name']}'");

        return redirect()->to(site_url('settings') . '#departments')
            ->with('success', "Department '{$dept['name']}' deleted.");
    }

    // ========== BRANCH METHODS ==========

    /** Add a new branch. */
    public function addBranch()
    {
        $name    = trim($this->request->getPost('name', FILTER_SANITIZE_SPECIAL_CHARS));
        $address = trim($this->request->getPost('address', FILTER_SANITIZE_SPECIAL_CHARS));

        if (empty($name)) {
            return redirect()->to(site_url('settings') . '#branches')->with('error', 'Branch name is required.');
        }
        $exists = $this->branchModel->where('name', $name)->first();
        if ($exists) {
            return redirect()->to(site_url('settings') . '#branches')
                ->with('error', "Branch '{$name}' already exists.");
        }

        $newBranchId = $this->branchModel->insert(['name' => $name, 'address' => $address, 'is_active' => 1]);
        $this->audit->logAction('Branches', 'create', $newBranchId, null, ['name' => $name, 'address' => $address], "Added branch '{$name}'");

        return redirect()->to(site_url('settings') . '#branches')->with('success', "Branch '{$name}' added.");
    }

    /** Edit an existing branch. */
    public function editBranch(int $id)
    {
        $branch = $this->branchModel->find($id);
        if (! $branch) {
            return redirect()->to(site_url('settings') . '#branches')->with('error', 'Branch not found.');
        }

        $name    = trim($this->request->getPost('name', FILTER_SANITIZE_SPECIAL_CHARS));
        $address = trim($this->request->getPost('address', FILTER_SANITIZE_SPECIAL_CHARS));

        if (empty($name)) {
            return redirect()->to(site_url('settings') . '#branches')->with('error', 'Branch name is required.');
        }
        $duplicate = $this->branchModel->where('name', $name)->where('id !=', $id)->first();
        if ($duplicate) {
            return redirect()->to(site_url('settings') . '#branches')
                ->with('error', "Another branch named '{$name}' already exists.");
        }

        $this->branchModel->update($id, ['name' => $name, 'address' => $address]);
        $this->audit->logAction('Branches', 'update', $id,
            ['name' => $branch['name'] ?? ''],
            ['name' => $name, 'address' => $address],
            "Updated branch: '{$name}'");

        return redirect()->to(site_url('settings') . '#branches')->with('success', "Branch updated to '{$name}'.");
    }

    /** Toggle branch active/inactive. */
    public function toggleBranch(int $id)
    {
        $branch = $this->branchModel->find($id);
        if (! $branch) {
            return redirect()->to(site_url('settings') . '#branches')->with('error', 'Branch not found.');
        }
        $newBranchStatus = $branch['is_active'] ? 0 : 1;
        $this->branchModel->update($id, ['is_active' => $newBranchStatus]);
        $this->audit->logAction('Branches', 'toggle', $id, null, null,
            "Toggled branch '{$branch['name']}' to " . ($newBranchStatus ? 'active' : 'inactive'));

        return redirect()->to(site_url('settings') . '#branches')->with('success', 'Branch status updated.');
    }

    /** Delete a branch. */
    public function deleteBranch(int $id)
    {
        $branch = $this->branchModel->find($id);
        if (! $branch) {
            return redirect()->to(site_url('settings') . '#branches')->with('error', 'Branch not found.');
        }
        $this->branchModel->delete($id);
        $this->audit->logAction('Branches', 'delete', $id, $branch, null, "Deleted branch '{$branch['name']}'");

        return redirect()->to(site_url('settings') . '#branches')
            ->with('success', "Branch '{$branch['name']}' deleted.");
    }
}
