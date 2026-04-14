<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="mb-0 fw-semibold"><i class="fa fa-calendar-day me-2 text-primary"></i>Special Day Adjustments</h5>
    <?php if (can_do('special_days', 'add')): ?>
    <a href="<?= site_url('special-days/create') ?>" class="btn btn-primary btn-sm">
        <i class="fa fa-plus me-1"></i>Add Adjustment
    </a>
    <?php endif; ?>
</div>

<!-- Filter bar -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-sm-6 col-lg-3">
                <label class="form-label form-label-sm mb-1">Search</label>
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Name, code, reason…" value="<?= esc($filters['search'] ?? '') ?>"/>
            </div>
            <div class="col-sm-6 col-lg-2">
                <label class="form-label form-label-sm mb-1">Type</label>
                <select name="adjustment_type" class="form-select form-select-sm">
                    <option value="">All Types</option>
                    <option value="fixed_amount"  <?= ($filters['adjustment_type'] ?? '') === 'fixed_amount'  ? 'selected' : '' ?>>Fixed Amount</option>
                    <option value="double_salary" <?= ($filters['adjustment_type'] ?? '') === 'double_salary' ? 'selected' : '' ?>>Double Salary</option>
                </select>
            </div>
            <div class="col-sm-6 col-lg-2">
                <label class="form-label form-label-sm mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="pending" <?= ($filters['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="applied" <?= ($filters['status'] ?? '') === 'applied' ? 'selected' : '' ?>>Applied</option>
                </select>
            </div>
            <div class="col-sm-6 col-lg-2">
                <label class="form-label form-label-sm mb-1">Date From</label>
                <input type="date" name="date_from" class="form-control form-control-sm"
                       value="<?= esc($filters['date_from'] ?? '') ?>"/>
            </div>
            <div class="col-sm-6 col-lg-2">
                <label class="form-label form-label-sm mb-1">Date To</label>
                <input type="date" name="date_to" class="form-control form-control-sm"
                       value="<?= esc($filters['date_to'] ?? '') ?>"/>
            </div>
            <div class="col-sm-6 col-lg-1 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="fa fa-filter"></i>
                </button>
                <a href="<?= site_url('special-days') ?>" class="btn btn-outline-secondary btn-sm w-100">
                    <i class="fa fa-xmark"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <?php if (empty($records)): ?>
        <div class="text-center text-muted py-5">
            <i class="fa fa-calendar-day fa-3x mb-3 opacity-25 d-block"></i>
            <p class="mb-0">No special day adjustments found.</p>
            <?php if (can_do('special_days', 'add')): ?>
            <a href="<?= site_url('special-days/create') ?>" class="btn btn-primary btn-sm mt-3">
                <i class="fa fa-plus me-1"></i>Add First Adjustment
            </a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Type</th>
                        <th class="text-end">Amount / Effect</th>
                        <th>Reason</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($records as $row): ?>
                <tr>
                    <td class="fw-semibold"><?= date('M j, Y', strtotime($row['date'])) ?></td>
                    <td>
                        <div class="fw-semibold"><?= esc($row['full_name']) ?></div>
                        <div class="text-muted small"><?= esc($row['employee_code']) ?></div>
                    </td>
                    <td class="text-muted small"><?= esc($row['department'] ?: '—') ?></td>
                    <td>
                        <?php if ($row['adjustment_type'] === 'double_salary'): ?>
                        <span class="badge" style="background:#dbeafe;color:#1e40af;">
                            <i class="fa fa-2 me-1"></i>Double Salary
                        </span>
                        <?php else: ?>
                        <span class="badge" style="background:#dcfce7;color:#166534;">
                            <i class="fa fa-plus me-1"></i>Fixed Amount
                        </span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end fw-semibold">
                        <?php if ($row['adjustment_type'] === 'double_salary'): ?>
                        <span class="text-primary">+₱<?= number_format((float)$row['daily_rate'], 2) ?></span>
                        <div class="text-muted" style="font-size:.75rem;">(daily rate × 2)</div>
                        <?php else: ?>
                        <span class="text-success">+₱<?= number_format((float)$row['amount'], 2) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-muted small"><?= esc($row['reason'] ?: '—') ?></td>
                    <td class="text-center">
                        <?php if ($row['status'] === 'applied'): ?>
                        <span class="badge bg-success">Applied</span>
                        <?php else: ?>
                        <span class="badge bg-warning text-dark">Pending</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php if ($row['status'] === 'pending'): ?>
                        <div class="d-flex gap-1 justify-content-center">
                            <?php if (can_do('special_days', 'edit')): ?>
                            <a href="<?= site_url('special-days/edit/' . $row['id']) ?>"
                               class="btn btn-sm btn-outline-primary" title="Edit">
                                <i class="fa fa-pen"></i>
                            </a>
                            <?php endif; ?>
                            <?php if (can_do('special_days', 'delete')): ?>
                            <a href="<?= site_url('special-days/delete/' . $row['id']) ?>"
                               class="btn btn-sm btn-outline-danger"
                               data-confirm="Delete this special day adjustment?"
                               title="Delete">
                                <i class="fa fa-trash"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <span class="text-muted small">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
