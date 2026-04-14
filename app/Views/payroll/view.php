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
<div class="card">
    <div class="card-header d-flex align-items-center gap-2 flex-wrap">
        <div>
            <i class="fa fa-list me-2"></i>Employee Payroll Details
            <span class="text-muted small ms-2"><?= count($details) ?> employee(s)</span>
        </div>
        <div class="ms-auto d-flex align-items-center gap-2 no-print">
            <label class="mb-0 small fw-semibold">Branch:</label>
            <select id="branchFilter" class="form-select form-select-sm" style="min-width:160px">
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
                        <th>Employee</th>
                        <th class="text-center">Days<br/>Worked</th>
                        <th class="text-end">Basic Pay</th>
                        <th class="text-end">Gross</th>
                        <th class="text-end">Absent</th>
                        <?php if ((int)$payroll['cutoff'] === 2): ?>
                        <th class="text-end">SSS</th>
                        <th class="text-end">PhilHealth</th>
                        <th class="text-end">Pag-IBIG</th>
                        <?php endif; ?>
                        <th class="text-end">Other</th>
                        <th class="text-end">Total Ded.</th>
                        <th class="text-end fw-bold">Net Pay</th>
                        <th>Payslip</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($details)): ?>
                <tr><td colspan="<?= (int)$payroll['cutoff'] === 2 ? 12 : 9 ?>" class="text-center text-muted py-4">No details found.</td></tr>
                <?php else: ?>
                    <?php foreach ($details as $d): ?>
                    <?php
                        $absentDed = (float)($d['absent_deduction'] ?? 0);
                        // Legacy: old records stored absent in benefits_deduction
                        if ($absentDed == 0 && ($d['benefits_deduction'] ?? 0) > 0) {
                            $absentDed = (float)$d['benefits_deduction'];
                        }
                    ?>
                    <tr id="pd-row-<?= $d['id'] ?>">
                        <td>
                            <div class="fw-semibold"><?= esc($d['full_name']) ?></div>
                            <div class="text-muted small"><?= esc($d['employee_code']) ?></div>
                        </td>
                        <td class="text-center">
                            <?= $d['days_worked'] ?>/<?= $d['working_days'] ?>
                            <div class="text-muted" style="font-size:.72rem;">
                                <?= $d['whole_days'] ?>W <?= $d['half_days'] ?>H <?= $d['absent_days'] ?>A
                            </div>
                        </td>
                        <td class="text-end">₱ <?= number_format(round($d['basic_pay']), 2) ?></td>
                        <td class="text-end text-success" data-col="gross">₱ <?= number_format(round($d['gross_pay']), 2) ?></td>
                        <td class="text-end text-danger small" data-col="absent"><?= $absentDed > 0 ? '₱ ' . number_format(round($absentDed), 2) : '—' ?></td>
                        <?php if ((int)$payroll['cutoff'] === 2): ?>
                        <td class="text-end text-danger small" data-col="sss"><?= $d['sss_deduction'] > 0 ? '₱ ' . number_format(round($d['sss_deduction']), 2) : '—' ?></td>
                        <td class="text-end text-danger small" data-col="philhealth"><?= $d['philhealth_deduction'] > 0 ? '₱ ' . number_format(round($d['philhealth_deduction']), 2) : '—' ?></td>
                        <td class="text-end text-danger small" data-col="pagibig"><?= $d['pagibig_deduction'] > 0 ? '₱ ' . number_format(round($d['pagibig_deduction']), 2) : '—' ?></td>
                        <?php endif; ?>
                        <td class="text-end text-danger small" data-col="other"><?= $d['other_deductions'] > 0 ? '₱ ' . number_format(round($d['other_deductions']), 2) : '—' ?></td>
                        <td class="text-end text-danger fw-semibold" data-col="total-ded">₱ <?= number_format(round($d['total_deductions']), 2) ?></td>
                        <td class="text-end fw-bold text-primary" data-col="net">₱ <?= number_format(round($d['net_pay']), 2) ?></td>
                        <td>
                            <a href="<?= site_url('payslip/view/' . $d['id']) . '?' . http_build_query(array_filter(['year' => $selYear, 'month' => $selMonth, 'branch_id' => $selBranch ?: ''], fn($v) => $v !== '' && $v !== 0)) ?>"
                               class="btn btn-sm btn-outline-info" target="_blank" title="View Payslip">
                                <i class="fa fa-receipt"></i>
                            </a>
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
                        <td class="text-end" colspan="5"></td>
                        <?php else: ?>
                        <td class="text-end" colspan="2"></td>
                        <?php endif; ?>
                        <td class="text-end" id="foot-ded">₱ <?= number_format(round($filteredDed), 2) ?></td>
                        <td class="text-end" id="foot-net">₱ <?= number_format(round($filteredNet), 2) ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
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
});
</script>

<?= $this->endSection() ?>
