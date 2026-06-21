<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$label = \App\Models\PayrollModel::periodLabel($payroll);
$cutoffLabel = (int)$payroll['cutoff'] === 1 ? '1st Cutoff' : '2nd Cutoff';
$branchLabel  = '';
if ($selBranch) {
    foreach ($branches as $_b) {
        if ((int)$_b['id'] === (int)$selBranch) { $branchLabel = $_b['name']; break; }
    }
}
?>

<!-- Header -->
<div class="d-flex align-items-center gap-2 mb-3 no-print">
    <a href="<?= site_url('payroll') . '?' . http_build_query(array_filter(['year' => $selYear, 'month' => $selMonth, 'branch_id' => $selBranch ?: ''], fn($v) => $v !== '' && $v !== 0)) ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fa fa-arrow-left"></i>
    </a>
    <div class="flex-fill">
        <h5 class="mb-0 fw-semibold"><?= esc($label) ?></h5>
        <small class="text-muted"><?= date('M j', strtotime($payroll['period_start'])) ?> – <?= date('M j, Y', strtotime($payroll['period_end'])) ?></small>
    </div>
    <span class="badge fs-6 <?= $payroll['status'] === 'finalized' ? 'badge-final' : 'badge-draft' ?>">
        <?= ucfirst($payroll['status']) ?>
    </span>
</div>

<!-- Summary Totals -->
<div class="row g-3 mb-3 no-print">
    <div class="col-sm-4">
        <div class="card p-3 text-center">
            <div class="text-muted small mb-1">Total Gross Pay</div>
            <div class="fw-bold fs-4 text-success" id="sum-gross">₱ <?= number_format(round($filteredGross), 2) ?></div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card p-3 text-center">
            <div class="text-muted small mb-1">Total Deductions</div>
            <div class="fw-bold fs-4 text-danger" id="sum-ded">₱ <?= number_format(round($filteredDed), 2) ?></div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card p-3 text-center">
            <div class="text-muted small mb-1">Total Net Pay</div>
            <div class="fw-bold fs-4 text-primary" id="sum-net">₱ <?= number_format(round($filteredNet), 2) ?></div>
        </div>
    </div>
</div>

<!-- Action Buttons -->
<div class="d-flex gap-2 mb-3 no-print">
    <?php if ($payroll['status'] === 'draft'): ?>
    <?php if (can_do('payroll', 'edit')): ?>
    <a href="<?= site_url('payroll/finalize/' . $payroll['id']) ?>"
       class="btn btn-success btn-sm"
       data-confirm="Finalize this payroll? This action cannot be undone.">
        <i class="fa fa-check-double me-1"></i>Finalize
    </a>
    <a href="<?= site_url('payroll/recalculate/' . $payroll['id']) ?>"
       class="btn btn-warning btn-sm text-dark">
        <i class="fa fa-rotate me-1"></i>Recalculate
    </a>
    <?php endif; ?>
    <?php if (can_do('payroll', 'delete')): ?>
    <a href="<?= site_url('payroll/delete/' . $payroll['id']) ?>"
       class="btn btn-outline-danger btn-sm"
       data-confirm="Delete this draft payroll?">
        <i class="fa fa-trash me-1"></i>Delete
    </a>
    <?php endif; ?>
    <?php endif; ?>
    <a href="<?= site_url('payslip/bulk/' . $payroll['id']) . '?' . http_build_query(array_filter(['year' => $selYear, 'month' => $selMonth, 'branch_id' => $selBranch ?: ''], fn($v) => $v !== '' && $v !== 0)) ?>"
       class="btn btn-outline-secondary btn-sm" target="_blank">
        <i class="fa fa-file-invoice me-1"></i>Bulk Payslips
    </a>
    <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print()">
        <i class="fa fa-print me-1"></i>Print
    </button>
    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="printReceivingCopy()">
        <i class="fa fa-file-signature me-1"></i>Print Receiving Copy
    </button>
    <a href="<?= site_url('payroll/deduction-report/' . $payroll['id']) ?>" target="_blank"
       class="btn btn-outline-danger btn-sm">
        <i class="fa fa-list-check me-1"></i>Print Deductions
    </a>
</div>

