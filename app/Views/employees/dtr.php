<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="<?= site_url('employees/view/' . $employee['id']) ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fa fa-arrow-left"></i>
    </a>
    <h5 class="mb-0 fw-semibold">DTR – <?= esc($employee['full_name']) ?></h5>
</div>

<!-- Month picker -->
<div class="card mb-3 p-2">
    <form method="get" class="d-flex gap-2 align-items-center">
        <label class="form-label mb-0 text-muted small">Month:</label>
        <input type="month" name="month" class="form-control form-control-sm" style="width:200px;"
               value="<?= esc($month) ?>"/>
        <button class="btn btn-sm btn-primary">Go</button>
    </form>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            <i class="fa fa-calendar-days me-2"></i>
            <?= date('F Y', strtotime($start)) ?>
        </span>
        <span class="text-muted small"><?= esc($employee['employee_code']) ?> | ₱<?= number_format($employee['daily_rate'], 2) ?>/day</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Day</th>
                        <th>Type</th>
                        <th>OT Hours</th>
                        <th>Equivalent</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                    $current = strtotime($start);
                    $endTs   = strtotime($end);
                    $recordsByDate = [];
                    foreach ($records as $r) {
                        $recordsByDate[$r['attendance_date']] = $r;
                    }
                    $totalDays = 0;
                    $totalOT   = 0;
                    while ($current <= $endTs):
                        $dateStr = date('Y-m-d', $current);
                        $dow     = (int) date('N', $current); // 1=Mon…7=Sun
                        $isWeekend = $dow >= 6;
                        $rec     = $recordsByDate[$dateStr] ?? null;
                        $dayEq   = 0;
                        if ($rec) {
                            $dayEq = match($rec['attendance_type']) {
                                'whole_day' => 1.0,
                                'half_am','half_pm' => 0.5,
                                default => 0,
                            };
                            $totalDays += $dayEq;
                            $totalOT   += (float) $rec['overtime_hours'];
                        }
                ?>
                <tr class="<?= $isWeekend ? 'table-light text-muted' : '' ?>">
                    <td><?= date('M j', $current) ?></td>
                    <td><?= date('D', $current) ?></td>
                    <td>
                        <?php if ($rec): ?>
                            <?php
                                echo match($rec['attendance_type']) {
                                    'whole_day' => '<span class="badge att-badge-whole">Whole Day</span>',
                                    'half_am'   => '<span class="badge att-badge-half">Half AM</span>',
                                    'half_pm'   => '<span class="badge att-badge-half">Half PM</span>',
                                    'absent'    => '<span class="badge att-badge-absent">Absent</span>',
                                    default     => esc($rec['attendance_type']),
                                };
                            ?>
                        <?php elseif ($isWeekend): ?>
                            <span class="text-muted small">Weekend</span>
                        <?php else: ?>
                            <span class="text-muted small">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?= ($rec && $rec['overtime_hours'] > 0) ? $rec['overtime_hours'] . ' hrs' : '—' ?></td>
                    <td><?= $dayEq > 0 ? $dayEq . ' day' : '—' ?></td>
                    <td class="text-muted small"><?= esc($rec['remarks'] ?? '') ?></td>
                </tr>
                <?php $current = strtotime('+1 day', $current); endwhile; ?>
                </tbody>
                <tfoot>
                    <tr class="table-dark fw-bold">
                        <td colspan="4">Total</td>
                        <td><?= $totalDays ?> day(s) | OT: <?= $totalOT ?> hrs</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
