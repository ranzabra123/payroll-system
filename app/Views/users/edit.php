<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="<?= site_url('users') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fa fa-arrow-left"></i>
    </a>
    <h5 class="mb-0 fw-semibold">Edit User</h5>
</div>

<div class="card" style="max-width:540px;">
    <div class="card-body">
        <form action="<?= site_url('users/update/' . $user['id']) ?>" method="POST" novalidate>
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                <input type="text" name="full_name" class="form-control"
                       value="<?= esc(old('full_name', $user['full_name'])) ?>" required/>
            </div>

            <div class="mb-3">
                <label class="form-label">Username <span class="text-danger">*</span></label>
                <input type="text" name="username" class="form-control"
                       value="<?= esc(old('username', $user['username'])) ?>" required/>
            </div>

            <div class="mb-3">
                <label class="form-label">New Password</label>
                <input type="password" name="password" class="form-control" autocomplete="new-password"/>
                <div class="form-text">Leave blank to keep current password.</div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col">
                    <label class="form-label">Role <span class="text-danger">*</span></label>
                    <select name="role" class="form-select" required>
                        <option value="admin"    <?= old('role', $user['role']) === 'admin'    ? 'selected' : '' ?>>Admin</option>
                        <option value="manager"  <?= old('role', $user['role']) === 'manager'  ? 'selected' : '' ?>>Manager</option>
                        <option value="staff"    <?= old('role', $user['role']) === 'staff'    ? 'selected' : '' ?>>Staff</option>
                        <option value="employee" <?= old('role', $user['role']) === 'employee' ? 'selected' : '' ?>>Employee</option>
                    </select>
                </div>
                <div class="col">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required>
                        <option value="active"   <?= old('status', $user['status']) === 'active'   ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= old('status', $user['status']) === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Branch Access</label>
                <select name="branch_id" class="form-select">
                    <option value="">All Branches</option>
                    <?php foreach ($branches as $b): ?>
                    <option value="<?= $b['id'] ?>" <?= old('branch_id', $user['branch_id']) == $b['id'] ? 'selected' : '' ?>>
                        <?= esc($b['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Restrict this user to employees/attendance of a specific branch. Leave blank for no restriction.</div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save me-1"></i>Update User
                </button>
                <a href="<?= site_url('users') ?>" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
