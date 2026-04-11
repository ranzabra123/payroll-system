<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="<?= site_url('employees') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fa fa-arrow-left"></i>
    </a>
    <h5 class="mb-0 fw-semibold">Edit Employee – <?= esc($employee['full_name']) ?></h5>
</div>

<div class="card" style="max-width:700px;">
    <div class="card-body">
        <form action="<?= site_url('employees/update/' . $employee['id']) ?>" method="POST" novalidate>
            <?= csrf_field() ?>

            <h6 class="text-muted mb-3 border-bottom pb-2">Personal Information</h6>
            <div class="row g-3 mb-3">
                <div class="col-md-8">
                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="full_name" class="form-control"
                           value="<?= esc(old('full_name', $employee['full_name'])) ?>" required/>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active"   <?= old('status', $employee['status']) === 'active'   ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= old('status', $employee['status']) === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Position <span class="text-danger">*</span></label>
                    <input type="text" name="position" class="form-control"
                           value="<?= esc(old('position', $employee['position'])) ?>" required/>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Department</label>
                    <select name="department" class="form-select">
                        <option value="">— Select Department —</option>
                        <?php foreach ($departments as $dept): ?>
                        <option value="<?= esc($dept['name']) ?>"
                            <?= old('department', $employee['department']) === $dept['name'] ? 'selected' : '' ?>>
                            <?= esc($dept['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Branch</label>
                    <select name="branch_id" class="form-select">
                        <option value="">— Select Branch —</option>
                        <?php foreach ($branches as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= old('branch_id', $employee['branch_id'] ?? '') == $b['id'] ? 'selected' : '' ?>>
                            <?= esc($b['name']) ?><?= $b['address'] ? ' – ' . esc($b['address']) : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Date Hired <span class="text-danger">*</span></label>
                    <input type="date" name="date_hired" class="form-control"
                           value="<?= esc(old('date_hired', $employee['date_hired'])) ?>" required/>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-select">
                        <option value="">— Select Gender —</option>
                        <option value="Male"   <?= old('gender', $employee['gender'] ?? '') === 'Male'   ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= old('gender', $employee['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                        <option value="Other"  <?= old('gender', $employee['gender'] ?? '') === 'Other'  ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
            </div>

            <h6 class="text-muted mb-3 border-bottom pb-2">Compensation</h6>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Monthly Salary (₱) <span class="text-danger">*</span></label>
                    <input type="number" id="monthly_salary" name="monthly_salary"
                           class="form-control" step="0.01" min="0"
                           value="<?= esc(old('monthly_salary', $employee['monthly_salary'])) ?>"
                           data-currency required/>
                    <div class="form-text" id="daily_rate_preview">
                        Daily Rate: ₱ <?= number_format($employee['daily_rate'], 2) ?>
                    </div>
                </div>
            </div>

            <h6 class="text-muted mb-3 border-bottom pb-2">Government Numbers</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">SSS Number</label>
                    <input type="text" name="sss_number" class="form-control font-monospace"
                           value="<?= esc(old('sss_number', $employee['sss_number'])) ?>"/>
                </div>
                <div class="col-md-6">
                    <label class="form-label">PhilHealth Number</label>
                    <input type="text" name="philhealth_number" class="form-control font-monospace"
                           value="<?= esc(old('philhealth_number', $employee['philhealth_number'])) ?>"/>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Pag-IBIG Number</label>
                    <input type="text" name="pagibig_number" class="form-control font-monospace"
                           value="<?= esc(old('pagibig_number', $employee['pagibig_number'])) ?>"/>
                </div>
                <div class="col-md-6">
                    <label class="form-label">TIN</label>
                    <input type="text" name="tin_number" class="form-control font-monospace"
                           value="<?= esc(old('tin_number', $employee['tin_number'])) ?>"/>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save me-1"></i>Update Employee
                </button>
                <a href="<?= site_url('employees') ?>" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
