<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center gap-2 mb-3">
   
    <h5 class="mb-0 fw-semibold">Benefits Summary Report</h5>
    <button class="btn btn-sm btn-outline-secondary ms-auto d-print-none" onclick="window.print()">
        <i class="fa fa-print me-1"></i>Print
    </button>
</div>

<!-- Filters -->
<div class="card mb-3 p-2 d-print-none">
    <form method="get" class="row g-2 align-items-end">
        <!-- Year -->
        <div class="col-sm-auto">
            <label class="form-label mb-1 small fw-semibold">Year</label>
            <select name="year" class="form-select form-select-sm" style="min-width:100px">
                <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                <option value="<?= $y ?>" <?= ($filters['year'] == $y) ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <!-- Month -->
        <div class="col-sm-auto">
            <label class="form-label mb-1 small fw-semibold">Month</label>
            <select name="month" class="form-select form-select-sm" style="min-width:120px">
                <option value="">All Months</option>
                <?php $monthNames = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
                for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= $m ?>" <?= (string)($filters['month']) === (string)$m ? 'selected' : '' ?>>
                    <?= $monthNames[$m] ?>
                </option>
                <?php endfor; ?>
            </select>
        </div>
        <!-- Benefit Type -->
        <div class="col-sm-auto">
            <label class="form-label mb-1 small fw-semibold">Benefit Type</label>
            <select name="benefit_type" class="form-select form-select-sm" style="min-width:140px">
                <option value="">All Types</option>
                <option value="sss"        <?= $filters['benefit_type'] === 'sss'        ? 'selected' : '' ?>>SSS</option>
                <option value="philhealth" <?= $filters['benefit_type'] === 'philhealth' ? 'selected' : '' ?>>PhilHealth</option>
                <option value="pagibig"    <?= $filters['benefit_type'] === 'pagibig'    ? 'selected' : '' ?>>Pag-IBIG</option>
            </select>
        </div>
        <!-- Branch -->
        <div class="col-sm-auto">
            <label class="form-label mb-1 small fw-semibold">Branch</label>
            <select name="branch_id" class="form-select form-select-sm" style="min-width:150px">
                <option value="">All Branches</option>
                <?php foreach ($branches as $b): ?>
                <option value="<?= $b['id'] ?>" <?= $filters['branch_id'] == $b['id'] ? 'selected' : '' ?>>
                    <?= esc($b['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <!-- Employee search -->
        <div class="col">
            <label class="form-label mb-1 small fw-semibold">Employee Name</label>
            <input type="text" name="q" class="form-control form-control-sm"
                   placeholder="Search employee…" value="<?= esc($filters['search']) ?>"/>
        </div>
        <div class="col-sm-auto">
            <button class="btn btn-sm btn-primary">Generate</button>
            <a href="<?= site_url('benefits/summary') ?>" class="btn btn-sm btn-outline-secondary ms-1">Reset</a>
        </div>
    </form>
</div>

<?php
$yearLabel  = esc($filters['year']);
$monthNames = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
$monthLabel = $filters['month'] ? $monthNames[(int)$filters['month']] . ' ' . $yearLabel : $yearLabel;
$typeLabels = ['sss'=>'SSS','philhealth'=>'PhilHealth','pagibig'=>'Pag-IBIG'];
$typeLabel  = $filters['benefit_type'] ? ' – ' . $typeLabels[$filters['benefit_type']] : '';
?>

<!-- Print header -->
<div class="d-none d-print-block text-center mb-3">
    <h4 class="fw-bold">Benefits Summary Report</h4>
    <div class="text-muted"><?= esc($monthLabel) ?><?= esc($typeLabel) ?><?= $filters['search'] ? ' – ' . esc($filters['search']) : '' ?></div>
</div>

<?php if (empty($rows) || (!$hasSss && !$hasPh && !$hasPi)): ?>
<div class="alert alert-info">No benefit deductions found for the selected period.</div>
<?php else: ?>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0 align-middle" id="benefitSummaryTable">
                <thead class="table-dark">
                    <tr>
                        <th rowspan="2" class="align-middle">Month</th>
                        <th rowspan="2" class="align-middle">Branch</th>
                        <th rowspan="2" class="align-middle">Employee Name</th>
                        <?php if ($hasSss): ?>
                        <th colspan="2" class="text-center">SSS</th>
                        <?php endif; ?>
                        <?php if ($hasPh): ?>
                        <th colspan="2" class="text-center">PhilHealth</th>
                        <?php endif; ?>
                        <?php if ($hasPi): ?>
                        <th colspan="2" class="text-center">Pag-IBIG</th>
                        <?php endif; ?>
                        <th rowspan="2" class="text-end align-middle">Total</th>
                    </tr>
                    <tr>
                        <?php if ($hasSss): ?>
                        <th class="text-end small">Employee</th>
                        <th class="text-end small">Employer</th>
                        <?php endif; ?>
                        <?php if ($hasPh): ?>
                        <th class="text-end small">Employee</th>
                        <th class="text-end small">Employer</th>
                        <?php endif; ?>
                        <?php if ($hasPi): ?>
                        <th class="text-end small">Employee</th>
                        <th class="text-end small">Employer</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                <?php
                $lastBranch = null;
                foreach ($rows as $r):
                    $sss = (float) $r['sss_deduction'];
                    $ph  = (float) $r['philhealth_deduction'];
                    $pi  = (float) $r['pagibig_deduction'];
                    // Only sum columns that are actually displayed
                    $rowTotal = ($hasSss ? $sss : 0) + ($hasPh ? $ph : 0) + ($hasPi ? $pi : 0);

                    $branchName = $r['branch_name'] ?: 'No Branch';
                    if ($branchName !== $lastBranch):
                        $lastBranch = $branchName;
                        $colspan = 4;
                        if ($hasSss) $colspan += 2;
                        if ($hasPh)  $colspan += 2;
                        if ($hasPi)  $colspan += 2;
                ?>
                <tr class="table-light">
                    <td colspan="<?= $colspan ?>" class="fw-semibold text-primary small py-1">
                        <i class="fa fa-building me-1"></i><?= esc($branchName) ?>
                    </td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td class="small text-nowrap">
                        <?= date('M Y', strtotime($r['payroll_month'] . '-01')) ?>
                        <span class="badge bg-light text-dark border ms-1" style="font-size:.65rem;">
                            Cut <?= $r['cutoff'] == 1 ? '1' : '2' ?>
                        </span>
                    </td>
                    <td class="small"><?= esc($r['branch_name'] ?: '—') ?></td>
                    <td>
                        <span class="fw-semibold"><?= esc($r['full_name']) ?></span>
                        <span class="text-muted font-monospace ms-1" style="font-size:.75rem;"><?= esc($r['employee_code']) ?></span>
                    </td>
                    <?php if ($hasSss): ?>
                    <td class="text-end <?= $sss > 0 ? '' : 'text-muted' ?>">
                        <?= $sss > 0 ? '₱ ' . number_format($sss, 2) : '—' ?>
                    </td>
                    <td class="text-end <?= $sss > 0 ? 'text-success' : 'text-muted' ?>">
                        <?= $sss > 0 ? '₱ ' . number_format($sss, 2) : '—' ?>
                    </td>
                    <?php endif; ?>
                    <?php if ($hasPh): ?>
                    <td class="text-end <?= $ph > 0 ? '' : 'text-muted' ?>">
                        <?= $ph > 0 ? '₱ ' . number_format($ph, 2) : '—' ?>
                    </td>
                    <td class="text-end <?= $ph > 0 ? 'text-success' : 'text-muted' ?>">
                        <?= $ph > 0 ? '₱ ' . number_format($ph, 2) : '—' ?>
                    </td>
                    <?php endif; ?>
                    <?php if ($hasPi): ?>
                    <td class="text-end <?= $pi > 0 ? '' : 'text-muted' ?>">
                        <?= $pi > 0 ? '₱ ' . number_format($pi, 2) : '—' ?>
                    </td>
                    <td class="text-end <?= $pi > 0 ? 'text-success' : 'text-muted' ?>">
                        <?= $pi > 0 ? '₱ ' . number_format($pi, 2) : '—' ?>
                    </td>
                    <?php endif; ?>
                    <td class="text-end fw-semibold">
                        <?= $rowTotal > 0 ? '₱ ' . number_format($rowTotal, 2) : '—' ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>

                <!-- Grand Total Footer -->
                <tfoot class="table-dark fw-bold">
                    <tr>
                        <td colspan="3" class="text-end">Grand Total</td>
                        <?php if ($hasSss): ?>
                        <td class="text-end">₱ <?= number_format($totSssEmp, 2) ?></td>
                        <td class="text-end">₱ <?= number_format($totSssEmr, 2) ?></td>
                        <?php endif; ?>
                        <?php if ($hasPh): ?>
                        <td class="text-end">₱ <?= number_format($totPhEmp, 2) ?></td>
                        <td class="text-end">₱ <?= number_format($totPhEmr, 2) ?></td>
                        <?php endif; ?>
                        <?php if ($hasPi): ?>
                        <td class="text-end">₱ <?= number_format($totPiEmp, 2) ?></td>
                        <td class="text-end">₱ <?= number_format($totPiEmr, 2) ?></td>
                        <?php endif; ?>
                        <td class="text-end">₱ <?= number_format($totSssEmp + $totPhEmp + $totPiEmp, 2) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mt-2">
    <?php if ($hasSss && ($filters['benefit_type'] === '' || $filters['benefit_type'] === 'sss')): ?>
    <div class="col-md">
        <div class="card border-primary">
            <div class="card-body py-2 text-center">
                <div class="fw-semibold text-primary">SSS</div>
                <div class="small text-muted">Employee</div>
                <div class="fw-bold">₱ <?= number_format($totSssEmp, 2) ?></div>
                <div class="small text-muted mt-1">Employer</div>
                <div class="fw-bold text-success">₱ <?= number_format($totSssEmr, 2) ?></div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php if ($hasPh && ($filters['benefit_type'] === '' || $filters['benefit_type'] === 'philhealth')): ?>
    <div class="col-md">
        <div class="card border-info">
            <div class="card-body py-2 text-center">
                <div class="fw-semibold text-info">PhilHealth</div>
                <div class="small text-muted">Employee</div>
                <div class="fw-bold">₱ <?= number_format($totPhEmp, 2) ?></div>
                <div class="small text-muted mt-1">Employer</div>
                <div class="fw-bold text-success">₱ <?= number_format($totPhEmr, 2) ?></div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php if ($hasPi && ($filters['benefit_type'] === '' || $filters['benefit_type'] === 'pagibig')): ?>
    <div class="col-md">
        <div class="card border-warning">
            <div class="card-body py-2 text-center">
                <div class="fw-semibold" style="color:#ca8a04;">Pag-IBIG</div>
                <div class="small text-muted">Employee</div>
                <div class="fw-bold">₱ <?= number_format($totPiEmp, 2) ?></div>
                <div class="small text-muted mt-1">Employer</div>
                <div class="fw-bold text-success">₱ <?= number_format($totPiEmr, 2) ?></div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <div class="col-md">
        <div class="card border-dark">
            <div class="card-body py-2 text-center">
                <div class="fw-semibold">Grand Total</div>
                <div class="small text-muted">Employee</div>
                <div class="fw-bold fs-6">₱ <?= number_format($totSssEmp + $totPhEmp + $totPiEmp, 2) ?></div>
                <div class="small text-muted mt-1">Employer</div>
                <div class="fw-bold text-success">₱ <?= number_format($totSssEmr + $totPhEmr + $totPiEmr, 2) ?></div>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<style>
@media print {
    .card { border: 1px solid #dee2e6 !important; }
    .btn, .d-print-none { display: none !important; }
    #benefitSummaryTable th, #benefitSummaryTable td { font-size: 0.72rem; }
}
</style>

<?= $this->endSection() ?>
