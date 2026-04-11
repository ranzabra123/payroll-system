<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="<?= site_url('benefits') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fa fa-arrow-left"></i>
    </a>
    <h5 class="mb-0 fw-semibold">Edit Benefit</h5>
</div>

<?php if (session()->getFlashdata('errors')): ?>
<div class="alert alert-danger">
    <?php foreach ((array) session()->getFlashdata('errors') as $e): ?>
    <div><i class="fa fa-circle-xmark me-1"></i><?= esc($e) ?></div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card" style="max-width:640px;">
    <div class="card-body">
        <form action="<?= site_url('benefits/update/' . $benefit['id']) ?>" method="POST" novalidate>
            <?= csrf_field() ?>

            <div class="row g-3 mb-3">
                <div class="col-12">
                    <label class="form-label fw-semibold">Benefit Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control"
                           value="<?= esc(old('name', $benefit['name'])) ?>"
                           required maxlength="100"/>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea name="description" class="form-control" rows="2"
                              maxlength="500"><?= esc(old('description', $benefit['description'])) ?></textarea>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Employee Share (&#8369;) <span class="text-danger">*</span></label>
                    <input type="number" id="emp_share" name="employee_share" class="form-control"
                           step="0.01" min="0"
                           value="<?= esc(old('employee_share', $benefit['employee_share'])) ?>" required/>
                    <div class="form-text">Amount deducted from employee's pay.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Company Contribution (&#8369;) <span class="text-danger">*</span></label>
                    <input type="number" id="emr_share" name="employer_share" class="form-control"
                           step="0.01" min="0"
                           value="<?= esc(old('employer_share', $benefit['employer_share'])) ?>" required/>
                    <div class="form-text">Employer counter-part (not deducted from employee).</div>
                </div>

                <div class="col-12">
                    <div class="alert alert-info py-2 mb-0">
                        <i class="fa fa-circle-info me-1"></i>
                        Monthly Total: <strong id="monthly_total">&#8369; 0.00</strong>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                    <select name="is_active" class="form-select" required>
                        <option value="1" <?= old('is_active', $benefit['is_active']) == 1 ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= old('is_active', $benefit['is_active']) == 0 ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-floppy-disk me-1"></i>Update
                </button>
                <a href="<?= site_url('benefits') ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const emp   = document.getElementById('emp_share');
    const emr   = document.getElementById('emr_share');
    const total = document.getElementById('monthly_total');
    function calc() {
        const t = (parseFloat(emp.value) || 0) + (parseFloat(emr.value) || 0);
        total.textContent = '\u20B1 ' + t.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
    }
    emp.addEventListener('input', calc);
    emr.addEventListener('input', calc);
    calc();
})();
</script>

<?= $this->endSection() ?>
