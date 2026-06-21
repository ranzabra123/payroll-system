<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-semibold">Employee Deductions</h5>
    <div class="d-flex gap-2">
        <a href="<?= site_url('deductions/summary') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fa fa-chart-bar me-1"></i>Summary Report
        </a>
        <?php if (can_do('deductions', 'add')): ?>
        <a href="<?= site_url('deductions/create') ?>" class="btn btn-primary btn-sm">
            <i class="fa fa-plus me-1"></i>Add Deduction
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3 p-2">
    <form method="get" class="d-flex flex-wrap gap-2 align-items-center">
        <input type="text" name="q" class="form-control form-control-sm flex-grow-1"
               placeholder="Search employee name, code, description…"
               value="<?= esc($filters['search'] ?? '') ?>"/>
        <select name="type" class="form-select form-select-sm" style="max-width:160px">
            <option value="">All Types</option>
            <option value="Cash Advance" <?= ($filters['type'] ?? '') === 'Cash Advance' ? 'selected' : '' ?>>Cash Advance</option>
            <option value="Debt"         <?= ($filters['type'] ?? '') === 'Debt'         ? 'selected' : '' ?>>Debt / Loan</option>
        </select>
        <select name="cutoff" class="form-select form-select-sm" style="max-width:140px">
            <option value="">All Cutoffs</option>
            <option value="15"   <?= ($filters['cutoff'] ?? '') === '15'   ? 'selected' : '' ?>>Every 15th</option>
            <option value="30"   <?= ($filters['cutoff'] ?? '') === '30'   ? 'selected' : '' ?>>Every 30th</option>
            <option value="both" <?= ($filters['cutoff'] ?? '') === 'both' ? 'selected' : '' ?>>Every Cutoff (15 &amp; 30)</option>
        </select>
        <select name="status" class="form-select form-select-sm" style="max-width:140px">
            <option value="">All Status</option>
            <option value="active"    <?= ($filters['status'] ?? '') === 'active'    ? 'selected' : '' ?>>Active</option>
            <option value="completed" <?= ($filters['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
            <option value="cancelled" <?= ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
        </select>
        <button class="btn btn-sm btn-primary">Filter</button>
        <?php if (array_filter($filters)): ?>
        <a href="<?= site_url('deductions') ?>" class="btn btn-sm btn-outline-secondary">Clear</a>
        <?php endif; ?>
    </form>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Employee</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Per Cutoff</th>
                        <th class="text-center">Cutoff</th>
                        <th class="text-end">Remaining</th>
                        <th>Date Completed</th>
                        <th>Progress</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($deductions)): ?>
                    <tr><td colspan="11" class="text-center text-muted py-4">No deduction records found.</td></tr>
                <?php else: ?>
                <?php foreach ($deductions as $d):
                    $paid    = (float)$d['total_amount'] - (float)$d['remaining_balance'];
                    $pct     = $d['total_amount'] > 0 ? round(($paid / $d['total_amount']) * 100) : 0;
                    $badgeCls = match($d['status']) {
                        'active'    => 'bg-success',
                        'completed' => 'bg-secondary',
                        'cancelled' => 'bg-danger',
                        default     => 'bg-light text-dark',
                    };
                ?>
                <tr id="ded-row-<?= $d['id'] ?>">
                    <td>
                        <div class="fw-semibold"><?= esc($d['full_name']) ?></div>
                        <div class="text-muted small font-monospace"><?= esc($d['employee_code']) ?></div>
                    </td>
                    <td>
                        <span class="badge <?= $d['type'] === 'Cash Advance' ? 'bg-info text-dark' : 'bg-warning text-dark' ?>">
                            <?= $d['type'] === 'Cash Advance' ? 'CA' : 'Debt' ?>
                        </span>
                    </td>
                    <td><?= esc($d['description'] ?? '—') ?></td>
                    <td class="text-end">₱ <?= number_format($d['total_amount'], 2) ?></td>
                    <td class="text-end">₱ <?= number_format($d['amount_per_cutoff'], 2) ?></td>
                    <td class="text-center">
                        <span class="badge bg-light text-dark border">
                            <?= match($d['cutoff']) { '15' => '15th', '30' => '30th', default => '15 & 30' } ?>
                        </span>
                    </td>
                    <td class="text-end fw-semibold <?= (float)$d['remaining_balance'] > 0 ? 'text-danger' : 'text-muted' ?>" data-col="remaining">
                        ₱ <?= number_format($d['remaining_balance'], 2) ?>
                    </td>
                    <td>
                        <?php if ($d['status'] === 'completed' && ! empty($d['updated_at'])): ?>
                        <?= date('M j, Y', strtotime($d['updated_at'])) ?>
                        <?php else: ?>
                        &mdash;
                        <?php endif; ?>
                    </td>
                    <td style="min-width:100px" data-col="progress">
                        <div class="progress" style="height:8px;" title="<?= $pct ?>% paid">
                            <div class="progress-bar <?= $pct >= 100 ? 'bg-success' : 'bg-warning' ?>"
                                 style="width:<?= $pct ?>%"></div>
                        </div>
                        <div class="text-muted" style="font-size:11px"><?= $pct ?>%</div>
                    </td>
                    <td data-col="status"><span class="badge <?= $badgeCls ?>"><?= ucfirst($d['status']) ?></span></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="<?= site_url('deductions/view/' . $d['id']) ?>"
                               class="btn btn-sm btn-outline-info" title="View"><i class="fa fa-eye"></i></a>
                            <?php if ($d['status'] === 'active'): ?>
                            <button type="button"
                                    class="btn btn-sm <?= $d['is_enabled'] ? 'btn-success' : 'btn-outline-secondary' ?> toggle-btn"
                                    data-id="<?= $d['id'] ?>"
                                    title="<?= $d['is_enabled'] ? 'Click to disable for next payroll' : 'Click to enable for next payroll' ?>">
                                <?= $d['is_enabled'] ? 'ON' : 'OFF' ?>
                            </button>
                            <?php endif; ?>
                            <?php if (can_do('deductions', 'edit')): ?>
                            <a href="<?= site_url('deductions/edit/' . $d['id']) ?>"
                               class="btn btn-sm btn-outline-primary" title="Edit"><i class="fa fa-pen-to-square"></i></a>
                            <?php endif; ?>
                            <?php if (can_do('deductions', 'delete')): ?>
                            <a href="<?= site_url('deductions/delete/' . $d['id']) ?>"
                               class="btn btn-sm btn-outline-danger" title="Delete"
                               onclick="return confirm('Delete this deduction record?')">
                               <i class="fa fa-trash"></i></a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?= $this->section('scripts') ?>
<script>
function getCookie(name) {
    const m = document.cookie.match('(?:^|;)\\s*' + name + '=([^;]*)');
    return m ? decodeURIComponent(m[1]) : '';
}

const fmt = n => '₱ ' + new Intl.NumberFormat('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}).format(n);

document.querySelectorAll('.toggle-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const id   = this.dataset.id;
        const self = this;
        self.disabled = true;
        const form = new FormData();
        form.append('csrf_test_name', getCookie('csrf_cookie_name'));

        fetch('<?= site_url('deductions/toggle/') ?>' + id, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: form
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            const tr = document.getElementById('ded-row-' + id);

            // — Toggle button —
            if (data.is_enabled) {
                self.textContent = 'ON';
                self.classList.replace('btn-outline-secondary', 'btn-success');
                self.title = 'Click to disable for next payroll';
            } else {
                self.textContent = 'OFF';
                self.classList.replace('btn-success', 'btn-outline-secondary');
                self.title = 'Click to enable for next payroll';
            }

            // — Remaining balance cell —
            const remEl  = tr.querySelector('[data-col="remaining"]');
            const balance = data.remaining_balance;
            remEl.textContent = fmt(balance);
            remEl.classList.toggle('text-danger', balance > 0);
            remEl.classList.toggle('text-muted',  balance <= 0);

            // — Progress bar cell —
            const total = data.total_amount;
            const paid  = total > 0 ? total - balance : 0;
            const pct   = total > 0 ? Math.min(100, Math.round((paid / total) * 100)) : 0;
            tr.querySelector('[data-col="progress"]').innerHTML =
                `<div class="progress" style="height:8px;" title="${pct}% paid">
                    <div class="progress-bar ${pct >= 100 ? 'bg-success' : 'bg-warning'}" style="width:${pct}%"></div>
                </div>
                <div class="text-muted" style="font-size:11px">${pct}%</div>`;

            // — Status badge cell —
            const badgeMap = {active:'bg-success', completed:'bg-secondary', cancelled:'bg-danger'};
            const badgeCls = badgeMap[data.status] || 'bg-light text-dark';
            tr.querySelector('[data-col="status"]').innerHTML =
                `<span class="badge ${badgeCls}">${data.status.charAt(0).toUpperCase() + data.status.slice(1)}</span>`;

            // Hide toggle button once deduction is no longer active
            if (data.status !== 'active') self.style.display = 'none';
        })
        .finally(() => { self.disabled = false; });
    });
});
</script>
<?= $this->endSection() ?>

<?= $this->endSection() ?>
