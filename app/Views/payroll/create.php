<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="<?= site_url('payroll') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fa fa-arrow-left"></i>
    </a>
    <h5 class="mb-0 fw-semibold">Generate Payroll</h5>
</div>

<div class="card" style="max-width:500px;">
    <div class="card-body">
        <p class="text-muted small mb-4">
            Select the payroll period and cutoff. The system will automatically compute pay
            based on attendance records, configuration, and deduction rates.
        </p>

        <form action="<?= site_url('payroll/generate') ?>" method="POST" novalidate>
            <?= csrf_field() ?>

            <div class="mb-4">
                <label class="form-label fw-medium">Payroll Month <span class="text-danger">*</span></label>
                <input type="month" name="payroll_month" id="payroll_month" class="form-control"
                       value="<?= esc($selectedMonth) ?>" required
                       onchange="window.location.href='<?= site_url('payroll/create') ?>?payroll_month='+this.value"/>
                <div class="form-text">Select the year and month for this payroll run.</div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-medium">Cut-off Period <span class="text-danger">*</span></label>
                <div class="d-flex gap-3">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="cutoff" id="c1" value="1" required <?= isset($disableFirst) && $disableFirst ? 'disabled' : '' ?> <?= (!isset($disableFirst) || !$disableFirst) ? 'checked' : '' ?>/>
                        <label class="form-check-label" for="c1">
                            <span class="fw-semibold">1st Cutoff</span><br/>
                            <span class="text-muted small">1 – 15 of the month</span>
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="cutoff" id="c2" value="2" required <?= isset($disableSecond) && $disableSecond ? 'disabled' : '' ?> <?= (isset($disableFirst) && $disableFirst && isset($disableSecond) && !$disableSecond) ? 'checked' : '' ?>/>
                        <label class="form-check-label" for="c2">
                            <span class="fw-semibold">2nd Cutoff</span><br/>
                            <span class="text-muted small">16 – end of the month</span>
                        </label>
                    </div>
                </div>
            </div>

            <?php if ($firstCutoffFinalized): ?>
            <div class="alert alert-warning py-2 small">
                <i class="fa fa-circle-info me-2"></i>
                Deductions (SSS, PhilHealth, Pag-IBIG) will be deducted in this cut-off!
            </div>
            <?php endif; ?>

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-calculator me-1"></i>Generate Payroll
                </button>
                <a href="<?= site_url('payroll') ?>" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>
