<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
  $totalGross = array_sum(array_column($payrolls, 'total_gross'));
  $totalDed   = array_sum(array_column($payrolls, 'total_deductions'));
  $totalNet   = array_sum(array_column($payrolls, 'total_net'));
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

<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <div class="d-flex align-items-center gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="window.print()" title="Print payroll list">
            <i class="fa fa-print me-1"></i>Print
        </button>
        <h5 class="mb-0 fw-semibold">Payroll Runs</h5>
    </div>
    <a href="<?= site_url('payroll/create') ?>" class="btn btn-primary btn-sm">
        <i class="fa fa-plus me-1"></i>Generate Payroll
    </a>
</div>

<!-- Year / Month Filter -->
<div class="card mb-3 p-2 no-print">
    <form method="get" class="d-flex gap-2 align-items-center flex-wrap">
        <label class="form-label mb-0 fw-medium">Year:</label>
        <select name="year" class="form-select form-select-sm" style="width:110px;">
            <?php
              $currentYear = (int) date('Y');
              for ($y = $currentYear; $y >= $currentYear - 5; $y--):
            ?>
            <option value="<?= $y ?>" <?= (string)$y === (string)$selYear ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
        <label class="form-label mb-0 fw-medium ms-2">Month:</label>
        <select name="month" class="form-select form-select-sm" style="width:150px;">
            <option value="" <?= $selMonth === '' ? 'selected' : '' ?>>All Months</option>
            <?php
              $months = ['1'=>'January','2'=>'February','3'=>'March','4'=>'April','5'=>'May','6'=>'June',
                         '7'=>'July','8'=>'August','9'=>'September','10'=>'October','11'=>'November','12'=>'December'];
              foreach ($months as $num => $name):
            ?>
            <option value="<?= $num ?>" <?= (string)$selMonth === (string)$num ? 'selected' : '' ?>><?= $name ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-sm btn-primary">
            <i class="fa fa-filter me-1"></i>Filter
        </button>
        <a href="<?= site_url('payroll') ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
    </form>
</div>

<div id="printArea">
    <!-- Print header (only visible when printing) -->
    <div class="d-none d-print-block mb-2">
        <h5 class="fw-bold mb-0">Payroll Runs</h5>
        <small class="text-muted">
            <?= $selYear ?>
            <?= $selMonth !== '' ? ' – ' . date('F', mktime(0,0,0,(int)$selMonth,1)) : ' – All Months' ?>
        </small>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Period</th>
                            <th>Cutoff</th>
                            <th>Start</th>
                            <th>End</th>
                            <th class="text-end">Working Days</th>
                            <th class="text-end">Gross Pay</th>
                            <th class="text-end">Deductions</th>
                            <th class="text-end">Net Pay</th>
                            <th>Status</th>
                            <th class="no-print">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($payrolls)): ?>
                    <tr><td colspan="10" class="text-center text-muted py-4">
                        No payroll runs found.
                        <a href="<?= site_url('payroll/create') ?>" class="no-print">Generate one now.</a>
                    </td></tr>
                    <?php else: ?>
                        <?php foreach ($payrolls as $p): ?>
                        <tr>
                            <td class="fw-semibold"><?= date('M Y', strtotime($p['period_start'])) ?></td>
                            <td>
                                <span class="badge bg-secondary">
                                    <?= $p['cutoff'] == 1 ? '1st (1–15)' : '2nd (16–end)' ?>
                                </span>
                            </td>
                            <td class="small"><?= date('M j', strtotime($p['period_start'])) ?></td>
                            <td class="small"><?= date('M j', strtotime($p['period_end'])) ?></td>
                            <td class="text-end"><?= $p['working_days'] ?></td>
                            <td class="text-end text-success fw-semibold">₱ <?= number_format($p['total_gross'], 2) ?></td>
                            <td class="text-end text-danger">₱ <?= number_format($p['total_deductions'], 2) ?></td>
                            <td class="text-end fw-bold">₱ <?= number_format($p['total_net'], 2) ?></td>
                            <td>
                                <span class="badge <?= $p['status'] === 'finalized' ? 'badge-final' : 'badge-draft' ?>">
                                    <?= ucfirst($p['status']) ?>
                                </span>
                            </td>
                            <td class="no-print">
                                <div class="d-flex gap-1">
                                    <a href="<?= site_url('payroll/view/' . $p['id']) ?>"
                                       class="btn btn-sm btn-outline-primary" title="View">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                    <?php if ($p['status'] === 'draft'): ?>
                                    <a href="<?= site_url('payroll/finalize/' . $p['id']) ?>"
                                       class="btn btn-sm btn-outline-success" title="Finalize"
                                       data-confirm="Finalize this payroll? This cannot be undone.">
                                        <i class="fa fa-check-double"></i>
                                    </a>
                                    <a href="<?= site_url('payroll/recalculate/' . $p['id']) ?>"
                                       class="btn btn-sm btn-outline-warning" title="Recalculate">
                                        <i class="fa fa-rotate"></i>
                                    </a>
                                    <a href="<?= site_url('payroll/delete/' . $p['id']) ?>"
                                       class="btn btn-sm btn-outline-danger" title="Delete"
                                       data-confirm="Delete this draft payroll?">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                    <a href="<?= site_url('payslip/bulk/' . $p['id']) ?>"
                                       class="btn btn-sm btn-outline-secondary" title="Bulk Payslips">
                                        <i class="fa fa-file-invoice"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                    <?php if (! empty($payrolls)): ?>
                    <tfoot>
                        <tr class="table-light fw-bold border-top border-2">
                            <td colspan="5" class="text-end text-muted small pe-3">Totals:</td>
                            <td class="text-end text-success">₱ <?= number_format($totalGross, 2) ?></td>
                            <td class="text-end text-danger">₱ <?= number_format($totalDed, 2) ?></td>
                            <td class="text-end">₱ <?= number_format($totalNet, 2) ?></td>
                            <td></td>
                            <td class="no-print"></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

