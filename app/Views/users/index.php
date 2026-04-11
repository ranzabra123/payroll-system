<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-semibold">User Management</h5>
    <a href="<?= site_url('users/create') ?>" class="btn btn-primary btn-sm">
        <i class="fa fa-user-plus me-1"></i>Add User
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($users)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No users found.</td></tr>
                <?php else: ?>
                    <?php foreach ($users as $i => $u): ?>
                    <tr>
                        <td class="text-muted small"><?= $i + 1 ?></td>
                        <td class="fw-semibold"><?= esc($u['full_name']) ?></td>
                        <td class="font-monospace"><?= esc($u['username']) ?></td>
                        <td>
                            <span class="badge <?= match($u['role']) { 'admin' => 'bg-danger', 'manager' => 'bg-warning text-dark', default => 'bg-info text-dark' } ?>">
                                <?= ucfirst($u['role']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?= $u['status'] === 'active' ? 'badge-active' : 'badge-inactive' ?>">
                                <?= ucfirst($u['status']) ?>
                            </span>
                        </td>
                        <td class="text-muted small">
                            <?= $u['last_login'] ? date('M j, Y g:i a', strtotime($u['last_login'])) : '—' ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="<?= site_url('users/edit/' . $u['id']) ?>"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="fa fa-pen-to-square"></i>
                                </a>
                                <?php if ($u['id'] !== session()->get('user_id')): ?>
                                <a href="<?= site_url('users/delete/' . $u['id']) ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   data-confirm="Delete this user?">
                                    <i class="fa fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
