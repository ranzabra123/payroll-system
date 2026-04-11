<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="<?= site_url('deductions') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fa fa-arrow-left"></i>
    </a>
    <h5 class="mb-0 fw-semibold">Add Deduction (Utang / CA)</h5>
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
        <form action="<?= site_url('deductions/store') ?>" method="POST" novalidate>
            <?= csrf_field() ?>

            <div class="row g-3 mb-3">
                <!-- Employee -->
                <div class="col-12">
                    <label class="form-label fw-semibold">Employee <span class="text-danger">*</span></label>
                    <select id="employee_id" name="employee_id" class="form-select" required>
                        <option value=""></option>
                        <?php foreach ($employees as $emp): ?>
                        <option value="<?= $emp['id'] ?>"
                            <?= old('employee_id', $empId) == $emp['id'] ? 'selected' : '' ?>>
                            <?= esc($emp['full_name']) ?> (<?= esc($emp['employee_code']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Type -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Deduction Type <span class="text-danger">*</span></label>
                    <select name="type" class="form-select" required>
                        <option value="Cash Advance" <?= old('type', 'Cash Advance') === 'Cash Advance' ? 'selected' : '' ?>>Cash Advance (CA)</option>
                        <option value="Debt"         <?= old('type') === 'Debt' ? 'selected' : '' ?>>Debt / Loan</option>
                    </select>
                </div>

                <!-- Cutoff -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Deduct On <span class="text-danger">*</span></label>
                    <select name="cutoff" class="form-select" required>
                        <option value="15"   <?= old('cutoff', '15') === '15'   ? 'selected' : '' ?>>Every 15th (1st Cutoff)</option>
                        <option value="30"   <?= old('cutoff') === '30'   ? 'selected' : '' ?>>Every 30th (2nd Cutoff)</option>
                        <option value="both" <?= old('cutoff') === 'both' ? 'selected' : '' ?>>Every Cutoff (15 &amp; 30)</option>
                    </select>
                </div>

                <!-- Description -->
                <div class="col-12">
                    <label class="form-label">Description / Purpose</label>
                    <input type="text" name="description" class="form-control"
                           value="<?= esc(old('description')) ?>" maxlength="200"
                           placeholder="e.g. Purchase advance, Emergency loan…"/>
                </div>

                <!-- Total Amount -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Total Amount (₱) <span class="text-danger">*</span></label>
                    <input type="number" id="total_amount" name="total_amount" class="form-control"
                           step="0.01" min="0.01" value="<?= esc(old('total_amount')) ?>" required/>
                </div>

                <!-- Per Cutoff Amount -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Deduction per Cutoff (₱) <span class="text-danger">*</span></label>
                    <input type="number" id="amount_per_cutoff" name="amount_per_cutoff" class="form-control"
                           step="0.01" min="0.01" value="<?= esc(old('amount_per_cutoff')) ?>" required/>
                    <div class="form-text" id="terms_preview">—</div>
                </div>

                <!-- Start Date -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Start Date <span class="text-danger">*</span></label>
                    <input type="date" name="start_date" class="form-control"
                           value="<?= esc(old('start_date', date('Y-m-d'))) ?>" required/>
                </div>

                <!-- Notes -->
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2"
                              maxlength="500"><?= esc(old('notes')) ?></textarea>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-floppy-disk me-1"></i>Save Deduction
                </button>
                <a href="<?= site_url('deductions') ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const total  = document.getElementById('total_amount');
    const perCut = document.getElementById('amount_per_cutoff');
    const preview = document.getElementById('terms_preview');

    function calc() {
        const t = parseFloat(total.value) || 0;
        const p = parseFloat(perCut.value) || 0;
        if (t > 0 && p > 0) {
            const terms = Math.ceil(t / p);
            preview.textContent = `≈ ${terms} cutoff term${terms !== 1 ? 's' : ''} to fully pay`;
        } else {
            preview.textContent = '—';
        }
    }

    total.addEventListener('input', calc);
    perCut.addEventListener('input', calc);
    calc();
})();
</script>

<?= $this->section('scripts') ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css"/>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
new TomSelect('#employee_id', {
    placeholder: 'Type to search employee…',
    maxOptions: 200,
});
</script>
<?= $this->endSection() ?>

<?= $this->endSection() ?>
