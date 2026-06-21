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
    <div class="card-header d-flex align-items-center gap-2 flex-wrap">
        <span><i class="fa fa-table-list me-2"></i><?= date('F Y', strtotime($month . '-01')) ?> – Attendance Summary</span>
        <div class="ms-auto input-group input-group-sm" style="width:260px;">
            <span class="input-group-text bg-white border-end-0">
                <i class="fa fa-magnifying-glass text-muted"></i>
            </span>
            <input type="text" id="recSearch" class="form-control border-start-0 ps-0"
                   placeholder="Search name, code, or position…"
                   oninput="filterRecords(this.value)"/>
            <button type="button" class="btn btn-outline-secondary"
                    onclick="document.getElementById('recSearch').value='';filterRecords('');" title="Clear">✕</button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Employee</th>
                        <th>Position</th>
                        <th class="text-center">Working Days</th>
                        <th class="text-center">Attendance</th>
                        <th class="text-center">Absent (Whole)</th>
                        <th class="text-center">Half Day</th>
                        <th class="text-center">OT Hours</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="recTbody">
                <?php if (empty($summary)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">
                        No attendance data for <?= date('F Y', strtotime($month . '-01')) ?>.
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($summary as $row): ?>
                    <?php
                        $deptWd     = (float) ($deptWdMap[$row['department'] ?? ''] ?? 26);
                        $adjWd      = $deptWd + ($calendarDays - 30);
                        $attendance = $row['whole_days'] + ($row['half_days'] * 0.5);
                    ?>
                    <tr data-search="<?= strtolower(esc($row['full_name']) . ' ' . esc($row['employee_code']) . ' ' . esc($row['position'])) ?>">
                        <td class="font-monospace small"><?= esc($row['employee_code']) ?></td>
                        <td class="fw-semibold"><?= esc($row['full_name']) ?></td>
                        <td class="text-muted small"><?= esc($row['position']) ?></td>
                        <td class="text-center fw-semibold"><?= number_format($adjWd, $adjWd == floor($adjWd) ? 0 : 1) ?></td>
                        <td class="text-center fw-semibold"><?= number_format($attendance, $attendance == floor($attendance) ? 0 : 1) ?></td>
                        <td class="text-center"><span class="badge att-badge-absent"><?= $row['absent_days'] ?></span></td>
                        <td class="text-center"><span class="badge att-badge-half"><?= $row['half_days'] ?></span></td>
                        <td class="text-center"><?= $row['total_overtime'] > 0 ? $row['total_overtime'] . ' hrs' : '—' ?></td>
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

<?= $this->section('scripts') ?>
<script>
function filterRecords(q) {
    const term = q.trim().toLowerCase();
    const rows = document.querySelectorAll('#recTbody tr[data-search]');
    let visible = 0;
    rows.forEach(function (row) {
        const match = !term || row.dataset.search.includes(term);
        row.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    // Show/hide the empty-state row
    const empty = document.querySelector('#recTbody tr td[colspan]');
    if (empty) empty.closest('tr').style.display = visible === 0 ? '' : 'none';
}
</script>
<?= $this->endSection() ?>
