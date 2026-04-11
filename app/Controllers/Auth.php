<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\UserModel;
use App\Models\AuditLogModel;

/**
 * Auth – handles login, logout.
 */
class Auth extends Controller
{
    public function login()
    {
        if (session()->get('logged_in')) {
            return redirect()->to(site_url('dashboard'));
        }
        return view('auth/login', ['title' => 'Login']);
    }

    public function attemptLogin()
    {
        $rules = [
            'username' => 'required|min_length[3]',
            'password' => 'required|min_length[4]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->with('errors', $this->validator->getErrors())->withInput();
        }

        $username = $this->request->getPost('username', FILTER_SANITIZE_SPECIAL_CHARS);
        $password = $this->request->getPost('password');

        $userModel = new UserModel();
        $user = $userModel->findActiveByUsername($username);

        if (! $user || ! password_verify($password, $user['password'])) {
            return redirect()->back()->with('error', 'Invalid username or password.')->withInput();
        }

        // Set session
        session()->set([
            'user_id'   => $user['id'],
            'username'  => $user['username'],
            'full_name' => $user['full_name'],
            'role'      => $user['role'],
            'branch_id' => $user['branch_id'] ?? null,
            'logged_in' => true,
        ]);

        $userModel->updateLastLogin($user['id']);

        // Audit
        $ip = $this->request->getIPAddress();
        (new AuditLogModel())->logAction('Auth', 'login', $user['id'], null, null,
            "User '{$user['username']}' logged in from {$ip}");

        return redirect()->to(site_url('dashboard'));
    }

    public function logout()
    {
        $logUsername = session()->get('username') ?? 'unknown';
        (new AuditLogModel())->logAction('Auth', 'logout', session()->get('user_id'), null, null,
            "User '{$logUsername}' logged out");
        session()->destroy();
        return redirect()->to(site_url('login'))->with('success', 'You have been logged out.');
    }
}
