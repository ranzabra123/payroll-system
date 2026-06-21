<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <?php $_logoUrl = setting_logo_url();
          $_companyName = setting('company_name', 'PayrollPH'); ?>
    <title>Payslip – <?= esc($detail['full_name']) ?> – <?= esc($_companyName) ?></title>
    <?php if ($_logoUrl): ?><link rel="icon" type="image/png" href="<?= esc($_logoUrl) ?>"><?php endif; ?>
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.min.css') ?>"/>
    <link rel="stylesheet" href="<?= base_url('assets/css/custom.css') ?>"/>
</head>
<body style="background:#f1f5f9;">

<?php $_companyName = setting('company_name', 'PayrollPH'); ?>
<?php $_logoUrl = setting_logo_url(); ?>
<?php $_tagline = setting('company_tagline', 'Payroll Management System'); ?>

<div class="container py-4">
    <!-- Print Button -->
    <div class="text-end mb-3 no-print">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fa fa-print me-1"></i>Print / Save PDF
        </button>
        <a href="<?= site_url('payroll/view/' . $payroll['id']) ?>"
           class="btn btn-outline-secondary ms-2">← Back to Payroll</a>
    </div>

    <!-- Payslip Document -->
    <div class="payslip-wrapper" id="payslip">
        <!-- Header -->
        <div class="payslip-header">
            <div class="row align-items-center">
                <div class="col">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <?php if ($_logoUrl): ?>
                        <img src="<?= esc($_logoUrl) ?>" alt="Logo" style="max-height: 35px; flex-shrink: 0;">
                        <?php endif; ?>
                        <div>
                            <h5 class="mb-1 fw-bold"><?= esc($_companyName) ?></h5>
                            <div class="opacity-75 small" style="font-size: 0.85rem;"><?= esc($_tagline) ?></div>
                        </div>
                    </div>
                </div>
                <div class="col text-end">
                    <div class="fw-bold" style="font-size: 1.1rem;">PAYSLIP</div>
                    <div class="opacity-75 small" style="font-size: 0.85rem;">
                        <?= \App\Models\PayrollModel::periodLabel($payroll) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Employee Info -->
        <div class="p-3 border-bottom">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted" style="width:130px;">Employee Name</td>
                            <td class="fw-semibold">: <?= esc($detail['full_name']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Employee ID</td>
                            <td class="font-monospace">: <?= esc($detail['employee_code']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Position</td>
                            <td>: <?= esc($detail['position']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Department</td>
                            <td>: <?= esc($detail['department'] ?? '—') ?></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted" style="width:130px;">Pay Period</td>
                            <td>: <?= date('M j', strtotime($payroll['period_start'])) ?>–<?= date('M j, Y', strtotime($payroll['period_end'])) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Cutoff</td>
                            <td>: <?= $payroll['cutoff'] == 1 ? '1st (1–15)' : '2nd (16–end)' ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Date Hired</td>
                            <td>: <?= date('M j, Y', strtotime($detail['date_hired'])) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Monthly Salary</td>
                            <td class="fw-semibold">: ₱ <?= number_format(round($detail['employee_salary']), 2) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Attendance Summary -->
        <div class="p-3 border-bottom">
            <h6 class="fw-bold text-uppercase small text-muted mb-2">Attendance Summary</h6>
            <div class="row text-center g-2">
                <?php
                    $cols = [
                        ['Working Days', $detail['working_days'], 'bg-secondary'],
                        ['Days Worked', (float)$detail['days_worked'], 'bg-primary'],
                        ['Whole Days', $detail['whole_days'], '#22c55e'],
                        ['Half Days', $detail['half_days'], '#f59e0b'],
                        ['Absent (Whole Days)', (float)($detail['absent_days'] ?? 0), '#ef4444'],
                        ['OT Hours', $detail['overtime_hours'] . ' hrs', '#7c3aed'],
                    ];
                ?>
                <?php foreach ($cols as [$label, $val, $color]): ?>
                <div class="col">
                    <div style="background:<?= $color ?>;color:#fff;border-radius:8px;padding:.4rem;">
                        <div class="fw-bold"><?= $val ?></div>
                        <div style="font-size:.68rem;"><?= $label ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Earnings & Deductions -->
        <div class="row g-0">
            <!-- Earnings -->
            <div class="col-md-6 p-3 border-end border-bottom">
                <h6 class="fw-bold text-uppercase small text-muted mb-3">Earnings</h6>

                <div class="payslip-row">
                    <span>Basic Pay (Semi-monthly)</span>
                    <span>₱ <?= number_format(round($detail['basic_pay']), 2) ?></span>
                </div>

                <?php if (($detail['special_adjustments'] ?? 0) != 0): ?>
                <div class="payslip-row">
                    <span>Special Adjustments</span>
                    <span>₱ <?= number_format(round($detail['special_adjustments']), 2) ?></span>
                </div>
                <?php endif; ?>

                <div class="payslip-row payslip-total mt-2">
                    <span class="fw-bold">GROSS PAY</span>
                    <span class="text-success fw-bold">₱ <?= number_format(round($detail['gross_pay']), 2) ?></span>
                </div>
            </div>

            <!-- Deductions -->
            <div class="col-md-6 p-3 border-bottom">
                <h6 class="fw-bold text-uppercase small text-muted mb-3">Deductions</h6>

                <?php
                    // Absent/half-day deduction
                    $absentDed  = (float)($detail['absent_deduction'] ?? 0);
                    $absentDays = (float)($detail['absent_days'] ?? 0);
                    $halfDays   = (float)($detail['half_days'] ?? 0);
                    // For legacy records, compute dynamically if absent_deduction not stored.
                    // Use daily_rate (= monthly_salary / dept_working_days) as the per-day basis.
                    // Half-days are intentionally excluded here because legacy records cannot
                    // distinguish Sunday half-days (paid) from weekday half-days (deducted).
                    if ($absentDed == 0 && $absentDays > 0) {
                        $dailySal  = (float)$detail['daily_rate'];
                        $absentDed = round($dailySal * $absentDays, 2);
                    }
                    // Split empDeds into pharmacy and other based on paid deduction history
                    $pharmacyDeds = array_filter($empDeds ?? [], fn($e) => ($e['type'] ?? '') === 'Pharmacy');
                    $otherEmpDeds = array_filter($empDeds ?? [], fn($e) => ($e['type'] ?? '') !== 'Pharmacy');
                    $pharmacyTotal = array_sum(array_column($pharmacyDeds, 'amount_deducted'));
                ?>

                <?php if ($absentDays > 0): ?>
                <div class="payslip-row">
                    <span>Absent (<?= number_format($absentDays, 0) ?> day<?= $absentDays != 1 ? 's' : '' ?>)</span>
                    <?php if ($halfDays == 0): ?><span class="text-danger">₱ <?= number_format($absentDed, 2) ?></span><?php endif; ?>
                </div>
                <?php endif; ?>
                <?php if ($halfDays > 0): ?>
                <div class="payslip-row">
                    <span>Half Day (<?= number_format($halfDays, 0) ?>)</span>
                    <?php if ($absentDays == 0): ?><span class="text-danger">₱ <?= number_format($absentDed, 2) ?></span><?php endif; ?>
                </div>
                <?php endif; ?>
                <?php if ($absentDays > 0 && $halfDays > 0): ?>
                <div class="payslip-row">
                    <span class="text-muted small">Total attendance deduction</span>
                    <span class="text-danger">₱ <?= number_format($absentDed, 2) ?></span>
                </div>
                <?php endif; ?>

                <?php if ($pharmacyTotal > 0): ?>
                    <?php foreach ($pharmacyDeds as $ed): ?>
                <div class="payslip-row">
                    <span><?= esc($ed['description'] ?: $ed['type']) ?></span>
                    <span class="text-danger">₱ <?= number_format(round($ed['amount_deducted']), 2) ?></span>
                </div>
                    <?php endforeach; ?>
                    <div class="payslip-row text-muted small">
                        <span>Pharmacy total</span>
                        <span>₱ <?= number_format(round($pharmacyTotal), 2) ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($detail['sss_deduction'] > 0): ?>
                <div class="payslip-row">
                    <span>SSS</span>
                    <span class="text-danger">₱ <?= number_format(round($detail['sss_deduction']), 2) ?></span>
                </div>
                <?php endif; ?>

                <?php if ($detail['philhealth_deduction'] > 0): ?>
                <div class="payslip-row">
                    <span>PhilHealth</span>
                    <span class="text-danger">₱ <?= number_format(round($detail['philhealth_deduction']), 2) ?></span>
                </div>
                <?php endif; ?>

                <?php if ($detail['pagibig_deduction'] > 0): ?>
                <div class="payslip-row">
                    <span>Pag-IBIG</span>
                    <span class="text-danger">₱ <?= number_format(round($detail['pagibig_deduction']), 2) ?></span>
                </div>
                <?php endif; ?>

                <?php if (! empty($otherEmpDeds) || $detail['other_deductions'] > 0): ?>
                <?php if (! empty($otherEmpDeds)):
                    foreach ($otherEmpDeds as $ed): ?>
                <div class="payslip-row">
                    <span><?= esc($ed['description'] ?? $ed['type'] ?? 'Other Deduction') ?></span>
                    <span class="text-danger">₱ <?= number_format(round($ed['amount_deducted']), 2) ?></span>
                </div>
                <?php endforeach;
                elseif ($detail['other_deductions'] > 0): ?>
                <div class="payslip-row">
                    <span>Other Deductions</span>
                    <span class="text-danger">₱ <?= number_format(round($detail['other_deductions']), 2) ?></span>
                </div>
                <?php endif; ?>
                <?php endif; ?>

                <div class="payslip-row payslip-total mt-2">
                    <span class="fw-bold">TOTAL DEDUCTIONS</span>
                    <span class="text-danger fw-bold">₱ <?= number_format(round($detail['total_deductions']), 2) ?></span>
                </div>
            </div>
        </div>

        <!-- Net Pay -->
        <div class="p-3 text-center" style="background:#f8fafc;border-radius:0 0 8px 8px;">
            <div class="text-muted small mb-1">NET PAY</div>
            <div class="fw-bold" style="font-size:2rem;color:var(--primary);">
                ₱ <?= number_format(round($detail['net_pay']), 2) ?>
            </div>

            <div class="row mt-3 border-top pt-2">
                <div class="col text-start">
                    <div class="text-muted small">SSS No.</div>
                    <div class="font-monospace small"><?= esc($detail['sss_number'] ?? '—') ?></div>
                </div>
                <div class="col text-center">
                    <div class="text-muted small">PhilHealth No.</div>
                    <div class="font-monospace small"><?= esc($detail['philhealth_number'] ?? '—') ?></div>
                </div>
                <div class="col text-end">
                    <div class="text-muted small">Pag-IBIG No.</div>
                    <div class="font-monospace small"><?= esc($detail['pagibig_number'] ?? '—') ?></div>
                </div>
            </div>
        </div>
    </div><!-- /.payslip-wrapper -->

    <div class="text-center text-muted small mt-3 no-print">
        This payslip is system-generated. Printed: <?= date('M j, Y g:i a') ?>
    </div>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous"/>
</body>
</html>
