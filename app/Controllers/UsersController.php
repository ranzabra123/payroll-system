<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\UserModel;
use App\Models\AuditLogModel;

/**
 * UsersController – CRUD for system users (admin only).
 */
class UsersController extends Controller
{
    protected UserModel $model;
    protected AuditLogModel $audit;

    public function __construct()
    {
        $this->model = new UserModel();
        $this->audit = new AuditLogModel();
    }

    /** Require admin role helper. */
    private function requireAdmin()
    {
        if (session()->get('role') !== 'admin') {
            return redirect()->to(site_url('dashboard'))->with('error', 'Access denied. Admin only.');
        }
        return null;
    }

    public function index()
    {
        if ($redirect = $this->requireAdmin()) return $redirect;

        $users = $this->model->orderBy('full_name', 'ASC')->findAll();
        return view('users/index', ['title' => 'User Management', 'users' => $users]);
    }

    public function create()
    {
        if ($redirect = $this->requireAdmin()) return $redirect;
        return view('users/create', [
            'title'    => 'Add User',
            'branches' => (new \App\Models\BranchModel())->getActiveList(),
        ]);
    }

    public function store()
    {
        if ($redirect = $this->requireAdmin()) return $redirect;

        $rules = [
            'username'    => 'required|min_length[3]|max_length[100]|is_unique[users.username]',
            'password'    => 'required|min_length[6]',
            'full_name'   => 'required|min_length[3]',
            'role'      => 'required|in_list[admin,manager,staff,employee]',
            'status'      => 'required|in_list[active,inactive]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->with('errors', $this->validator->getErrors())->withInput();
        }

        $data = [
            'username'  => $this->request->getPost('username', FILTER_SANITIZE_SPECIAL_CHARS),
            'password'  => $this->request->getPost('password'),
            'full_name' => $this->request->getPost('full_name', FILTER_SANITIZE_SPECIAL_CHARS),
            'role'      => $this->request->getPost('role'),
            'branch_id' => $this->request->getPost('branch_id') ?: null,
            'status'    => $this->request->getPost('status'),
        ];

        $id = $this->model->insert($data);
        $displayData = $data; unset($displayData['password']);
        $this->audit->logAction('Users', 'create', $id, null, $displayData,
            "Created user '{$data['username']}' with role: {$data['role']}");

        return redirect()->to(site_url('users'))->with('success', 'User created successfully.');
    }

    public function edit(int $id)
    {
        if ($redirect = $this->requireAdmin()) return $redirect;

        $user = $this->model->find($id);
        if (! $user) {
            return redirect()->to(site_url('users'))->with('error', 'User not found.');
        }
        return view('users/edit', [
            'title'    => 'Edit User',
            'user'     => $user,
            'branches' => (new \App\Models\BranchModel())->getActiveList(),
        ]);
    }

    public function update(int $id)
    {
        if ($redirect = $this->requireAdmin()) return $redirect;

        $user = $this->model->find($id);
        if (! $user) {
            return redirect()->to(site_url('users'))->with('error', 'User not found.');
        }

        $rules = [
            'username'  => "required|min_length[3]|max_length[100]|is_unique[users.username,id,{$id}]",
            'full_name' => 'required|min_length[3]',
            'role'      => 'required|in_list[admin,manager,staff,employee]',
            'status'    => 'required|in_list[active,inactive]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->with('errors', $this->validator->getErrors())->withInput();
        }

        $data = [
            'username'  => $this->request->getPost('username', FILTER_SANITIZE_SPECIAL_CHARS),
            'full_name' => $this->request->getPost('full_name', FILTER_SANITIZE_SPECIAL_CHARS),
            'role'      => $this->request->getPost('role'),
            'branch_id' => $this->request->getPost('branch_id') ?: null,
            'status'    => $this->request->getPost('status'),
        ];

        // Only update password if provided
        $pw = $this->request->getPost('password');
        if (! empty($pw)) {
            if (strlen($pw) < 6) {
                return redirect()->back()->with('error', 'Password must be at least 6 characters.')->withInput();
            }
            $data['password'] = $pw;
        }

        $this->model->skipValidation(true)->update($id, $data);
        $logData = $data; unset($logData['password']);
        $this->audit->logAction('Users', 'update', $id, $user, $logData,
            "Updated user '{$user['username']}' — role: {$user['role']} → {$data['role']}, status: {$user['status']} → {$data['status']}");

        return redirect()->to(site_url('users'))->with('success', 'User updated successfully.');
    }

    public function delete(int $id)
    {
        if ($redirect = $this->requireAdmin()) return $redirect;

        // Prevent self-deletion
        if ($id === (int) session()->get('user_id')) {
            return redirect()->to(site_url('users'))->with('error', 'You cannot delete your own account.');
        }

        $user = $this->model->find($id);
        if (! $user) {
            return redirect()->to(site_url('users'))->with('error', 'User not found.');
        }

        $this->model->delete($id);
        $this->audit->logAction('Users', 'delete', $id, $user, null,
            "Deleted user '{$user['username']}' (Role: {$user['role']})");

        return redirect()->to(site_url('users'))->with('success', 'User deleted successfully.');
    }
}
