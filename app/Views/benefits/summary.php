<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="<?= site_url('benefits') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fa fa-arrow-left"></i>
    </a>
    <h5 class="mb-0 fw-semibold">Benefits Summary Report</h5>
    <button class="btn btn-sm btn-outline-secondary ms-auto d-print-none" onclick="window.print()">
        <i class="fa fa-print me-1"></i>Print
    </button>
</div>

<!-- Filters -->
<div class="card mb-3 p-2 d-print-none">
    <form method="get" class="d-flex flex-wrap gap-2 align-items-center">
        <!-- Month -->
        <div>
            <label class="form-label mb-0 small fw-semibold me-1">Month</label>
            <input type="month" name="month" class="form-control form-control-sm"
                   value="<?= esc($filters['month']) ?>" style="max-width:160px"/>
        </div>
        <!-- Benefit Type -->
        <select name="benefit_type" class="form-select form-select-sm" style="max-width:160px">
            <option value="">All Benefit Types</option>
            <?php foreach ($benefitTypes as $bt): ?>
            <option value="<?= esc($bt) ?>" <?= ($filters['benefit_type'] ?? '') === $bt ? 'selected' : '' ?>>
                <?= esc($bt) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <!-- Cutoff -->
        <select name="cutoff" class="form-select form-select-sm" style="max-width:140px">
            <option value="">All Cutoffs</option>
            <option value="15" <?= ($filters['cutoff'] ?? '') === '15' ? 'selected' : '' ?>>Every 15th</option>
            <option value="30" <?= ($filters['cutoff'] ?? '') === '30' ? 'selected' : '' ?>>Every 30th</option>
        </select>
        <!-- Search -->
        <input type="text" name="q" class="form-control form-control-sm flex-grow-1"
               placeholder="Search employee…" value="<?= esc($filters['search'] ?? '') ?>"/>
        <button class="btn btn-sm btn-primary">Generate</button>
        <a href="<?= site_url('benefits/summary') ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
    </form>
</div>

<?php
$monthLabel  = date('F Y', strtotime(($filters['month'] ?: date('Y-m')) . '-01'));
$cutoffLabel = match($filters['cutoff'] ?? '') {
    '15'    => '1st Cutoff (15th)',
    '30'    => '2nd Cutoff (30th)',
    default => 'All Cutoffs',
};
$typeLabel = $filters['benefit_type'] !== '' ? $filters['benefit_type'] : 'All Benefit Types';
?>

<!-- Screen report identity bar -->
<div class="card mb-3 border-primary">
    <div class="card-body py-2 px-3 d-flex flex-wrap align-items-center gap-3">
        <div>
            <span class="text-muted small fw-semibold text-uppercase me-1">Report</span>
            <span class="fw-bold text-primary">Benefits Summary Report</span>
        </div>
        <div class="vr d-none d-md-block"></div>
        <div>
            <span class="text-muted small me-1">Month:</span>
            <span class="badge bg-secondary"><?= esc($monthLabel) ?></span>
        </div>
        <div>
            <span class="text-muted small me-1">Cutoff:</span>
            <span class="badge bg-secondary"><?= esc($cutoffLabel) ?></span>
        </div>
        <div>
            <span class="text-muted small me-1">Benefit Type:</span>
            <span class="badge <?= $filters['benefit_type'] !== '' ? 'bg-primary' : 'bg-light text-dark border' ?>">
                <?= esc($typeLabel) ?>
            </span>
        </div>
        <?php if (($filters['search'] ?? '') !== ''): ?>
        <div>
            <span class="text-muted small me-1">Search:</span>
            <span class="badge bg-warning text-dark"><?= esc($filters['search']) ?></span>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Print-only header -->
<div class="d-none d-print-block mb-3 text-center">
    <h4 class="fw-bold">Benefits Summary Report</h4>
    <div class="text-muted"><?= esc($monthLabel) ?> &mdash; <?= esc($cutoffLabel) ?> &mdash; <?= esc($typeLabel) ?></div>
</div>

<?php if (empty($grouped)): ?>
<div class="alert alert-info">No active benefits found for this period.</div>
<?php else: ?>

<!-- Per-Type Tables -->
<?php foreach ($grouped as $type => $group): ?>
<div class="card mb-4">
    <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
        <span><span class="badge bg-primary me-2"><?= esc($type) ?></span><?= count($group['rows']) ?> employee(s)</span>
        <span class="text-muted small">
            Total: Employee ₱ <?= number_format($group['emp_total'], 2) ?> +
                   Employer ₱ <?= number_format($group['emr_total'], 2) ?> =
                   <strong>₱ <?= number_format($group['emp_total'] + $group['emr_total'], 2) ?></strong>
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Employee Code</th>
                        <th>Employee Name</th>
                        <th>Department</th>
                        <th class="text-center">Cutoff</th>
                        <th class="text-end">Employee Share</th>
                        <th class="text-end">Employer Share</th>
                        <th class="text-end">Total Monthly</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($group['rows'] as $i => $r): ?>
                <tr>
                    <td class="text-muted"><?= $i + 1 ?></td>
                    <td class="font-monospace small"><?= esc($r['employee_code']) ?></td>
                    <td><?= esc($r['full_name']) ?></td>
                    <td><?= esc($r['department'] ?? '—') ?></td>
                    <td class="text-center">
                        <span class="badge bg-light text-dark border">
                            <?= $r['cutoff'] === '15' ? '15th' : '30th' ?>
                        </span>
                    </td>
                    <td class="text-end">₱ <?= number_format($r['employee_share'], 2) ?></td>
                    <td class="text-end">₱ <?= number_format($r['employer_share'], 2) ?></td>
                    <td class="text-end fw-semibold">₱ <?= number_format($r['employee_share'] + $r['employer_share'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light fw-semibold">
                    <tr>
                        <td colspan="5" class="text-end">Subtotal</td>
                        <td class="text-end">₱ <?= number_format($group['emp_total'], 2) ?></td>
                        <td class="text-end">₱ <?= number_format($group['emr_total'], 2) ?></td>
                        <td class="text-end">₱ <?= number_format($group['emp_total'] + $group['emr_total'], 2) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Grand Total -->
<div class="card border-dark">
    <div class="card-body">
        <div class="row text-center g-3">
            <div class="col-md-4">
                <div class="text-muted small">Total Employee Contributions</div>
                <div class="fw-bold fs-4 text-primary">₱ <?= number_format($grandEmpShare, 2) ?></div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small">Total Employer Contributions</div>
                <div class="fw-bold fs-4 text-success">₱ <?= number_format($grandEmrShare, 2) ?></div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small">Grand Total</div>
                <div class="fw-bold fs-4">₱ <?= number_format($grandTotal, 2) ?></div>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<style>
@media print {
    .card { border: 1px solid #dee2e6 !important; page-break-inside: avoid; }
    .btn, .d-print-none { display: none !important; }
}
</style>

<?= $this->endSection() ?>
