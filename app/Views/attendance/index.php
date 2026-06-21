<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-semibold">Daily Attendance Input</h5>
    <a href="<?= site_url('attendance/records') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="fa fa-table me-1"></i>Monthly Records
    </a>
</div>

<!-- Date picker -->
<div class="card mb-3 p-2">
    <form method="get" class="d-flex gap-2 align-items-center">
        <label class="form-label mb-0 fw-medium">Date:</label>
         <input type="date" name="date" class="form-control form-control-sm" style="width:200px;"
             value="<?= esc($date) ?>" onchange="this.form.submit()"/>
        
        <span class="text-muted small ms-2">
            <i class="fa fa-circle-info me-1"></i>
            <?= date('l, F j, Y', strtotime($date)) ?>
        </span>
    </form>
</div>

<?php if (date('N', strtotime($date)) === '7'): ?>
<div class="alert alert-warning d-flex align-items-center gap-2 py-2 mb-2" id="sundayNotice">
    <i class="fa fa-sun text-warning"></i>
    <span><strong>Sunday</strong> — All employees have been pre-set to <strong>Half Day (AM)</strong>. Adjust individually if needed.</span>
</div>
<?php endif; ?>

<!-- Branch and search filter form (separate from attendance save form) -->
<div class="d-flex gap-2 ms-auto align-items-center mb-2">
    <form method="get" id="branchSortForm" class="d-flex align-items-center gap-2 mb-0" style="margin-bottom:0 !important;">
        <select name="branch_id" class="form-select form-select-sm" style="width:160px;" onchange="document.getElementById('branchSortForm').submit()">
            <option value="">All Branches</option>
            <?php if (isset($branches)) foreach ($branches as $b): ?>
                <option value="<?= $b['id'] ?>" <?= (isset($branchId) && $branchId == $b['id']) ? 'selected' : '' ?>><?= esc($b['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="hidden" name="date" value="<?= esc($date) ?>"/>
    </form>
    <div class="input-group input-group-sm" style="width:260px;">
        <span class="input-group-text bg-white border-end-0">
            <i class="fa fa-magnifying-glass text-muted"></i>
        </span>
        <input type="text" id="attSearch" class="form-control border-start-0 ps-0"
               placeholder="Search employee, position, or branch…"
               oninput="filterAttendance(this.value)"/>
        <button type="button" class="btn btn-outline-secondary"
                onclick="document.getElementById('attSearch').value=''; filterAttendance('');"
                title="Clear">✕</button>
    </div>
</div>

<?php if (empty($employees)): ?>
<div class="alert alert-warning">
    <i class="fa fa-triangle-exclamation me-2"></i>No active employees found.
    <a href="<?= site_url('employees/create') ?>">Add employees</a> first.
</div>
<?php else: ?>

<form action="<?= site_url('attendance/store') ?>" method="POST" id="attendanceSaveForm">
    <?= csrf_field() ?>
    <input type="hidden" name="attendance_date" value="<?= esc($date) ?>"/>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span><i class="fa fa-clipboard-list me-2 text-primary"></i>
                Attendance for <?= date('F j, Y', strtotime($date)) ?>
            </span>
            <div class="d-flex gap-2 ms-auto">
                <button type="button" class="btn btn-sm btn-outline-success" onclick="markAll('whole_day')">
                    All Whole Day
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="markAll('absent')">
                    All Absent
                </button>
                <?php if (can_do('attendance', 'delete')): ?>
                <button type="button" class="btn btn-sm btn-danger <?= empty($existingMap) ? 'd-none' : '' ?>" id="deleteAllBtn"
                        title="Remove all attendance records for this date">
                    <i class="fa fa-trash me-1"></i>Remove All
                </button>
                <?php endif; ?>
            </div>

        </div>
        <div id="noResults" class="alert alert-info m-3 d-none">
            <i class="fa fa-circle-info me-2"></i>No employees match your search.
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0 align-middle" id="attendanceTable">
                    <thead>
                        <tr>
                            <th style="width:30px;">#</th>
                            <th>Employee</th>
                            <th>Position</th>
                            <th>Branch</th>
                            <th style="width:200px;">
                                <div class="d-flex gap-2 align-items-center">
                                    Attendance
                                </div>
                            </th>
                            <th style="width:130px;">OT Hours</th>
                            <th style="width:100px;">Status</th>
                        </tr>
                    </thead>
                    <tbody id="attendanceTbody">
                    <?php foreach ($employees as $i => $emp): ?>
                        <?php $existing = $existingMap[$emp['id']] ?? null; ?>
                        <tr data-search="<?= strtolower(esc($emp['full_name']) . ' ' . esc($emp['employee_code']) . ' ' . esc($emp['position']) . ' ' . (isset($emp['branch_name']) ? esc($emp['branch_name']) : '')) ?>">
                            <td class="text-muted small"><?= $i + 1 ?></td>
                            <td>
                                <div class="fw-semibold"><?= esc($emp['full_name']) ?></div>
                                <div class="text-muted small"><?= esc($emp['employee_code']) ?></div>
                            </td>
                            <td class="text-muted small"><?= esc($emp['position']) ?></td>
                            <td class="text-muted small"><?= isset($emp['branch_name']) ? esc($emp['branch_name']) : '' ?></td>
                            <td>
                                <select name="attendance[<?= $emp['id'] ?>]"
                                        class="form-select form-select-sm att-select">
                                    <?php $sel = $existing ? $existing['attendance_type'] : 'whole_day'; ?>
                                    <option value="whole_day" <?= $sel === 'whole_day' ? 'selected' : '' ?>>Whole Day</option>
                                    <option value="half_am"   <?= $sel === 'half_am'   ? 'selected' : '' ?>>Half Day AM</option>
                                    <option value="half_pm"   <?= $sel === 'half_pm'   ? 'selected' : '' ?>>Half Day PM</option>
                                    <option value="absent"    <?= $sel === 'absent'    ? 'selected' : '' ?>>Absent</option>
                                    <option value="day_off"   <?= $sel === 'day_off'   ? 'selected' : '' ?>>Day Off</option>
                                </select>
                            </td>
                            <td>
                                <input type="number" name="overtime[<?= $emp['id'] ?>]"
                                       class="form-control form-control-sm"
                                       min="0" max="12" step="0.5"
                                       value="<?= $existing['overtime_hours'] ?? 0 ?>"
                                       placeholder="0"/>
                            </td>
                            <td>
                                <?php if ($existing): ?>
                                <span class="badge bg-success small">Recorded</span>
                                <?php else: ?>
                                <span class="badge bg-danger small">No Record</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if (can_do('attendance', 'add') || can_do('attendance', 'edit')): ?>
        <div class="card-footer d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-floppy-disk me-1"></i>Save Attendance
            </button>
            <span class="text-muted small align-self-center">
                Saving will overwrite existing records for this date.
            </span>
        </div>
        <?php endif; ?>
    </div>
</form>

<!-- Delete-by-date form (must be outside the main form) -->
<form id="deleteByDateForm" action="<?= site_url('attendance/delete-by-date') ?>" method="POST" class="d-none">
    <?= csrf_field() ?>
    <input type="hidden" name="date" value="<?= esc($date) ?>">
</form>

<?php endif; ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
function markAll(type) {
    // Only mark visible rows
    document.querySelectorAll('#attendanceTbody tr').forEach(function(row) {
        if (row.style.display !== 'none') {
            const sel = row.querySelector('.att-select');
            if (sel) sel.value = type;
        }
    });
}

function filterAttendance(q) {
    const term = q.trim().toLowerCase();
    const rows = document.querySelectorAll('#attendanceTbody tr');
    let visible = 0;
    rows.forEach(function(row) {
        const text = row.dataset.search || '';
        const show = !term || text.includes(term);
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    let n = 1;
    rows.forEach(function(row) {
        if (row.style.display !== 'none') {
            const numCell = row.querySelector('td:first-child');
            if (numCell) numCell.textContent = n++;
        }
    });
    document.getElementById('noResults').classList.toggle('d-none', visible > 0);
}

document.getElementById('deleteAllBtn')?.addEventListener('click', function () {
    if (confirm('Remove ALL attendance records for <?= date('F j, Y', strtotime($date)) ?>?\n\nThis cannot be undone.')) {
        document.getElementById('deleteByDateForm').submit();
    }
});

// Auto-set half_am for employees with NO existing record when the date is a Sunday.
// Never override rows that are already recorded (badge = Recorded).
(function () {
    const dateVal = '<?= esc($date) ?>';
    if (!dateVal) return;
    const d = new Date(dateVal + 'T00:00:00');
    if (d.getDay() !== 0) return; // 0 = Sunday

    document.querySelectorAll('#attendanceTbody tr').forEach(function (row) {
        const sel    = row.querySelector('.att-select');
        const badge  = row.querySelector('.badge');
        const isNew  = badge && badge.classList.contains('bg-danger'); // "No Record"
        // Only default to half_am when there is no saved record yet
        if (sel && isNew) {
            sel.value = 'half_am';
        }
    });

    // Warn when user manually selects "absent" on a Sunday
    document.querySelectorAll('.att-select').forEach(function (sel) {
        sel.addEventListener('change', function () {
            if (this.value === 'absent') {
                const row   = this.closest('tr');
                const name  = row ? (row.querySelector('td:nth-child(2) .fw-semibold') || {}).textContent : '';
                if (!confirm('Sunday: Mark ' + (name ? name.trim() : 'this employee') + ' as Absent on a Sunday?\n\nSundays are normally half-day. Press OK to confirm Absent.')) {
                    this.value = 'half_am';
                }
            }
        });
    });
})();
</script>
<?= $this->endSection() ?>
