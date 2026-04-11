<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="<?= site_url('benefits') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fa fa-arrow-left"></i>
    </a>
    <h5 class="mb-0 fw-semibold">
        Manage Assignments &mdash; <span class="text-primary"><?= esc($benefit['name']) ?></span>
    </h5>
</div>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success py-2"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('errors')): ?>
<div class="alert alert-danger">
    <?php foreach ((array) session()->getFlashdata('errors') as $e): ?>
    <div><i class="fa fa-circle-xmark me-1"></i><?= esc($e) ?></div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Benefit summary card -->
<div class="card mb-3 border-primary border-opacity-50">
    <div class="card-body py-2 d-flex flex-wrap gap-4">
        <div><span class="text-muted small">Employee Share</span><br><strong>&#8369; <?= number_format($benefit['employee_share'], 2) ?></strong></div>
        <div><span class="text-muted small">Company Contribution</span><br><strong>&#8369; <?= number_format($benefit['employer_share'], 2) ?></strong></div>
        <div><span class="text-muted small">Monthly Total</span><br><strong class="text-primary">&#8369; <?= number_format($benefit['employee_share'] + $benefit['employer_share'], 2) ?></strong></div>
        <div><span class="text-muted small">Status</span><br>
            <span class="badge <?= $benefit['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                <?= $benefit['is_active'] ? 'Active' : 'Inactive' ?>
            </span>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Add Assignment Form -->
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header fw-semibold py-2">
                <i class="fa fa-plus me-1"></i>Add Assignment
            </div>
            <div class="card-body">
                <form action="<?= site_url('benefits/assign-store/' . $benefit['id']) ?>" method="POST" novalidate id="assign-form">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Assign To <span class="text-danger">*</span></label>
                        <div class="d-flex gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="scope" id="scope_dept"
                                       value="department" <?= old('scope', 'department') === 'department' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="scope_dept">Department</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="scope" id="scope_emp"
                                       value="employee" <?= old('scope') === 'employee' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="scope_emp">Individual Employee</label>
                            </div>
                        </div>
                    </div>

                    <!-- Department selector -->
                    <div class="mb-3" id="dept-group">
                        <label class="form-label fw-semibold">Department <span class="text-danger">*</span></label>
                        <select name="department" class="form-select">
                            <option value="">— Select Department —</option>
                            <?php foreach ($departments as $dept): ?>
                            <option value="<?= esc($dept['name']) ?>"
                                <?= old('department') === $dept['name'] ? 'selected' : '' ?>>
                                <?= esc($dept['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Employee selector -->
                    <div class="mb-3 d-none" id="emp-group">
                        <label class="form-label fw-semibold">Employee <span class="text-danger">*</span></label>
                        <select name="employee_id" id="employee_sel" class="form-select">
                            <option value=""></option>
                            <?php foreach ($employees as $emp): ?>
                            <option value="<?= $emp['id'] ?>"
                                <?= old('employee_id') == $emp['id'] ? 'selected' : '' ?>>
                                <?= esc($emp['full_name']) ?> (<?= esc($emp['employee_code']) ?>)
                                &mdash; <?= esc($emp['department']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Deduct On <span class="text-danger">*</span></label>
                        <select name="cutoff" class="form-select" required>
                            <option value="both" <?= old('cutoff', 'both') === 'both' ? 'selected' : '' ?>>Every Cutoff (split monthly)</option>
                            <option value="30"   <?= old('cutoff') === '30'   ? 'selected' : '' ?>>End of Month (2nd cutoff only)</option>
                            <option value="15"   <?= old('cutoff') === '15'   ? 'selected' : '' ?>>1st Cutoff only (15th)</option>
                        </select>
                        <div class="form-text">
                            "Every Cutoff" deducts half the amount each payroll run.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Effective Date <span class="text-danger">*</span></label>
                        <input type="date" name="effective_date" class="form-control"
                               value="<?= esc(old('effective_date', date('Y-m-01'))) ?>" required/>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"
                                  maxlength="500"><?= esc(old('notes')) ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa fa-floppy-disk me-1"></i>Add Assignment
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Current Assignments -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header fw-semibold py-2">
                <i class="fa fa-list me-1"></i>Current Assignments
                <span class="badge bg-primary ms-1"><?= count($assignments) ?></span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($assignments)): ?>
                <div class="text-center text-muted py-4">No assignments yet.</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle small">
                        <thead class="table-light">
                            <tr>
                                <th>Assigned To</th>
                                <th class="text-center">Cutoff</th>
                                <th>Effective</th>
                                <th class="text-center">Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($assignments as $a): ?>
                        <tr>
                            <td>
                                <?php if ($a['scope'] === 'department'): ?>
                                <span class="badge bg-info text-dark me-1"><i class="fa fa-building"></i></span>
                                <?= esc($a['department']) ?>
                                <?php else: ?>
                                <span class="badge bg-secondary me-1"><i class="fa fa-user"></i></span>
                                <div><?= esc($a['employee_name']) ?></div>
                                <div class="text-muted" style="font-size:.72rem;"><?= esc($a['employee_code']) ?> &mdash; <?= esc($a['employee_department']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php
                                $cutoffLabels = ['both' => 'Every', '15' => '15th', '30' => 'EOM'];
                                $cutoffClass  = ['both' => 'bg-primary', '15' => 'bg-warning text-dark', '30' => 'bg-success'];
                                ?>
                                <span class="badge <?= $cutoffClass[$a['cutoff']] ?? 'bg-secondary' ?>">
                                    <?= $cutoffLabels[$a['cutoff']] ?? $a['cutoff'] ?>
                                </span>
                            </td>
                            <td><?= date('M j, Y', strtotime($a['effective_date'])) ?></td>
                            <td class="text-center">
                                <span class="badge <?= $a['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>">
                                    <?= ucfirst($a['status']) ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?= site_url('benefits/assignment-delete/' . $a['id']) ?>"
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Remove this assignment?')"
                                   title="Delete">
                                    <i class="fa fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?= $this->section('scripts') ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css"/>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
(function () {
    const deptGroup = document.getElementById('dept-group');
    const empGroup  = document.getElementById('emp-group');
    const radios    = document.querySelectorAll('input[name="scope"]');

    function toggle() {
        const val = document.querySelector('input[name="scope"]:checked').value;
        deptGroup.classList.toggle('d-none', val !== 'department');
        empGroup.classList.toggle('d-none',  val !== 'employee');
    }
    radios.forEach(r => r.addEventListener('change', toggle));
    toggle();

    new TomSelect('#employee_sel', {
        placeholder: 'Type to search employee…',
        maxOptions: 200,
    });
})();
</script>
<?= $this->endSection() ?>

<?= $this->endSection() ?>