<!-- Print-only header (hidden on screen) -->
<div class="d-none d-print-block mb-3" style="border-bottom:2px solid #000;padding-bottom:.6rem;">
    <?php $_companyName = setting('company_name', 'PayrollPH'); ?>
    <div style="font-size:1.3rem;font-weight:700;"><?= esc($_companyName) ?></div>
    <div style="font-size:1.1rem;font-weight:600;margin-top:.15rem;">Payroll Report &mdash; <?= esc($label) ?></div>
    <div style="font-size:.9rem;color:#555;margin-top:.1rem;">
        <?= $cutoffLabel ?> &bull;
        Period: <?= date('M j, Y', strtotime($payroll['period_start'])) ?> &ndash; <?= date('M j, Y', strtotime($payroll['period_end'])) ?>
        <?php if ($branchLabel): ?> &bull; Branch: <?= esc($branchLabel) ?><?php endif; ?>
    </div>
    <div style="font-size:.85rem;color:#555;margin-top:.1rem;">
        <?= count($details) ?> employee(s) &bull; <?= $payroll['working_days'] ?> working days &bull;
        Gross: ₱ <?= number_format(round($filteredGross), 2) ?> &bull;
        Deductions: ₱ <?= number_format(round($filteredDed), 2) ?> &bull;
        Net Pay: ₱ <?= number_format(round($filteredNet), 2) ?>
    </div>
</div>

