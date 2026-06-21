<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Bulk Payslips – <?= esc($title) ?></title>
    <?php $_logoUrl = setting_logo_url();
          $_companyName = setting('company_name', 'PayrollPH'); 
          if ($_logoUrl): ?><link rel="icon" type="image/png" href="<?= esc($_logoUrl) ?>"><?php endif; ?>
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.min.css') ?>"/>
    <link rel="stylesheet" href="<?= base_url('assets/css/custom.css') ?>"/>
    <style>
        /* ── Variables ── */
        :root { --primary: #2563eb; }

        /* ── Page setup ── */
        @page { size: A4 portrait; margin: 3mm 2mm; }

        /* ── Payslip grid ── */
        .payslip-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 0.6rem;
        }

        /* ── Single payslip card ── */
        .payslip-wrapper {
            border: 1px solid #d1d5db;
            border-radius: 5px;
            overflow: hidden;
            background: #fff;
            font-family: Arial, sans-serif;
            font-size: 8.5pt;
            display: flex;
            flex-direction: column;
        }

        /* ── Blue header (matches UI) ── */
        .ps-header {
            background: #2563eb !important;
            color: #fff !important;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.25rem 0.4rem;
        }
        .ps-header-left { display: flex; align-items: center; gap: 0.25rem; }
        .ps-logo        { height: 20px; width: auto; object-fit: contain; flex-shrink: 0; }
        .ps-company     { font-weight: 700; font-size: 9pt; }
        .ps-header-right{ text-align: right; }
        .ps-label       { font-weight: 700; font-size: 8pt; display: block; letter-spacing:.05em; }
        .ps-period      { font-size: 7.5pt; opacity: .85; display: block; }

        /* ── Info rows ── */
        .ps-info        { border-bottom: 1px solid #e5e7eb; padding: 0.15rem 0.3rem; }
        .ps-info-table  { width: 100%; border-collapse: collapse; }
        .ps-info-table td { padding: 0.5px 2px; line-height: 1.3; font-size: 8pt; vertical-align: top; }
        .ps-k           { color: #6b7280; width: 22%; white-space: nowrap; }
        .ps-v           { width: 28%; }

        /* ── Earnings / Deductions ── */
        .ps-body        { display: flex; flex-direction: column; flex: 1; border-bottom: 1px solid #e5e7eb; }
        .ps-col-left    { width: 100%; padding: 0.15rem 0.25rem; border-bottom: 1px solid #e5e7eb; }
        .ps-col-right   { width: 100%; padding: 0.15rem 0.25rem; }
        .ps-section-title { font-size: 7pt; font-weight: 700; text-transform: uppercase;
                            letter-spacing: .05em; color: #6b7280; margin-bottom: 0.1rem; }
        .ps-row         { display: flex; justify-content: space-between; font-size: 8pt;
                          line-height: 1.35; }
        .ps-row span:last-child { font-size: 10pt; }
        .ps-total       { font-weight: 700; border-top: 0.5px solid #d1d5db;
                          margin-top: 0.1rem; padding-top: 0.1rem; }

        /* ── Net Pay footer ── */
        .ps-net         { display: flex; align-items: center; justify-content: space-between;
                          padding: 0.15rem 0.3rem; background: #f8fafc; }
        .ps-net-label   { font-size: 10pt; color: #6b7280; font-weight: 600; text-transform: uppercase; }
        .ps-net-value   { font-weight: 700; font-size: 14pt; color: var(--primary); }

        /* ── Utilities ── */
        .text-success { color: #16a34a; }
        .text-danger  { color: #dc2626; font-size: 10pt; }
        .fw-semibold  { font-weight: 600; }
        .font-monospace { font-family: monospace; }

        /* ── Screen only ── */
        @media screen {
            body { background: #f1f5f9; }
            .container-fluid { padding: 1rem; }
            .payslip-grid { gap: 1rem; }
            .ps-header {
                background: #2563eb !important;
                color: #fff !important;
            }
        }

        /* ── Print ── */
        @media print {
            .no-print { display: none !important; }
            html, body, .container-fluid, .py-4 {
                margin: 0 !important; padding: 0 !important;
                max-width: 100% !important; background: white !important;
            }
            .payslip-grid { gap: 0; }
            .payslip-wrapper {
                border-radius: 0;
                border: 0.5px solid #bbb;
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body style="background:#f1f5f9;">

<?php $_companyName = setting('company_name', 'PayrollPH'); ?>
<?php $_logoUrl = setting_logo_url(); ?>
<?php $_tagline = setting('company_tagline', 'Payroll Management System'); ?>

<div class="container-fluid py-4">
    <div class="d-flex align-items-center justify-content-between mb-3 no-print flex-wrap gap-2">
        <h5 class="mb-0"><?= esc($title) ?> (<?= count($details) ?> payslips)</h5>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <select id="branch-filter" class="form-select form-select-sm" style="width:180px;" onchange="filterByBranch(this.value)">
                <option value="">All Branches</option>
                <?php foreach ($branches as $b): ?>
                <option value="<?= esc($b['id']) ?>"><?= esc($b['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" id="payslip-search" class="form-control form-control-sm" placeholder="Search employee…" style="width:200px;" oninput="filterPayslips()">
            <button onclick="window.print()" class="btn btn-primary btn-sm">
                <i class="fa fa-print me-1"></i>Print All
            </button>
            <a href="<?= site_url('payroll/view/' . $payroll['id']) ?>" class="btn btn-outline-secondary btn-sm">← Back</a>
        </div>
    </div>

    <div class="payslip-grid">
        <?php foreach ($details as $d): ?>
        <?php
            $dAbsent   = (float)($d['absent_deduction'] ?? 0);
            $dAbsDays  = (float)($d['absent_days'] ?? 0);
            $dHalfDays = (float)($d['half_days'] ?? 0);
            // Legacy fallback: only use absent_days — half_days may include paid Sundays
            // which cannot be distinguished without re-querying attendance.
            if ($dAbsent == 0 && $dAbsDays > 0) {
                $dDailySal = (float)$d['daily_rate'];
                $dAbsent   = round($dDailySal * $dAbsDays, 2);
            }
            // Deductable half-days = derived from stored deduction minus whole-day absents
            // absent_deduction = daily_rate × (absent_days + deductable_half_days × 0.5)
            // deductable_half_days = ((absent_deduction / daily_rate) - absent_days) / 0.5
            $dDeductUnits     = (float)$d['daily_rate'] > 0 && $dAbsent > 0
                                ? round($dAbsent / (float)$d['daily_rate'], 4)
                                : $dAbsDays;
            $dDeductHalfDays  = max(0, round(($dDeductUnits - $dAbsDays) / 0.5));
            // Days worked calculation: present + absent + deductable_half
            // daysWorked stores: whole_days + (half_days * 0.5)
            // whole_days = daysWorked - (half_days * 0.5)
            $dWholeDays       = (float)$d['days_worked'] - ((float)$d['half_days'] * 0.5);
            $dTotalDaysAcct   = $dWholeDays + $dAbsDays + ($dDeductHalfDays * 0.5);
        ?>
        <div class="payslip-wrapper" data-name="<?= esc(strtolower($d['full_name'])) ?>" data-branch="<?= esc($d['branch_id'] ?? '') ?>">

            <!-- Blue header -->
            <div class="ps-header">
                <div class="ps-header-left">
                    <?php if ($_logoUrl): ?><img src="<?= esc($_logoUrl) ?>" alt="Logo" class="ps-logo"><?php endif; ?>
                    <span class="ps-company"><?= esc($_companyName) ?></span>
                </div>
                <div class="ps-header-right">
                    <span class="ps-label">PAYSLIP</span>
                    <span class="ps-period"><?= \App\Models\PayrollModel::periodLabel($payroll) ?></span>
                </div>
            </div>

            <!-- Employee + pay info: vertical list -->
            <div class="ps-info">
                <table class="ps-info-table">
                    <tr>
                        <td class="ps-k">Employee</td>
                        <td class="ps-v fw-semibold"><?= esc($d['full_name']) ?></td>
                    </tr>
                    <tr>
                        <td class="ps-k">Position</td>
                        <td class="ps-v"><?= esc($d['position']) ?></td>
                    </tr>
                    <tr>
                        <td class="ps-k">Period</td>
                        <td class="ps-v"><?= date('M j', strtotime($payroll['period_start'])) ?>–<?= date('M j, Y', strtotime($payroll['period_end'])) ?></td>
                    </tr>
                    <tr>
                        <td class="ps-k">Salary</td>
                        <td class="ps-v fw-semibold">₱ <?= number_format(round($d['employee_salary']), 2) ?></td>
                    </tr>
                  <!--  <tr>
                        <td class="ps-k">Days Worked</td>
                        <td class="ps-v"><strong><?= rtrim(rtrim(number_format($dTotalDaysAcct, 2), '0'), '.') ?></strong></td>
                    </tr>-->
                </table>
            </div>

            <!-- Earnings | Deductions -->
            <div class="ps-body">
                <div class="ps-col-left">
                    <div class="ps-section-title">Earnings</div>
                    <div class="ps-row"><span>Basic Pay</span><span>₱ <?= number_format(round($d['basic_pay']), 2) ?></span></div>
                    <div class="ps-row ps-total"><span>GROSS</span><span class="text-success">₱ <?= number_format(round($d['gross_pay']), 2) ?></span></div>
                </div>
                <div class="ps-col-right">
                    <div class="ps-section-title">Deductions</div>
                    <?php
                        $dTotalDeductUnits = $dAbsDays + ($dDeductHalfDays * 0.5);
                    ?>
                    <?php if ($dTotalDeductUnits > 0): ?>
                        <div class="ps-row"><span class="text-danger">Absent: <?= rtrim(rtrim(number_format($dTotalDeductUnits, 2), '0'), '.') ?></span><span class="text-danger">₱ <?= number_format($dAbsent, 2) ?></span></div>
                    <?php endif; ?>
                    <?php if (($d['sss_deduction'] ?? 0) > 0): ?><div class="ps-row"><span>SSS</span><span class="text-danger">₱ <?= number_format(round($d['sss_deduction']), 2) ?></span></div><?php endif; ?>
                    <?php if (($d['philhealth_deduction'] ?? 0) > 0): ?><div class="ps-row"><span>PhilHealth</span><span class="text-danger">₱ <?= number_format(round($d['philhealth_deduction']), 2) ?></span></div><?php endif; ?>
                    <?php if (($d['pagibig_deduction'] ?? 0) > 0): ?><div class="ps-row"><span>Pag-IBIG</span><span class="text-danger">₱ <?= number_format(round($d['pagibig_deduction']), 2) ?></span></div><?php endif; ?>
                    <?php if (($d['other_deductions'] ?? 0) > 0 || ($d['pharmacy_deduction'] ?? 0) > 0): ?>
                        <?php
                            $empDeds      = $empDedsMap[$d['employee_id']] ?? [];
                            $pharmDedList = array_filter($empDeds, fn($e) => ($e['type'] ?? '') === 'Pharmacy');
                            $otherDedList = array_filter($empDeds, fn($e) => ($e['type'] ?? '') !== 'Pharmacy');
                            $pharmTotal   = array_sum(array_column($pharmDedList, 'amount_deducted'));
                        ?>
                        <?php if ($pharmTotal > 0): ?>
                            <?php foreach ($pharmDedList as $ed): ?>
                    <div class="ps-row"><span><?= esc($ed['description'] ?? $ed['type'] ?? 'Pharmacy') ?></span><span class="text-danger">₱ <?= number_format(round($ed['amount_deducted']), 2) ?></span></div>
                            <?php endforeach; ?>
                    <div class="ps-row text-muted small"><span>Pharmacy total</span><span>₱ <?= number_format(round($pharmTotal), 2) ?></span></div>
                        <?php elseif (($d['pharmacy_deduction'] ?? 0) > 0): ?>
                    <div class="ps-row"><span>Pharmacy</span><span class="text-danger">₱ <?= number_format(round($d['pharmacy_deduction']), 2) ?></span></div>
                        <?php endif; ?>
                        <?php if ($otherDedList): foreach ($otherDedList as $ed): ?>
                    <div class="ps-row"><span><?= esc($ed['description'] ?? $ed['type'] ?? 'Other Deduction') ?></span><span class="text-danger">₱ <?= number_format(round($ed['amount_deducted']), 2) ?></span></div>
                        <?php endforeach; elseif (($d['other_deductions'] ?? 0) > 0): ?>
                    <div class="ps-row"><span>Other Ded.</span><span class="text-danger">₱ <?= number_format(round($d['other_deductions']), 2) ?></span></div>
                        <?php endif; ?>
                    <?php endif; ?>
                    <div class="ps-row ps-total"><span>DED.</span><span class="text-danger">₱ <?= number_format(round($d['total_deductions']), 2) ?></span></div>
                </div>
            </div>

            <!-- Net Pay -->
            <div class="ps-net">
                <span class="ps-net-label">NET PAY</span>
                <span class="ps-net-value">₱ <?= number_format(round($d['net_pay']), 2) ?></span>
            </div>

        </div><!-- /payslip-wrapper -->
        <?php endforeach; ?>
    </div>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous"/>
<script>
var activeFilter = { branch: '', name: '' };

function filterPayslips() {
    activeFilter.name = (document.getElementById('payslip-search').value || '').trim().toLowerCase();
    applyFilters();
}

function filterByBranch(branchId) {
    activeFilter.branch = branchId.toString();
    applyFilters();
}

function applyFilters() {
    document.querySelectorAll('.payslip-wrapper').forEach(function(el) {
        var nameMatch  = !activeFilter.name   || (el.dataset.name   || '').includes(activeFilter.name);
        var branchMatch = !activeFilter.branch || (el.dataset.branch || '') === activeFilter.branch;
        el.style.display = (nameMatch && branchMatch) ? '' : 'none';
    });
}
</script>
</body>
</html>
