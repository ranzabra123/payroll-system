<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="<?= site_url('special-days') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fa fa-arrow-left"></i>
    </a>
    <h5 class="mb-0 fw-semibold">Add Special Day Adjustment</h5>
</div>

<?php if (session()->getFlashdata('errors')): ?>
<div class="alert alert-danger">
    <?php foreach ((array) session()->getFlashdata('errors') as $e): ?>
    <div><i class="fa fa-circle-xmark me-1"></i><?= esc($e) ?></div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card" style="max-width:780px;">
    <div class="card-body">
        <form action="<?= site_url('special-days/store') ?>" method="POST" novalidate id="special-day-form">
            <?= csrf_field() ?>
            <input type="hidden" name="scope" id="scope-input" value="<?= old('scope', 'department') ?>"/>

            <!-- ---- Scope toggle ---- -->
            <div class="mb-4">
                <label class="form-label fw-semibold d-block">Apply To</label>
                <div class="btn-group" role="group">
                    <input type="radio" class="btn-check" name="_scope_ui" id="scope-dept" value="department"
                           <?= old('scope', 'department') !== 'employee' ? 'checked' : '' ?> autocomplete="off"/>
                    <label class="btn btn-outline-primary" for="scope-dept">
                        <i class="fa fa-sitemap me-1"></i>Department(s)
                    </label>

                    <input type="radio" class="btn-check" name="_scope_ui" id="scope-emp" value="employee"
                           <?= old('scope') === 'employee' ? 'checked' : '' ?> autocomplete="off"/>
                    <label class="btn btn-outline-primary" for="scope-emp">
                        <i class="fa fa-user me-1"></i>Individual Employee
                    </label>
                </div>
                <div class="form-text mt-1">
                    Use <strong>Department(s)</strong> to bulk-apply to every active employee in selected departments.
                </div>
            </div>

            <!-- ---- Shared: Date / Type / Amount / Reason ---- -->
            <div class="row g-3 mb-3">
                <div class="col-sm-4">
                    <label class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                    <input type="date" name="date" class="form-control"
                           value="<?= old('date', date('Y-m-d')) ?>" required/>
                    <div class="form-text">The specific day this adjustment covers.</div>
                </div>

                <div class="col-sm-4">
                    <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
                    <select name="adjustment_type" id="adjustment_type" class="form-select" required>
                        <option value="fixed_amount"  <?= old('adjustment_type', 'fixed_amount') === 'fixed_amount'  ? 'selected' : '' ?>>
                            Fixed Amount
                        </option>
                        <option value="double_salary" <?= old('adjustment_type') === 'double_salary' ? 'selected' : '' ?>>
                            Double Salary (2&times; daily rate)
                        </option>
                    </select>
                </div>

                <div class="col-sm-4" id="amount-row">
                    <label class="form-label fw-semibold">Amount (&#8369;) <span class="text-danger">*</span></label>
                    <input type="number" name="amount" class="form-control" id="amount-input"
                           step="0.01" min="0.01" value="<?= old('amount') ?>" placeholder="0.00"/>
                </div>

                <div class="col-sm-4 d-none" id="double-info">
                    <label class="form-label fw-semibold">Effect</label>
                    <div class="rounded border p-2 text-primary" style="background:#eff6ff;font-size:.85rem;">
                        <i class="fa fa-circle-info me-1"></i>
                        Adds one extra daily rate — effectively 2&times; pay for this date.
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Reason / Note</label>
                    <input type="text" name="reason" class="form-control"
                           value="<?= old('reason') ?>"
                           placeholder="e.g. Company anniversary bonus, Holiday premium…"/>
                </div>
            </div>

            <hr class="my-3"/>

            <!-- ========== BY DEPARTMENT ========== -->
            <div id="panel-department">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <label class="form-label fw-semibold mb-0">
                        Select Department(s) <span class="text-danger">*</span>
                    </label>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="select-all-depts">
                            <i class="fa fa-check-double me-1"></i>All
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="deselect-all-depts">
                            <i class="fa fa-xmark me-1"></i>Clear
                        </button>
                    </div>
                </div>

                <?php if (empty($departments)): ?>
                <div class="alert alert-warning">
                    <i class="fa fa-triangle-exclamation me-2"></i>
                    No departments found. <a href="<?= site_url('settings') ?>#departments">Add departments in Settings</a>.
                </div>
                <?php else: ?>
                <div class="row g-2" id="dept-checkbox-list">
                    <?php
                    $oldDepts = (array) old('departments', []);
                    foreach ($departments as $dept):
                        $count = $deptCounts[$dept['name']] ?? 0;
                        $checked = in_array($dept['name'], $oldDepts);
                    ?>
                    <div class="col-sm-6 col-lg-4">
                        <label class="dept-card d-flex align-items-center gap-2 p-2 rounded border <?= $checked ? 'dept-card--selected' : '' ?>"
                               style="cursor:pointer;transition:all .15s;">
                            <input type="checkbox" name="departments[]"
                                   value="<?= esc($dept['name']) ?>"
                                   class="dept-check form-check-input mt-0 flex-shrink-0"
                                   <?= $checked ? 'checked' : '' ?>/>
                            <div class="flex-grow-1" style="min-width:0;">
                                <div class="fw-semibold text-truncate" style="font-size:.9rem;"><?= esc($dept['name']) ?></div>
                                <div class="text-muted" style="font-size:.75rem;">
                                    <i class="fa fa-users me-1"></i><?= $count ?> active emp<?= $count !== 1 ? 's' : '' ?>
                                </div>
                            </div>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-2 text-muted small" id="dept-selected-summary" style="min-height:1.4em;"></div>
                <?php endif; ?>
            </div>

            <!-- ========== BY EMPLOYEE ========== -->
            <div id="panel-employee" class="d-none">
                <label class="form-label fw-semibold">Employee <span class="text-danger">*</span></label>
                <select id="employee_id" name="employee_id" class="form-select">
                    <option value=""></option>
                    <?php foreach ($employees as $emp): ?>
                    <option value="<?= $emp['id'] ?>"
                        <?= old('employee_id') == $emp['id'] ? 'selected' : '' ?>>
                        <?= esc($emp['full_name']) ?> &ndash; <?= esc($emp['employee_code']) ?>
                        <?= $emp['department'] ? '(' . esc($emp['department']) . ')' : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="alert alert-warning py-2 mt-3 mb-3">
                <i class="fa fa-triangle-exclamation me-2"></i>
                Adjustments stay <strong>Pending</strong> until payroll is generated for the period containing this date.
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-floppy-disk me-1"></i>Save Adjustment
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
<style>
.dept-card { background:#f8fafc; }
.dept-card:hover { border-color:#93c5fd !important; background:#eff6ff; }
.dept-card--selected { border-color:#2563eb !important; background:#eff6ff; }
</style>
<script>
(function () {
    const scopeInput  = document.getElementById('scope-input');
    const panelDept   = document.getElementById('panel-department');
    const panelEmp    = document.getElementById('panel-employee');
    const scopeRadios = document.querySelectorAll('input[name="_scope_ui"]');
    let tomSelect     = null;

    function applyScope(scope) {
        scopeInput.value = scope;
        if (scope === 'department') {
            panelDept.classList.remove('d-none');
            panelEmp.classList.add('d-none');
            if (tomSelect) tomSelect.disable();
        } else {
            panelDept.classList.add('d-none');
            panelEmp.classList.remove('d-none');
            if (tomSelect) tomSelect.enable();
        }
    }

    scopeRadios.forEach(function (r) {
        r.addEventListener('change', function () { applyScope(this.value); });
    });

    const empEl = document.getElementById('employee_id');
    if (empEl) {
        tomSelect = new TomSelect('#employee_id', {
            placeholder: 'Type to search employee…',
            maxOptions: 200,
        });
    }

    const initScope = document.querySelector('input[name="_scope_ui"][checked]')?.value || 'department';
    applyScope(initScope);

    // ---- adjustment type toggle ----
    const typeSelect  = document.getElementById('adjustment_type');
    const amountRow   = document.getElementById('amount-row');
    const amountInput = document.getElementById('amount-input');
    const doubleInfo  = document.getElementById('double-info');

    function toggleType() {
        if (typeSelect.value === 'double_salary') {
            amountRow.classList.add('d-none');
            doubleInfo.classList.remove('d-none');
            amountInput.removeAttribute('required');
        } else {
            amountRow.classList.remove('d-none');
            doubleInfo.classList.add('d-none');
            amountInput.setAttribute('required', 'required');
        }
    }
    typeSelect.addEventListener('change', toggleType);
    toggleType();

    // ---- department checkboxes ----
    const deptChecks = document.querySelectorAll('.dept-check');
    const summary    = document.getElementById('dept-selected-summary');

    function updateDeptCards() {
        let totalEmp = 0, totalDept = 0;
        deptChecks.forEach(function (cb) {
            const card    = cb.closest('.dept-card');
            const empText = card.querySelector('.text-muted')?.textContent || '';
            const count   = parseInt((empText.match(/\d+/) || [0])[0]) || 0;
            if (cb.checked) {
                card.classList.add('dept-card--selected');
                totalEmp += count;
                totalDept++;
            } else {
                card.classList.remove('dept-card--selected');
            }
        });
        if (summary) {
            summary.innerHTML = totalDept > 0
                ? '<i class="fa fa-circle-check me-1 text-success"></i><strong>' + totalDept + '</strong> dept(s) selected &mdash; <strong>' + totalEmp + '</strong> employee(s) will be tagged.'
                : '';
        }
    }

    deptChecks.forEach(function (cb) { cb.addEventListener('change', updateDeptCards); });

    document.getElementById('select-all-depts')?.addEventListener('click', function () {
        deptChecks.forEach(function (cb) { cb.checked = true; });
        updateDeptCards();
    });
    document.getElementById('deselect-all-depts')?.addEventListener('click', function () {
        deptChecks.forEach(function (cb) { cb.checked = false; });
        updateDeptCards();
    });

    updateDeptCards();
})();
</script>
<?= $this->endSection() ?>

