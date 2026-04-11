<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php $label = \App\Models\PayrollModel::periodLabel($payroll); ?>

<!-- Header -->
<div class="d-flex align-items-center gap-2 mb-3">
    <a href="<?= site_url('payroll') ?>" class="btn btn-sm btn-outline-secondary">
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
<div class="row g-3 mb-3">
    <div class="col-sm-4">
        <div class="card p-3 text-center">
            <div class="text-muted small mb-1">Total Gross Pay</div>
            <div class="fw-bold fs-4 text-success">₱ <?= number_format($payroll['total_gross'], 2) ?></div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card p-3 text-center">
            <div class="text-muted small mb-1">Total Deductions</div>
            <div class="fw-bold fs-4 text-danger">₱ <?= number_format($payroll['total_deductions'], 2) ?></div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card p-3 text-center">
            <div class="text-muted small mb-1">Total Net Pay</div>
            <div class="fw-bold fs-4 text-primary">₱ <?= number_format($payroll['total_net'], 2) ?></div>
        </div>
    </div>
</div>

<!-- Action Buttons -->
<div class="d-flex gap-2 mb-3 no-print">
    <?php if ($payroll['status'] === 'draft'): ?>
    <a href="<?= site_url('payroll/finalize/' . $payroll['id']) ?>"
       class="btn btn-success btn-sm"
       data-confirm="Finalize this payroll? This action cannot be undone.">
        <i class="fa fa-check-double me-1"></i>Finalize
    </a>
    <a href="<?= site_url('payroll/recalculate/' . $payroll['id']) ?>"
       class="btn btn-warning btn-sm text-dark">
        <i class="fa fa-rotate me-1"></i>Recalculate
    </a>
    <a href="<?= site_url('payroll/delete/' . $payroll['id']) ?>"
       class="btn btn-outline-danger btn-sm"
       data-confirm="Delete this draft payroll?">
        <i class="fa fa-trash me-1"></i>Delete
    </a>
    <?php endif; ?>
    <a href="<?= site_url('payslip/bulk/' . $payroll['id']) ?>"
       class="btn btn-outline-secondary btn-sm" target="_blank">
        <i class="fa fa-file-invoice me-1"></i>Bulk Payslips
    </a>
    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#printModal">
        <i class="fa fa-print me-1"></i>Print
    </button>
</div>

<!-- Employee Details Table -->
<div class="card">
    <div class="card-header">
        <i class="fa fa-list me-2"></i>Employee Payroll Details
        <span class="text-muted small ms-2"><?= count($details) ?> employee(s) | <?= $payroll['working_days'] ?> working days</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th class="text-center">Days<br/>Worked</th>
                        <th class="text-center">OT<br/>Hours</th>
                        <th class="text-end">Basic Pay</th>
                        <th class="text-end">OT Pay</th>
                        <th class="text-end">Gross</th>
                        <th class="text-end">SSS</th>
                        <th class="text-end">PhilHealth</th>
                        <th class="text-end">Pag-IBIG</th>
                        <th class="text-end">Benefits</th>
                        <th class="text-end">Total Ded.</th>
                        <th class="text-end fw-bold">Net Pay</th>
                        <th>Payslip</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($details)): ?>
                <tr><td colspan="13" class="text-center text-muted py-4">No details found.</td></tr>
                <?php else: ?>
                    <?php foreach ($details as $d): ?>
                    <tr>
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
                        <td class="text-center"><?= $d['overtime_hours'] > 0 ? $d['overtime_hours'] : '—' ?></td>
                        <td class="text-end">₱ <?= number_format($d['basic_pay'], 2) ?></td>
                        <td class="text-end"><?= $d['overtime_pay'] > 0 ? '₱ ' . number_format($d['overtime_pay'], 2) : '—' ?></td>
                        <td class="text-end text-success">₱ <?= number_format($d['gross_pay'], 2) ?></td>
                        <td class="text-end text-danger small">₱ <?= number_format($d['sss_deduction'], 2) ?></td>
                        <td class="text-end text-danger small">₱ <?= number_format($d['philhealth_deduction'], 2) ?></td>
                        <td class="text-end text-danger small">₱ <?= number_format($d['pagibig_deduction'], 2) ?></td>
                        <td class="text-end text-danger small">₱ <?= number_format($d['benefits_deduction'] ?? 0, 2) ?></td>
                        <td class="text-end text-danger fw-semibold">₱ <?= number_format($d['total_deductions'], 2) ?></td>
                        <td class="text-end fw-bold text-primary">₱ <?= number_format($d['net_pay'], 2) ?></td>
                        <td>
                            <a href="<?= site_url('payslip/view/' . $d['id']) ?>"
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
                        <td colspan="5">Totals</td>
                        <td class="text-end">₱ <?= number_format($payroll['total_gross'], 2) ?></td>
                        <td class="text-end" colspan="4"></td>
                        <td class="text-end">₱ <?= number_format($payroll['total_deductions'], 2) ?></td>
                        <td class="text-end">₱ <?= number_format($payroll['total_net'], 2) ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Print Modal -->
<div class="modal fade" id="printModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Print Payroll Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="printForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="paperSize" class="form-label">Paper Size</label>
                        <select id="paperSize" class="form-select" required>
                            <option value="a4">A4 (8.27 x 11.69 in)</option>
                            <option value="short">Short (8.5 x 11 in)</option>
                            <option value="legal">Legal (8.5 x 14 in)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-print me-1"></i>Print
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('printForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const paperSize = document.getElementById('paperSize').value;
    const payrollId = <?= $payroll['id'] ?>;
    const printUrl = `<?= site_url('payroll/print') ?>/${payrollId}?paperSize=${paperSize}`;
    
    // Open print view in new window
    window.open(printUrl, '_blank');
    
    // Close the modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('printModal'));
    modal.hide();
});
</script>

<?= $this->endSection() ?>
