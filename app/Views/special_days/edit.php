<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="<?= site_url('special-days') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fa fa-arrow-left"></i>
    </a>
    <h5 class="mb-0 fw-semibold">Edit Special Day Adjustment</h5>
</div>

<?php if (session()->getFlashdata('errors')): ?>
<div class="alert alert-danger">
    <?php foreach ((array) session()->getFlashdata('errors') as $e): ?>
    <div><i class="fa fa-circle-xmark me-1"></i><?= esc($e) ?></div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card" style="max-width:680px;">
    <div class="card-body">
        <form action="<?= site_url('special-days/update/' . $record['id']) ?>" method="POST" novalidate>
            <?= csrf_field() ?>

            <div class="row g-3 mb-3">
                <!-- Employee -->
                <div class="col-12">
                    <label class="form-label fw-semibold">Employee <span class="text-danger">*</span></label>
                    <select id="employee_id" name="employee_id" class="form-select" required>
                        <option value=""></option>
                        <?php foreach ($employees as $emp): ?>
                        <option value="<?= $emp['id'] ?>"
                            <?= (old('employee_id', $record['employee_id']) == $emp['id']) ? 'selected' : '' ?>>
                            <?= esc($emp['full_name']) ?> – <?= esc($emp['employee_code']) ?>
                            <?= $emp['department'] ? '(' . esc($emp['department']) . ')' : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Date -->
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                    <input type="date" name="date" class="form-control"
                           value="<?= old('date', $record['date']) ?>" required/>
                </div>

                <!-- Adjustment Type -->
                <div class="col-sm-6">
                    <label class="form-label fw-semibold">Adjustment Type <span class="text-danger">*</span></label>
                    <select name="adjustment_type" id="adjustment_type" class="form-select" required>
                        <option value="fixed_amount"
                            <?= old('adjustment_type', $record['adjustment_type']) === 'fixed_amount' ? 'selected' : '' ?>>
                            Fixed Amount – add a specific amount
                        </option>
                        <option value="double_salary"
                            <?= old('adjustment_type', $record['adjustment_type']) === 'double_salary' ? 'selected' : '' ?>>
                            Double Salary – pay 2× daily rate for this day
                        </option>
                    </select>
                </div>

                <!-- Amount -->
                <div class="col-sm-6" id="amount-row">
                    <label class="form-label fw-semibold">Amount (₱) <span class="text-danger">*</span></label>
                    <input type="number" name="amount" class="form-control" step="0.01" min="0.01"
                           value="<?= old('amount', $record['amount']) ?>" placeholder="0.00"/>
                    <div class="form-text">Additional amount to add to payroll for this day.</div>
                </div>

                <!-- Double salary info -->
                <div class="col-sm-6 d-none" id="double-info">
                    <label class="form-label fw-semibold">Effect</label>
                    <div class="alert alert-info mb-0 py-2">
                        <i class="fa fa-circle-info me-2"></i>
                        The employee's daily rate will be added as a bonus for this day —
                        effectively doubling pay for that date.
                    </div>
                </div>

                <!-- Reason -->
                <div class="col-12">
                    <label class="form-label fw-semibold">Reason / Note</label>
                    <input type="text" name="reason" class="form-control"
                           value="<?= old('reason', $record['reason']) ?>"
                           placeholder="e.g. Holiday pay, special event bonus…"/>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-floppy-disk me-1"></i>Update Adjustment
                </button>
                <a href="<?= site_url('special-days') ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css"/>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
new TomSelect('#employee_id', {
    placeholder: 'Type to search employee…',
    maxOptions: 200,
});

const typeSelect = document.getElementById('adjustment_type');
const amountRow  = document.getElementById('amount-row');
const doubleInfo = document.getElementById('double-info');

function toggleTypeFields() {
    if (typeSelect.value === 'double_salary') {
        amountRow.classList.add('d-none');
        doubleInfo.classList.remove('d-none');
        amountRow.querySelector('input').removeAttribute('required');
    } else {
        amountRow.classList.remove('d-none');
        doubleInfo.classList.add('d-none');
        amountRow.querySelector('input').setAttribute('required', 'required');
    }
}

typeSelect.addEventListener('change', toggleTypeFields);
toggleTypeFields();
</script>
<?= $this->endSection() ?>
