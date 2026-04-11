<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="<?= site_url('deductions') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fa fa-arrow-left"></i>
    </a>
    <h5 class="mb-0 fw-semibold">Edit Deduction</h5>
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
        <form action="<?= site_url('deductions/update/' . $deduction['id']) ?>" method="POST" novalidate>
            <?= csrf_field() ?>

            <div class="row g-3 mb-3">
                <!-- Employee (read-only on edit) -->
                <div class="col-12">
                    <label class="form-label fw-semibold">Employee</label>
                    <select name="employee_id" class="form-select" disabled>
                        <?php foreach ($employees as $emp): ?>
                        <option value="<?= $emp['id'] ?>" <?= $emp['id'] == $deduction['employee_id'] ? 'selected' : '' ?>>
                            <?= esc($emp['full_name']) ?> (<?= esc($emp['employee_code']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text text-muted">Employee cannot be changed after creation.</div>
                </div>

                <!-- Type -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Deduction Type <span class="text-danger">*</span></label>
                    <select name="type" class="form-select" required>
                        <option value="Cash Advance" <?= old('type', $deduction['type']) === 'Cash Advance' ? 'selected' : '' ?>>Cash Advance (CA)</option>
                        <option value="Debt"         <?= old('type', $deduction['type']) === 'Debt' ? 'selected' : '' ?>>Debt / Loan</option>
                    </select>
                </div>

                <!-- Status -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required>
                        <option value="active"    <?= old('status', $deduction['status']) === 'active'    ? 'selected' : '' ?>>Active</option>
                        <option value="completed" <?= old('status', $deduction['status']) === 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="cancelled" <?= old('status', $deduction['status']) === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>

                <!-- Cutoff -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Deduct On <span class="text-danger">*</span></label>
                    <select name="cutoff" class="form-select" required>
                        <option value="15"   <?= old('cutoff', $deduction['cutoff']) === '15'   ? 'selected' : '' ?>>Every 15th (1st Cutoff)</option>
                        <option value="30"   <?= old('cutoff', $deduction['cutoff']) === '30'   ? 'selected' : '' ?>>Every 30th (2nd Cutoff)</option>
                        <option value="both" <?= old('cutoff', $deduction['cutoff']) === 'both' ? 'selected' : '' ?>>Every Cutoff (15 &amp; 30)</option>
                    </select>
                </div>

                <!-- Amount per cutoff -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Deduction per Cutoff (₱) <span class="text-danger">*</span></label>
                    <input type="number" name="amount_per_cutoff" class="form-control" step="0.01" min="0.01"
                           value="<?= esc(old('amount_per_cutoff', $deduction['amount_per_cutoff'])) ?>" required/>
                </div>

                <!-- Total (display only) -->
                <div class="col-md-6">
                    <label class="form-label">Original Total Amount</label>
                    <div class="form-control bg-light">₱ <?= number_format($deduction['total_amount'], 2) ?></div>
                </div>

                <!-- Remaining Balance (editable for manual adjustments) -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Remaining Balance (₱) <span class="text-danger">*</span></label>
                    <input type="number" name="remaining_balance" class="form-control" step="0.01" min="0"
                           value="<?= esc(old('remaining_balance', $deduction['remaining_balance'])) ?>" required/>
                    <div class="form-text text-muted">Set to 0 to mark as completed.</div>
                </div>

                <!-- Description -->
                <div class="col-12">
                    <label class="form-label">Description / Purpose</label>
                    <input type="text" name="description" class="form-control"
                           value="<?= esc(old('description', $deduction['description'])) ?>"
                           maxlength="200"/>
                </div>

                <!-- Start Date -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Start Date <span class="text-danger">*</span></label>
                    <input type="date" name="start_date" class="form-control"
                           value="<?= esc(old('start_date', $deduction['start_date'])) ?>" required/>
                </div>

                <!-- Notes -->
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2"
                              maxlength="500"><?= esc(old('notes', $deduction['notes'])) ?></textarea>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-floppy-disk me-1"></i>Update
                </button>
                <a href="<?= site_url('deductions/view/' . $deduction['id']) ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