<!-- Employee Details Table -->
<div class="card no-print">
    <div class="card-header d-flex align-items-center gap-2 flex-wrap no-print">
        <div>
            <i class="fa fa-list me-2"></i>Employee Payroll Details
            <span class="text-muted small ms-2" id="visible-count"><?= count($details) ?> employee(s)</span>
        </div>
        <div class="ms-auto d-flex align-items-center gap-2 flex-wrap">
            <span class="small fw-semibold text-muted">Employee:</span>
            <input type="search" id="empSearch" class="form-control form-control-sm"
                   placeholder="Search name / code…" style="width:190px;">
            <span class="small fw-semibold text-muted">Dept:</span>
            <select id="deptFilter" class="form-select form-select-sm" style="width:150px">
                <option value="">All Departments</option>
                <?php
                $depts = array_unique(array_filter(array_column($details, 'department')));
                sort($depts);
                foreach ($depts as $dept): ?>
                <option value="<?= esc($dept) ?>"><?= esc($dept) ?></option>
                <?php endforeach; ?>
            </select>
            <span class="small fw-semibold text-muted">Branch:</span>
            <select id="branchFilter" class="form-select form-select-sm" style="width:150px">
                <option value="">All Branches</option>
                <?php foreach ($branches as $b): ?>
                <option value="<?= $b['id'] ?>" <?= (int)$selBranch === (int)$b['id'] ? 'selected' : '' ?>>
                    <?= esc($b['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead>
                    <tr>
                        <th style="white-space:nowrap;">Employee</th>
                        <th class="text-center">Days Worked<br/><span class="fw-normal text-muted" style="font-size:.75em;">/ Daily Rate</span></th>
                        <th class="text-end">Basic Pay</th>
                        <th class="text-end">Gross</th>
                        <th class="text-end" id="th-absent" style="cursor:pointer;user-select:none;white-space:nowrap;" onclick="sortByAbsent()" title="Click to sort by absent">Absent <span id="absent-sort-icon" class="text-muted" style="font-size:.75em;">&#8645;</span></th>
                        <th class="text-end">Pharmacy</th>
                        <?php if ((int)$payroll['cutoff'] === 2): ?>
                        <th class="text-end">SSS</th>
                        <th class="text-end">PhilHealth</th>
                        <th class="text-end">Pag-IBIG</th>
                        <?php endif; ?>
                        <th class="text-end">Other</th>
                        <th class="text-end">Total Ded.</th>
                        <th class="text-end fw-bold">Net Pay</th>
                        <th class="no-print">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($details)): ?>
                <tr><td colspan="<?= (int)$payroll['cutoff'] === 2 ? 13 : 10 ?>" class="text-center text-muted py-4">No details found.</td></tr>
                <?php else: ?>
                    <?php foreach ($details as $d): ?>
                    <?php
                        $absentDed = (float)($d['absent_deduction'] ?? 0);
                        // Legacy: old records stored absent in benefits_deduction
                        if ($absentDed == 0 && ($d['benefits_deduction'] ?? 0) > 0) {
                            $absentDed = (float)$d['benefits_deduction'];
                        }
                    ?>
                    <tr id="pd-row-<?= $d['id'] ?>" data-name="<?= esc(strtolower($d['full_name'] . ' ' . $d['employee_code'])) ?>" data-dept="<?= esc($d['department'] ?? '') ?>" data-gross="<?= (float)$d['gross_pay'] ?>" data-ded="<?= (float)$d['total_deductions'] ?>" data-net="<?= (float)$d['net_pay'] ?>" data-absent="<?= $absentDed ?>">
                        <td style="white-space:nowrap;">
                            <div class="fw-semibold"><?= esc($d['full_name']) ?></div>
                            <div class="text-muted small"><?= esc($d['branch_name'] ?? '') ?></div> 
                        </td>
                        <td class="text-center">
                            <?php
                                $absUnits = (float)$d['absent_days'];
                                if ((float)$d['daily_rate'] > 0 && (float)($d['absent_deduction'] ?? 0) > 0) {
                                    $absUnits = round((float)$d['absent_deduction'] / (float)$d['daily_rate'], 2);
                                }
                            ?>
                            <div style="font-size:.8rem;">
                                <?php if ($absUnits > 0): ?>
                                    <span class="text-danger fw-semibold">Absent: <?= rtrim(rtrim(number_format($absUnits, 2), '0'), '.') ?></span><br>
                                <?php endif; ?>
                                <span class="text-muted" style="font-size:.72rem;">&#8369;<?= number_format((float)$d['daily_rate'], 2) ?>/day</span>
                            </div>
                        </td>
                        <td class="text-end" style="white-space:nowrap;">&#8369; <?= number_format(round($d['basic_pay']), 2) ?></td>
                        <td class="text-end text-success" style="white-space:nowrap;" data-col="gross">&#8369; <?= number_format(round($d['gross_pay']), 2) ?></td>
                        <td class="text-end text-danger small" style="white-space:nowrap;" data-col="absent"><?= $absentDed > 0 ? '&#8369; ' . number_format($absentDed, 2) : '&mdash;' ?></td>
                        <td class="text-end text-danger small" style="white-space:nowrap;" data-col="pharmacy"><?= ($d['pharmacy_deduction'] ?? 0) > 0 ? '&#8369; ' . number_format(round($d['pharmacy_deduction']), 2) : '&mdash;' ?></td>
                        <?php if ((int)$payroll['cutoff'] === 2): ?>
                        <td class="text-end text-danger small" style="white-space:nowrap;" data-col="sss"><?= $d['sss_deduction'] > 0 ? '&#8369; ' . number_format(round($d['sss_deduction']), 2) : '&mdash;' ?></td>
                        <td class="text-end text-danger small" style="white-space:nowrap;" data-col="philhealth"><?= $d['philhealth_deduction'] > 0 ? '&#8369; ' . number_format(round($d['philhealth_deduction']), 2) : '&mdash;' ?></td>
                        <td class="text-end text-danger small" style="white-space:nowrap;" data-col="pagibig"><?= $d['pagibig_deduction'] > 0 ? '&#8369; ' . number_format(round($d['pagibig_deduction']), 2) : '&mdash;' ?></td>
                        <?php endif; ?>
                        <td class="text-end text-danger small" style="white-space:nowrap;" data-col="other"><?= $d['other_deductions'] > 0 ? '&#8369; ' . number_format(round($d['other_deductions']), 2) : '&mdash;' ?></td>
                        <td class="text-end text-danger fw-semibold" style="white-space:nowrap;" data-col="total-ded">&#8369; <?= number_format(round($d['total_deductions']), 2) ?></td>
                        <td class="text-end fw-bold text-primary" style="white-space:nowrap;" data-col="net">&#8369; <?= number_format(round($d['net_pay']), 2) ?></td>
                        <td class="no-print">
                            <div class="d-flex gap-1">
                            <a href="<?= site_url('payslip/view/' . $d['id']) . '?' . http_build_query(array_filter(['year' => $selYear, 'month' => $selMonth, 'branch_id' => $selBranch ?: ''], fn($v) => $v !== '' && $v !== 0)) ?>"
                               class="btn btn-sm btn-outline-info" target="_blank" title="View Payslip">
                                <i class="fa fa-receipt"></i>
                            </a>
                            <a href="<?= site_url('attendance/view/' . $d['employee_id'] . '?month=' . date('Y-m', strtotime($payroll['period_start']))) ?>"
                               class="btn btn-sm btn-outline-secondary" target="_blank" title="View Attendance">
                                <i class="fa fa-eye"></i>
                            </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="table-dark fw-bold">
                        <td colspan="3">Totals</td>
                        <td class="text-end" id="foot-gross">₱ <?= number_format(round($filteredGross), 2) ?></td>
                        <?php if ((int)$payroll['cutoff'] === 2): ?>
                        <td class="text-end" colspan="6"></td>
                        <?php else: ?>
                        <td class="text-end" colspan="3"></td>
                        <?php endif; ?>
                        <td class="text-end" id="foot-ded">₱ <?= number_format(round($filteredDed), 2) ?></td>
                        <td class="text-end" id="foot-net">₱ <?= number_format(round($filteredNet), 2) ?></td>
                        <td class="no-print"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Print-only: separate table per branch -->
<div class="d-none d-print-block">
    <?php
    $printBranchGroups = [];
    foreach ($details as $_pd) {
        $_pbn = $_pd['branch_name'] ?? 'No Branch';
        $printBranchGroups[$_pbn][] = $_pd;
    }
    ksort($printBranchGroups);
    $printBranchKeys = array_keys($printBranchGroups);
    ?>
    <?php foreach ($printBranchGroups as $printBranch => $printRows): ?>
    <?php $isFirstBranch = ($printBranch === $printBranchKeys[0]); ?>
    <div style="margin-bottom:1.8rem;<?= $isFirstBranch ? '' : 'page-break-before:always;' ?>">
        <div style="font-size:.95rem;font-weight:700;border-bottom:2px solid #000;padding-bottom:.2rem;margin-bottom:.4rem;text-transform:uppercase;letter-spacing:.04em;">
            <?= esc($printBranch) ?>
        </div>
        <table style="width:100%;border-collapse:collapse;font-size:.72rem;">
            <thead>
                <tr style="border-bottom:1px solid #999;">
                    <th style="text-align:left;padding:2px 4px;">Employee</th>
                    <th style="text-align:center;padding:2px 4px;">Days</th>
                    <th style="text-align:right;padding:2px 4px;">Basic Pay</th>
                    <th style="text-align:right;padding:2px 4px;">Gross</th>
                    <th style="text-align:right;padding:2px 4px;">Absent</th>
                    <th style="text-align:right;padding:2px 4px;">Pharmacy</th>
                    <?php if ((int)$payroll['cutoff'] === 2): ?>
                    <th style="text-align:right;padding:2px 4px;">SSS</th>
                    <th style="text-align:right;padding:2px 4px;">PhilHealth</th>
                    <th style="text-align:right;padding:2px 4px;">Pag-IBIG</th>
                    <?php endif; ?>
                    <th style="text-align:right;padding:2px 4px;">Other</th>
                    <th style="text-align:right;padding:2px 4px;">Total Ded.</th>
                    <th style="text-align:right;padding:2px 4px;font-weight:700;">Net Pay</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($printRows as $_pr): ?>
                <?php
                    $_prAbsentDed = (float)($_pr['absent_deduction'] ?? 0);
                    if ($_prAbsentDed == 0 && ($_pr['benefits_deduction'] ?? 0) > 0) {
                        $_prAbsentDed = (float)$_pr['benefits_deduction'];
                    }
                ?>
                <tr style="border-bottom:0.5px solid #e0e0e0;">
                    <td style="padding:2px 4px;">
                        <div style="font-weight:600;"><?= esc($_pr['full_name']) ?></div>
                        <div style="font-size:.62rem;color:#555;"><?= esc($_pr['position'] ?? '') ?></div>
                    </td>
                    <td style="text-align:center;padding:2px 4px;"><?= (float)$_pr['days_worked'] ?> / <?= $_pr['working_days'] ?></td>
                    <td style="text-align:right;padding:2px 4px;">&#8369; <?= number_format(round($_pr['basic_pay']), 2) ?></td>
                    <td style="text-align:right;padding:2px 4px;">&#8369; <?= number_format(round($_pr['gross_pay']), 2) ?></td>
                    <td style="text-align:right;padding:2px 4px;"><?= $_prAbsentDed > 0 ? '&#8369; ' . number_format($_prAbsentDed, 2) : '&mdash;' ?></td>
                    <td style="text-align:right;padding:2px 4px;"><?= ($_pr['pharmacy_deduction'] ?? 0) > 0 ? '&#8369; ' . number_format(round($_pr['pharmacy_deduction']), 2) : '&mdash;' ?></td>
                    <?php if ((int)$payroll['cutoff'] === 2): ?>
                    <td style="text-align:right;padding:2px 4px;"><?= $_pr['sss_deduction'] > 0 ? '&#8369; ' . number_format(round($_pr['sss_deduction']), 2) : '&mdash;' ?></td>
                    <td style="text-align:right;padding:2px 4px;"><?= $_pr['philhealth_deduction'] > 0 ? '&#8369; ' . number_format(round($_pr['philhealth_deduction']), 2) : '&mdash;' ?></td>
                    <td style="text-align:right;padding:2px 4px;"><?= $_pr['pagibig_deduction'] > 0 ? '&#8369; ' . number_format(round($_pr['pagibig_deduction']), 2) : '&mdash;' ?></td>
                    <?php endif; ?>
                    <td style="text-align:right;padding:2px 4px;"><?= $_pr['other_deductions'] > 0 ? '&#8369; ' . number_format(round($_pr['other_deductions']), 2) : '&mdash;' ?></td>
                    <td style="text-align:right;padding:2px 4px;">&#8369; <?= number_format(round($_pr['total_deductions']), 2) ?></td>
                    <td style="text-align:right;padding:2px 4px;font-weight:700;color:#1d4ed8;">&#8369; <?= number_format(round($_pr['net_pay']), 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="border-top:2px solid #000;font-weight:700;background:#f5f5f5;">
                    <td colspan="3" style="padding:3px 4px;">Branch Total (<?= count($printRows) ?> employee<?= count($printRows) !== 1 ? 's' : '' ?>)</td>
                    <td style="text-align:right;padding:3px 4px;">&#8369; <?= number_format(round(array_sum(array_column($printRows, 'gross_pay'))), 2) ?></td>
                    <td colspan="<?= (int)$payroll['cutoff'] === 2 ? 6 : 3 ?>" style="padding:3px 4px;"></td>
                    <td style="text-align:right;padding:3px 4px;">&#8369; <?= number_format(round(array_sum(array_column($printRows, 'total_deductions'))), 2) ?></td>
                    <td style="text-align:right;padding:3px 4px;color:#1d4ed8;">&#8369; <?= number_format(round(array_sum(array_column($printRows, 'net_pay'))), 2) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php endforeach; ?>
</div>

<?php if ($payroll['status'] === 'draft'): ?>
<script>
(function () {
    const BASE_POLL   = '<?= site_url('payroll/poll/' . $payroll['id']) ?>';
    const SEL_BRANCH  = '<?= (int)$selBranch ?>';
    const POLL_URL    = BASE_POLL + (SEL_BRANCH ? '?branch_id=' + SEL_BRANCH : '');
    const isCutoff2   = <?= (int)$payroll['cutoff'] === 2 ? 'true' : 'false' ?>;
    let   pollHash    = '<?= round($filteredGross) ?>,<?= round($filteredDed) ?>,<?= round($filteredNet) ?>';

    const fmt = n => '₱ ' + new Intl.NumberFormat('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}).format(Math.round(n));

    function showToast(msg) {
        let container = document.getElementById('poll-toast-wrap');
        if (!container) {
            container = document.createElement('div');
            container.id = 'poll-toast-wrap';
            container.className = 'position-fixed bottom-0 end-0 p-3';
            container.style.zIndex = 9999;
            document.body.appendChild(container);
        }
        container.innerHTML =
            `<div class="toast align-items-center text-bg-info border-0 show" role="alert">
                <div class="d-flex">
                    <div class="toast-body"><i class="fa fa-rotate me-2"></i>${msg}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto"
                            onclick="this.closest('.toast').remove()"></button>
                </div>
            </div>`;
        setTimeout(() => { container.innerHTML = ''; }, 6000);
    }

    function applyData(data) {
        // Summary cards
        const setId = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = fmt(val); };
        setId('sum-gross',  data.total_gross);
        setId('sum-ded',    data.total_deductions);
        setId('sum-net',    data.total_net);
        setId('foot-gross', data.total_gross);
        setId('foot-ded',   data.total_deductions);
        setId('foot-net',   data.total_net);

        // Detail rows
        data.details.forEach(row => {
            const tr = document.getElementById('pd-row-' + row.id);
            if (!tr) return;

            const setCell = (col, val) => {
                const el = tr.querySelector('[data-col="' + col + '"]');
                if (el) el.textContent = val > 0 ? fmt(val) : '—';
            };

            const grossEl = tr.querySelector('[data-col="gross"]');
            if (grossEl) grossEl.textContent = fmt(row.gross_pay);

            setCell('absent', row.absent_deduction);
            setCell('pharmacy', row.pharmacy_deduction);
            if (isCutoff2) {
                setCell('sss',        row.sss_deduction);
                setCell('philhealth', row.philhealth_deduction);
                setCell('pagibig',    row.pagibig_deduction);
            }
            setCell('other', row.other_deductions);

            const dedEl = tr.querySelector('[data-col="total-ded"]');
            if (dedEl) dedEl.textContent = fmt(row.total_deductions);

            const netEl = tr.querySelector('[data-col="net"]');
            if (netEl) netEl.textContent = fmt(row.net_pay);
        });
    }

    setInterval(() => {
        fetch(POLL_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => {
                const newHash = Math.round(data.total_gross) + ',' + Math.round(data.total_deductions) + ',' + Math.round(data.total_net);
                if (newHash !== pollHash) {
                    pollHash = newHash;
                    applyData(data);
                    showToast('Payroll totals updated automatically.');
                }
            })
            .catch(() => {}); // silent on network errors
    }, 15000);
}());
</script>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const sel = document.getElementById('branchFilter');
    if (sel) {
        sel.addEventListener('change', function () {
            const base   = '<?= site_url('payroll/view/' . $payroll['id']) ?>';
            const year   = '<?= esc($selYear) ?>';
            const month  = '<?= esc($selMonth) ?>';
            const params = new URLSearchParams();
            if (year)       params.set('year',      year);
            if (month)      params.set('month',     month);
            if (this.value) params.set('branch_id', this.value);
            const qs = params.toString();
            location.href = qs ? base + '?' + qs : base;
        });
    }

    // --- Employee search + department filter ---
    const searchInput = document.getElementById('empSearch');
    const deptSelect  = document.getElementById('deptFilter');
    const tbody       = document.querySelector('table tbody');
    const countEl     = document.getElementById('visible-count');
    const footGross   = document.getElementById('foot-gross');
    const footDed     = document.getElementById('foot-ded');
    const footNet     = document.getElementById('foot-net');
    const sumGross    = document.getElementById('sum-gross');
    const sumDed      = document.getElementById('sum-ded');
    const sumNet      = document.getElementById('sum-net');

    const fmt = n => '\u20b1 ' + new Intl.NumberFormat('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}).format(Math.round(n));

    function applyFilters() {
        const q    = (searchInput ? searchInput.value.trim().toLowerCase() : '');
        const dept = (deptSelect  ? deptSelect.value : '');
        const rows = tbody ? Array.from(tbody.querySelectorAll('tr[data-name]')) : [];

        let visCount = 0, visGross = 0, visDed = 0, visNet = 0;

        rows.forEach(tr => {
            const nameMatch = !q    || tr.dataset.name.includes(q);
            const deptMatch = !dept || tr.dataset.dept === dept;
            const show = nameMatch && deptMatch;
            tr.style.display = show ? '' : 'none';
            if (show) {
                visCount++;
                visGross += parseFloat(tr.dataset.gross) || 0;
                visDed   += parseFloat(tr.dataset.ded)   || 0;
                visNet   += parseFloat(tr.dataset.net)   || 0;
            }
        });

        if (countEl) countEl.textContent = visCount + ' employee(s)';
        if (footGross) footGross.textContent = fmt(visGross);
        if (footDed)   footDed.textContent   = fmt(visDed);
        if (footNet)   footNet.textContent   = fmt(visNet);
        if (sumGross)  sumGross.textContent  = fmt(visGross);
        if (sumDed)    sumDed.textContent    = fmt(visDed);
        if (sumNet)    sumNet.textContent    = fmt(visNet);
    }

    if (searchInput) searchInput.addEventListener('input', applyFilters);
    if (deptSelect)  deptSelect.addEventListener('change', applyFilters);

    // --- Sort by Absent ---
    let absentSortDir = 0; // 0=original, 1=desc (with absent first), 2=asc (without absent first)
    let originalOrder = [];
    if (tbody) {
        originalOrder = Array.from(tbody.querySelectorAll('tr[data-absent]')).map(r => r);
    }
    window.sortByAbsent = function () {
        if (!tbody) return;
        absentSortDir = (absentSortDir % 2) + 1;
        const icon = document.getElementById('absent-sort-icon');
        const rows = Array.from(tbody.querySelectorAll('tr[data-absent]'));
        rows.sort((a, b) => {
            const av = parseFloat(a.dataset.absent) || 0;
            const bv = parseFloat(b.dataset.absent) || 0;
            return absentSortDir === 1 ? bv - av : av - bv;
        });
        rows.forEach(r => tbody.appendChild(r));
        if (icon) icon.innerHTML = absentSortDir === 1 ? '&#8595;' : '&#8593;';
    };
});
</script>

