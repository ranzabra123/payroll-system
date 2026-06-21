<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$months = [
    '1'=>'January','2'=>'February','3'=>'March','4'=>'April',
    '5'=>'May','6'=>'June','7'=>'July','8'=>'August',
    '9'=>'September','10'=>'October','11'=>'November','12'=>'December',
];
$currentYear = (int) date('Y');

$totalAmount    = array_sum(array_column($deductions, 'total_amount'));
$totalRemaining = array_sum(array_column($deductions, 'remaining_balance'));
$totalPaid      = $totalAmount - $totalRemaining;

$monthLabel  = $filters['month'] !== '' ? ($months[$filters['month']] ?? '') . ' ' . $filters['year'] : 'All Months ' . $filters['year'];
$typeLabel   = $filters['type']   !== '' ? $filters['type']   : 'All Types';
$statusLabel = $filters['status'] !== '' ? ucfirst($filters['status']) : 'All Status';
$cutoffLabel = match($filters['cutoff'] ?? '') {
    '15'    => '15th',
    '30'    => '30th',
    'both'  => 'Every Cutoff',
    default => 'All Cutoffs',
};
?>

<style>
@media print {
    @page { size: landscape; margin: 12mm; }
    body * { visibility: hidden; }
    #printArea, #printArea * { visibility: visible; }
    #printArea { position: absolute; inset: 0; padding: 10px; }
    .no-print { display: none !important; }
}
</style>

<!-- Header -->
<div class="d-flex align-items-center gap-2 mb-3 no-print">
    <a href="<?= site_url('deductions') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fa fa-arrow-left"></i>
    </a>
    <h5 class="mb-0 fw-semibold">Deductions Summary Report</h5>
    <button class="btn btn-sm btn-outline-secondary ms-auto" onclick="window.print()">
        <i class="fa fa-print me-1"></i>Print
    </button>
</div>

