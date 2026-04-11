<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-semibold">Monthly Attendance Summary</h5>
    <a href="<?= site_url('attendance') ?>" class="btn btn-outline-primary btn-sm">
        <i class="fa fa-calendar-check me-1"></i>Daily Input
    </a>
</div>

<!-- Month picker -->
<div class="card mb-3 p-2">
    <form method="get" class="d-flex gap-2 align-items-center">
        <label class="form-label mb-0 fw-medium">Month:</label>
        <input type="month" name="month" class="form-control form-control-sm" style="width:200px;"
               value="<?= esc($month) ?>"/>
        <button class="btn btn-sm btn-primary">View</button>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <i class="fa fa-table-list me-2"></i>
        <?= date('F Y', strtotime($month . '-01')) ?> – Attendance Summary
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Employee</th>
                        <th>Position</th>
                        <th class="text-center">Whole Days</th>
                        <th class="text-center">Half Days</th>
                        <th class="text-center">Absent</th>
                        <th class="text-center">OT Hours</th>
                        <th class="text-center">Effective Days</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($summary)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">
                        No attendance data for <?= date('F Y', strtotime($month . '-01')) ?>.
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($summary as $row): ?>
                    <?php $effectiveDays = $row['whole_days'] + ($row['half_days'] * 0.5); ?>
                    <tr>
                        <td class="font-monospace small"><?= esc($row['employee_code']) ?></td>
                        <td class="fw-semibold"><?= esc($row['full_name']) ?></td>
                        <td class="text-muted small"><?= esc($row['position']) ?></td>
                        <td class="text-center"><span class="badge att-badge-whole"><?= $row['whole_days'] ?></span></td>
                        <td class="text-center"><span class="badge att-badge-half"><?= $row['half_days'] ?></span></td>
                        <td class="text-center"><span class="badge att-badge-absent"><?= $row['absent_days'] ?></span></td>
                        <td class="text-center"><?= $row['total_overtime'] > 0 ? $row['total_overtime'] . ' hrs' : '—' ?></td>
                        <td class="text-center fw-semibold"><?= $effectiveDays ?></td>
                        <td>
                            <a href="<?= site_url('attendance/view/' . $row['employee_id'] . '?month=' . $month) ?>"
                               class="btn btn-sm btn-outline-info">
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

<?= $this->endSection() ?>
