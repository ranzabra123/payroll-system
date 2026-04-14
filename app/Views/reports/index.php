<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-semibold">Reports</h5>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-medium">Report Type</label>
                <select name="type" class="form-select" onchange="this.form.submit()">
                    <option value="cutoff"   <?= $type === 'cutoff'   ? 'selected' : '' ?>>Cutoff Summary</option>
                    <option value="monthly"  <?= $type === 'monthly'  ? 'selected' : '' ?>>Monthly Summary</option>
                    <option value="employee" <?= $type === 'employee' ? 'selected' : '' ?>>Per Employee</option>
                </select>
            </div>

            <?php if ($type !== 'employee'): ?>
            <div class="col-md-3">
                <label class="form-label fw-medium">Month</label>
                <input type="month" name="month" class="form-control" value="<?= esc($month) ?>"/>
            </div>
            <?php else: ?>
            <div class="col-md-4">
                <label class="form-label fw-medium">Employee</label>
                <select name="employee_id" class="form-select">
                    <option value="">— Select Employee —</option>
                    <?php foreach ($employees as $emp): ?>
                    <option value="<?= $emp['id'] ?>" <?= $empId == $emp['id'] ? 'selected' : '' ?>>
                        <?= esc($emp['full_name']) ?> (<?= esc($emp['employee_code']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fa fa-filter me-1"></i>Filter
                </button>
            </div>
            <div class="col-md-2">
                <a href="<?= site_url('reports/export-csv') . '?' . http_build_query(['type' => $type, 'month' => $month, 'employee_id' => $empId]) ?>"
                   class="btn btn-outline-success w-100">
                    <i class="fa fa-file-csv me-1"></i>Export CSV
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Report Output -->
<?php if ($type === 'cutoff'): ?>

<div class="card">
    <div class="card-header">
        <i class="fa fa-scissors me-2"></i>Cutoff Payroll Summary – <?= date('F Y', strtotime($month . '-01')) ?>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Period</th>
                        <th>Cutoff</th>
                        <th>Start – End</th>
                        <th class="text-center">Working Days</th>
                        <th class="text-end">Total Gross</th>
                        <th class="text-end">Total Deductions</th>
                        <th class="text-end fw-bold">Total Net</th>
                        <th>Status</th>
                        <th>View</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($data)): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">No finalized payrolls found.</td></tr>
                <?php else: ?>
                    <?php foreach ($data as $row): ?>
                    <tr>
                        <td><?= date('F Y', strtotime($row['period_start'])) ?></td>
                        <td><span class="badge bg-secondary"><?= $row['cutoff'] == 1 ? '1st' : '2nd' ?></span></td>
                        <td class="small"><?= date('M j', strtotime($row['period_start'])) ?> – <?= date('M j', strtotime($row['period_end'])) ?></td>
                        <td class="text-center"><?= $row['working_days'] ?></td>
                        <td class="text-end text-success">₱ <?= number_format($row['total_gross'], 2) ?></td>
                        <td class="text-end text-danger">₱ <?= number_format($row['total_deductions'], 2) ?></td>
                        <td class="text-end fw-bold">₱ <?= number_format($row['total_net'], 2) ?></td>
                        <td><span class="badge badge-final">Finalized</span></td>
                        <td>
                            <a href="<?= site_url('payroll/view/' . $row['id']) ?>"
                               class="btn btn-sm btn-outline-primary">
                                <i class="fa fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php elseif ($type === 'monthly'): ?>

<div class="card">
    <div class="card-header">
        <i class="fa fa-calendar me-2"></i>Monthly Payroll Summary
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th class="text-end">Total Gross</th>
                        <th class="text-end">Total Deductions</th>
                        <th class="text-end fw-bold">Total Net</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($data)): ?>
                <tr><td colspan="4" class="text-center text-muted py-4">No data available.</td></tr>
                <?php else: ?>
                    <?php $totalGross = $totalDed = $totalNet = 0; ?>
                    <?php foreach ($data as $row): ?>
                    <?php
                        $totalGross += $row['total_gross'];
                        $totalDed   += $row['total_deductions'];
                        $totalNet   += $row['total_net'];
                    ?>
                    <tr>
                        <td class="fw-semibold"><?= date('F Y', strtotime($row['payroll_month'] . '-01')) ?></td>
                        <td class="text-end text-success">₱ <?= number_format($row['total_gross'], 2) ?></td>
                        <td class="text-end text-danger">₱ <?= number_format($row['total_deductions'], 2) ?></td>
                        <td class="text-end fw-bold">₱ <?= number_format($row['total_net'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
                <?php if (! empty($data)): ?>
                <tfoot>
                    <tr class="table-dark fw-bold">
                        <td>TOTAL</td>
                        <td class="text-end">₱ <?= number_format($totalGross, 2) ?></td>
                        <td class="text-end">₱ <?= number_format($totalDed, 2) ?></td>
                        <td class="text-end">₱ <?= number_format($totalNet, 2) ?></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<?php elseif ($type === 'employee'): ?>

<?php if (! $empId): ?>
<div class="alert alert-info">
    <i class="fa fa-circle-info me-2"></i>Select an employee above to view their payroll history.
</div>
<?php elseif (empty($data)): ?>
<div class="alert alert-warning">
    <i class="fa fa-triangle-exclamation me-2"></i>No finalized payroll records for this employee.
</div>
<?php else: ?>

<div class="card">
    <div class="card-header">
        <i class="fa fa-user me-2"></i>
        Payroll History – <?= esc($data[0]['full_name']) ?> (<?= esc($data[0]['employee_code']) ?>)
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Period</th>
                        <th>Cutoff</th>
                        <th class="text-center">Days Worked</th>
                        <th class="text-end">Basic Pay</th>
                        <th class="text-end">Gross</th>
                        <th class="text-end">Total Ded.</th>
                        <th class="text-end fw-bold">Net Pay</th>
                    </tr>
                </thead>
                <tbody>
                <?php $totalGross = $totalDed = $totalNet = 0; ?>
                <?php foreach ($data as $row): ?>
                <?php
                    $totalGross += $row['gross_pay'];
                    $totalDed   += $row['total_deductions'];
                    $totalNet   += $row['net_pay'];
                ?>
                <tr>
                    <td><?= date('F Y', strtotime($row['period_start'])) ?></td>
                    <td><span class="badge bg-secondary"><?= $row['cutoff'] == 1 ? '1st' : '2nd' ?></span></td>
                    <td class="text-center"><?= $row['days_worked'] ?>/<?= $row['working_days'] ?></td>
                    <td class="text-end">₱ <?= number_format($row['basic_pay'], 2) ?></td>
                    <td class="text-end text-success">₱ <?= number_format($row['gross_pay'], 2) ?></td>
                    <td class="text-end text-danger">₱ <?= number_format($row['total_deductions'], 2) ?></td>
                    <td class="text-end fw-bold">₱ <?= number_format($row['net_pay'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-dark fw-bold">
                        <td colspan="5">TOTAL</td>
                        <td class="text-end">₱ <?= number_format($totalGross, 2) ?></td>
                        <td class="text-end">₱ <?= number_format($totalDed, 2) ?></td>
                        <td class="text-end">₱ <?= number_format($totalNet, 2) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php endif; ?>

<?= $this->endSection() ?>