<!-- Filters -->
<div class="card mb-3 p-2 no-print">
    <form method="get" class="d-flex flex-wrap gap-2 align-items-center">
        <label class="form-label mb-0 fw-medium">Year:</label>
        <select name="year" class="form-select form-select-sm" style="width:110px;">
            <?php for ($y = $currentYear; $y >= $currentYear - 5; $y--): ?>
            <option value="<?= $y ?>" <?= (string)$y === (string)$filters['year'] ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>

        <label class="form-label mb-0 fw-medium">Month:</label>
        <select name="month" class="form-select form-select-sm" style="width:140px;">
            <option value="">All Months</option>
            <?php foreach ($months as $num => $name): ?>
            <option value="<?= $num ?>" <?= (string)$filters['month'] === (string)$num ? 'selected' : '' ?>><?= $name ?></option>
            <?php endforeach; ?>
        </select>

        <select name="type" class="form-select form-select-sm" style="width:150px;">
            <option value="">All Types</option>
            <option value="Cash Advance" <?= $filters['type'] === 'Cash Advance' ? 'selected' : '' ?>>Cash Advance</option>
            <option value="Debt"         <?= $filters['type'] === 'Debt'         ? 'selected' : '' ?>>Debt / Loan</option>
            <option value="Pharmacy"     <?= $filters['type'] === 'Pharmacy'     ? 'selected' : '' ?>>Pharmacy</option>
        </select>

        <select name="status" class="form-select form-select-sm" style="width:140px;">
            <option value="">All Status</option>
            <option value="active"    <?= $filters['status'] === 'active'    ? 'selected' : '' ?>>Active</option>
            <option value="completed" <?= $filters['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
            <option value="cancelled" <?= $filters['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
        </select>

        <select name="cutoff" class="form-select form-select-sm" style="width:160px;">
            <option value="">All Cutoffs</option>
            <option value="15"   <?= $filters['cutoff'] === '15'   ? 'selected' : '' ?>>Every 15th</option>
            <option value="30"   <?= $filters['cutoff'] === '30'   ? 'selected' : '' ?>>Every 30th</option>
            <option value="both" <?= $filters['cutoff'] === 'both' ? 'selected' : '' ?>>Every Cutoff (15 &amp; 30)</option>
        </select>

        <input type="text" name="q" class="form-control form-control-sm flex-grow-1"
               placeholder="Search employee…" value="<?= esc($filters['search']) ?>"/>
        <button class="btn btn-sm btn-primary"><i class="fa fa-filter me-1"></i>Filter</button>
        <a href="<?= site_url('deductions/summary') ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
    </form>
</div>

<!-- Report identity bar (screen only) -->
<div class="card mb-3 border-primary no-print">
    <div class="card-body py-2 px-3 d-flex flex-wrap align-items-center gap-3">
        <div>
            <span class="text-muted small fw-semibold text-uppercase me-1">Report</span>
            <span class="fw-bold text-primary">Deductions Summary Report</span>
        </div>
        <div class="vr d-none d-md-block"></div>
        <div><span class="text-muted small me-1">Period:</span>
            <span class="badge bg-secondary"><?= esc($monthLabel) ?></span></div>
        <div><span class="text-muted small me-1">Type:</span>
            <span class="badge <?= $filters['type'] !== '' ? 'bg-primary' : 'bg-light text-dark border' ?>"><?= esc($typeLabel) ?></span></div>
        <div><span class="text-muted small me-1">Status:</span>
            <span class="badge <?= $filters['status'] !== '' ? 'bg-info text-dark' : 'bg-light text-dark border' ?>"><?= esc($statusLabel) ?></span></div>
        <div><span class="text-muted small me-1">Cutoff:</span>
            <span class="badge bg-light text-dark border"><?= esc($cutoffLabel) ?></span></div>
        <?php if ($filters['search'] !== ''): ?>
        <div><span class="text-muted small me-1">Search:</span>
            <span class="badge bg-warning text-dark"><?= esc($filters['search']) ?></span></div>
        <?php endif; ?>
    </div>
</div>

<div id="printArea">
    <!-- Print-only header -->
    <div class="d-none d-print-block mb-3">
        <h4 class="fw-bold mb-1">Deductions Summary Report</h4>
        <div class="text-muted small">
            Period: <?= esc($monthLabel) ?> &nbsp;|&nbsp;
            Type: <?= esc($typeLabel) ?> &nbsp;|&nbsp;
            Status: <?= esc($statusLabel) ?> &nbsp;|&nbsp;
            Cutoff: <?= esc($cutoffLabel) ?>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th class="text-center">Cutoff</th>
                            <th class="text-center">Start Date</th>
                            <th class="text-end">Total Amount</th>
                            <th class="text-end">Paid</th>
                            <th class="text-end">Remaining</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($deductions)): ?>
                        <tr>
                            <td colspan="11" class="text-center text-muted py-4">
                                No deduction records found for this period.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($deductions as $i => $d):
                            $paid = (float)$d['total_amount'] - (float)$d['remaining_balance'];
                            $badgeCls = match($d['status']) {
                                'active'    => 'bg-success',
                                'completed' => 'bg-secondary',
                                'cancelled' => 'bg-danger',
                                default     => 'bg-light text-dark',
                            };
                        ?>
                        <tr>
                            <td class="text-muted small"><?= $i + 1 ?></td>
                            <td>
                                <div class="fw-semibold"><?= esc($d['full_name']) ?></div>
                                <div class="text-muted small font-monospace"><?= esc($d['employee_code']) ?></div>
                            </td>
                            <td class="small text-muted"><?= esc($d['department'] ?? '—') ?></td>
                            <td>
                                <?php
                                    [$_tbadge, $_tlabel] = match($d['type']) {
                                        'Cash Advance' => ['bg-info text-dark',    'CA'],
                                        'Pharmacy'     => ['bg-success text-white', 'Pharmacy'],
                                        default        => ['bg-warning text-dark',  'Debt'],
                                    };
                                ?>
                                <span class="badge <?= $_tbadge ?>"><?= $_tlabel ?></span>
                            </td>
                            <td class="small"><?= esc($d['description'] ?? '—') ?></td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border">
                                    <?= match($d['cutoff']) { '15' => '15th', '30' => '30th', default => '15 & 30' } ?>
                                </span>
                            </td>
                            <td class="text-center small"><?= date('M j, Y', strtotime($d['start_date'])) ?></td>
                            <td class="text-end">₱ <?= number_format($d['total_amount'], 2) ?></td>
                            <td class="text-end text-success">₱ <?= number_format($paid, 2) ?></td>
                            <td class="text-end fw-semibold <?= (float)$d['remaining_balance'] > 0 ? 'text-danger' : 'text-muted' ?>">
                                ₱ <?= number_format($d['remaining_balance'], 2) ?>
                            </td>
                            <td class="text-center">
                                <span class="badge <?= $badgeCls ?>"><?= ucfirst($d['status']) ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                    <?php if (! empty($deductions)): ?>
                    <tfoot>
                        <tr class="table-secondary fw-bold border-top border-2">
                            <td colspan="7" class="text-end pe-3 text-muted small">
                                Total (<?= count($deductions) ?> record<?= count($deductions) !== 1 ? 's' : '' ?>):
                            </td>
                            <td class="text-end">₱ <?= number_format($totalAmount, 2) ?></td>
                            <td class="text-end text-success">₱ <?= number_format($totalPaid, 2) ?></td>
                            <td class="text-end text-danger fw-bold">₱ <?= number_format($totalRemaining, 2) ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>

    <!-- Grand total cards -->
    <?php if (! empty($deductions)): ?>
    <div class="row g-3 mt-2">
        <div class="col-md-4">
            <div class="card text-center border-0 bg-light">
                <div class="card-body py-3">
                    <div class="text-muted small mb-1">Total Deduction Amount</div>
                    <div class="fw-bold fs-4">₱ <?= number_format($totalAmount, 2) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center border-0 bg-light">
                <div class="card-body py-3">
                    <div class="text-muted small mb-1">Total Paid / Collected</div>
                    <div class="fw-bold fs-4 text-success">₱ <?= number_format($totalPaid, 2) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center border-0 bg-light">
                <div class="card-body py-3">
                    <div class="text-muted small mb-1">Total Outstanding Balance</div>
                    <div class="fw-bold fs-4 text-danger">₱ <?= number_format($totalRemaining, 2) ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
