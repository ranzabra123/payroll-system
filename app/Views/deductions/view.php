<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$paid     = (float)$deduction['total_amount'] - (float)$deduction['remaining_balance'];
$pct      = $deduction['total_amount'] > 0 ? round(($paid / $deduction['total_amount']) * 100, 1) : 0;
$badgeCls = match($deduction['status']) {
    'active'    => 'badge bg-success',
    'completed' => 'badge bg-secondary',
    'cancelled' => 'badge bg-danger',
    default     => 'badge bg-light text-dark',
};
?>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="<?= site_url('deductions') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fa fa-arrow-left"></i>
    </a>
    <h5 class="mb-0 fw-semibold">Deduction Detail</h5>
    <div class="ms-auto d-flex gap-2">
        <?php if (can_do('deductions', 'edit')): ?>
        <a href="<?= site_url('deductions/edit/' . $deduction['id']) ?>" class="btn btn-sm btn-outline-primary">
            <i class="fa fa-pen-to-square me-1"></i>Edit
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">
    <!-- Employee Info -->
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header fw-semibold"><i class="fa fa-user me-2"></i>Employee</div>
            <div class="card-body">
                <div class="fw-bold fs-6"><?= esc($deduction['full_name']) ?></div>
                <div class="text-muted font-monospace small mb-2"><?= esc($deduction['employee_code']) ?></div>
                <div class="small text-muted"><?= esc($deduction['position'] ?? '') ?></div>
                <div class="small text-muted"><?= esc($deduction['department'] ?? '') ?></div>
                <hr/>
                <a href="<?= site_url('employees/view/' . $deduction['employee_id']) ?>"
                   class="btn btn-sm btn-outline-secondary w-100">
                    <i class="fa fa-external-link-alt me-1"></i>View Employee
                </a>
            </div>
        </div>
    </div>

    <!-- Deduction Summary -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header fw-semibold d-flex justify-content-between">
                <span><i class="fa fa-file-invoice-dollar me-2"></i>Deduction Summary</span>
                <span class="<?= $badgeCls ?>"><?= ucfirst($deduction['status']) ?></span>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <div class="text-muted small">Type</div>
                        <div class="fw-semibold">
                            <span class="badge <?= $deduction['type'] === 'Cash Advance' ? 'bg-info text-dark' : 'bg-warning text-dark' ?>">
                                <?= esc($deduction['type']) ?>
                            </span>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Deduct On</div>
                        <div class="fw-semibold"><?= match($deduction['cutoff']) { '15' => 'Every 15th (1st Cutoff)', '30' => 'Every 30th (2nd Cutoff)', default => 'Every Cutoff (15 &amp; 30)' } ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Description</div>
                        <div class="fw-semibold"><?= esc($deduction['description'] ?: '—') ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Start Date</div>
                        <div class="fw-semibold"><?= date('M j, Y', strtotime($deduction['start_date'])) ?></div>
                    </div>
                </div>

                <!-- Amounts -->
                <div class="row g-3 mb-4">
                    <div class="col-sm-4">
                        <div class="card bg-light text-center p-3">
                            <div class="text-muted small">Total Amount</div>
                            <div class="fw-bold fs-5">₱ <?= number_format($deduction['total_amount'], 2) ?></div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="card bg-success bg-opacity-10 text-center p-3">
                            <div class="text-muted small">Paid So Far</div>
                            <div class="fw-bold fs-5 text-success">₱ <?= number_format($paid, 2) ?></div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="card bg-danger bg-opacity-10 text-center p-3">
                            <div class="text-muted small">Remaining</div>
                            <div class="fw-bold fs-5 text-danger">₱ <?= number_format($deduction['remaining_balance'], 2) ?></div>
                        </div>
                    </div>
                </div>

                <!-- Progress -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span>Payment Progress</span>
                        <span><?= $pct ?>%</span>
                    </div>
                    <div class="progress" style="height:12px">
                        <div class="progress-bar <?= $pct >= 100 ? 'bg-success' : 'bg-warning' ?>"
                             style="width:<?= $pct ?>%"></div>
                    </div>
                </div>

                <!-- Per cutoff & remaining terms -->
                <div class="row g-2 small text-muted">
                    <div class="col-sm-6">
                        <i class="fa fa-calculator me-1"></i>
                        <strong>₱ <?= number_format($deduction['amount_per_cutoff'], 2) ?></strong> per cutoff
                    </div>
                    <div class="col-sm-6">
                        <i class="fa fa-hourglass-half me-1"></i>
                        <strong><?= $termsLeft ?></strong> term<?= $termsLeft !== 1 ? 's' : '' ?> remaining
                    </div>
                    <?php if ($deduction['notes']): ?>
                    <div class="col-12 mt-2">
                        <i class="fa fa-note-sticky me-1"></i><?= esc($deduction['notes']) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Deduction History -->
<div class="card mt-4">
    <div class="card-header fw-semibold d-flex align-items-center gap-2">
        <i class="fa fa-clock-rotate-left text-primary"></i>
        Deduction History
        <span class="badge bg-secondary ms-1"><?= count($history) ?></span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($history)): ?>
        <div class="text-center text-muted py-4">
            <i class="fa fa-inbox fa-2x mb-2 d-block opacity-25"></i>
            No deduction history yet.
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle small">
                <thead class="table-light">
                    <tr>
                        <th>Payroll Period</th>
                        <th class="text-center">Cutoff</th>
                        <th class="text-end">Amount Deducted</th>
                        <th class="text-end">Balance Before</th>
                        <th class="text-end">Balance After</th>
                        <th class="text-center">Payroll Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($history as $h): ?>
                <tr>
                    <td class="fw-semibold">
                        <?= date('M Y', strtotime($h['period_start'])) ?>
                        <div class="text-muted" style="font-size:.75rem;">
                            <?= date('M j', strtotime($h['period_start'])) ?> – <?= date('M j, Y', strtotime($h['period_end'])) ?>
                        </div>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-secondary">
                            <?= $h['cutoff_num'] == 1 ? '1st (1–15)' : '2nd (16–end)' ?>
                        </span>
                    </td>
                    <td class="text-end text-danger fw-semibold">– ₱ <?= number_format($h['amount_deducted'], 2) ?></td>
                    <td class="text-end">₱ <?= number_format($h['balance_before'], 2) ?></td>
                    <td class="text-end fw-semibold <?= (float)$h['balance_after'] <= 0 ? 'text-success' : '' ?>">
                        ₱ <?= number_format($h['balance_after'], 2) ?>
                        <?php if ((float)$h['balance_after'] <= 0): ?>
                        <span class="badge bg-success ms-1">Paid</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <span class="badge <?= $h['payroll_status'] === 'finalized' ? 'bg-success' : 'bg-warning text-dark' ?>">
                            <?= ucfirst($h['payroll_status']) ?>
                        </span>
                    </td>
                    <td class="text-muted"><?= date('M j, Y', strtotime($h['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