<?php
// Build branch → employees map for receiving copy (sorted by full_name ASC)
$_rcBranches = [];
foreach ($details as $_d) {
    $_bn = $_d['branch_name'] ?? 'No Branch';
    $_rcBranches[$_bn][] = $_d['full_name'];
}
foreach ($_rcBranches as $_bn => &$_names) {
    sort($_names);
}
unset($_names);
ksort($_rcBranches);
?>
<script>
window.printReceivingCopy = function () {
    const company  = <?= json_encode(setting('company_name', 'PayrollPH')) ?>;
    const label    = <?= json_encode($label) ?>;
    const cutoff   = <?= json_encode($cutoffLabel) ?>;
    const period   = <?= json_encode(date('M j, Y', strtotime($payroll['period_start'])) . ' – ' . date('M j, Y', strtotime($payroll['period_end']))) ?>;
    const branches = <?= json_encode($_rcBranches) ?>;

    let html = `<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8"/>
<title>Receiving Copy – ${label}</title>
<style>
  @page { size: A4 portrait; margin: 8mm 10mm; }
  * { box-sizing: border-box; }
  body { font-family: Arial, sans-serif; font-size: 9pt; margin: 0; padding: 0; }
  .doc-header h1 { font-size: 12pt; font-weight: 700; margin: 0 0 1px; }
  .doc-header .meta { font-size: 8pt; color: #444; margin: 0; }
  .columns { column-count: 2; column-gap: 8mm; }
  .branch-block { break-inside: avoid; margin-bottom: 10px; }
  .branch-title { font-size: 9.5pt; font-weight: bold; border-bottom: 1.5px solid #000;
                  padding-bottom: 2px; margin-bottom: 4px; text-transform: uppercase; letter-spacing: .03em; }
  table { width: 100%; border-collapse: collapse; }
  td { padding: 1px 2px 5px; font-size: 8.5pt; vertical-align: bottom; }
  .num { width: 18px; color: #666; white-space: nowrap; }
  .name-cell { width: 38%; font-weight: 600; white-space: nowrap; padding-right: 4px; }
  .sig-cell { }
  .sig-inner { display: flex; align-items: flex-end; gap: 4px; }
  .sig-line { flex: 1; border-bottom: 1px solid #000; min-width: 40px; }
  thead th { font-size: 7.5pt; text-transform: uppercase; letter-spacing: .05em; color: #444;
             border-bottom: 1px solid #999; padding: 1px 2px 3px; font-weight: 700; }
  thead th.th-sig { width: 45%; }
  .doc-header { column-span: all; border-bottom: 1.5px solid #000; padding-bottom: 4px; margin-bottom: 8px; }
</style>
</head>
<body>`;

    const branchEntries = Object.entries(branches);

    // Header is placed inside the columns container with column-span:all
    // so it stays glued to the top of the first branch block on the same page
    html += `<div class="columns">`;
    html += `<div class="doc-header">
  <h1>${company}</h1>
  <p class="meta">Payroll Receiving Copy &mdash; ${label} &bull; ${cutoff} &bull; Period: ${period}</p>
</div>`;
    branchEntries.forEach(([branchName, names]) => {
        html += `<div class="branch-block">
  <div class="branch-title">${branchName}</div>
  <table>
    <thead><tr>
      <th colspan="2">Employee</th>
      <th class="th-sig">Signature</th>
    </tr></thead>
    <tbody>`;
        names.forEach((name, i) => {
            html += `<tr>
      <td class="num">${i + 1}.</td>
      <td class="name-cell">${name}</td>
      <td class="sig-cell"><div class="sig-inner"><div class="sig-line"></div></div></td>
    </tr>`;
        });
        html += `</tbody></table></div>`;
    });
    html += `</div>`;

    html += `</body></html>`;

    const w = window.open('', '_blank', 'width=900,height=700');
    w.document.write(html);
    w.document.close();
    w.focus();
    w.onload = () => w.print();
};
</script>

<?= $this->endSection() ?>
