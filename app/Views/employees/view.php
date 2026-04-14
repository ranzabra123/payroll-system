<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="<?= site_url('employees') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="fa fa-arrow-left"></i>
    </a>
    <h5 class="mb-0 fw-semibold"><?= esc($employee['full_name']) ?></h5>
    <span class="badge <?= $employee['status'] === 'active' ? 'badge-active' : 'badge-inactive' ?>">
        <?= ucfirst($employee['status']) ?>
    </span>
</div>

<div class="row g-3">
    <!-- Profile Card -->
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-body text-center py-4">
                <div style="width:80px;height:80px;border-radius:50%;background:#2563eb;display:flex;align-items:center;justify-content:center;color:#fff;font-size:2rem;font-weight:700;margin:0 auto 1rem;">
                    <?= strtoupper(substr($employee['full_name'], 0, 1)) ?>
                </div>
                <h5 class="mb-1"><?= esc($employee['full_name']) ?></h5>
                <p class="text-muted mb-3"><?= esc($employee['position']) ?> <?= $employee['department'] ? '– ' . esc($employee['department']) : '' ?></p>
                <span class="badge bg-secondary font-monospace fs-6"><?= esc($employee['employee_code']) ?></span>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">Employment</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tr><td class="text-muted">Date Hired</td>
                        <td><?= date('M j, Y', strtotime($employee['date_hired'])) ?></td></tr>
                    <tr><td class="text-muted">Service</td>
                        <td><?= \App\Models\EmployeeModel::yearsOfService($employee['date_hired']) ?></td></tr>
                    <tr><td class="text-muted">Monthly Salary</td>
                        <td class="fw-semibold text-success">₱ <?= number_format($employee['monthly_salary'], 2) ?></td></tr>
                    <tr><td class="text-muted">Daily Rate</td>
                        <td>₱ <?= number_format($employee['daily_rate'], 2) ?></td></tr>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Gov't Numbers</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0 font-monospace">
                    <tr><td class="text-muted">SSS</td><td><?= esc($employee['sss_number'] ?? '—') ?></td></tr>
                    <tr><td class="text-muted">PhilHealth</td><td><?= esc($employee['philhealth_number'] ?? '—') ?></td></tr>
                    <tr><td class="text-muted">Pag-IBIG</td><td><?= esc($employee['pagibig_number'] ?? '—') ?></td></tr>
                    <tr><td class="text-muted">TIN</td><td><?= esc($employee['tin_number'] ?? '—') ?></td></tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Right side -->
    <div class="col-lg-8">
        <div class="d-flex gap-2 mb-3">
            <?php if (can_do('employees', 'edit')): ?>
            <a href="<?= site_url('employees/edit/' . $employee['id']) ?>" class="btn btn-primary btn-sm">
                <i class="fa fa-pen-to-square me-1"></i>Edit
            </a>
            <?php endif; ?>
            <a href="<?= site_url('employees/dtr/' . $employee['id']) ?>" class="btn btn-outline-secondary btn-sm">
                <i class="fa fa-calendar-days me-1"></i>DTR
            </a>
            <a href="<?= site_url('attendance/view/' . $employee['id']) ?>" class="btn btn-outline-info btn-sm">
                <i class="fa fa-clock me-1"></i>Attendance
            </a>
        </div>

        <!-- Salary History -->
        <div class="card mb-3">
            <div class="card-header">
                <i class="fa fa-history me-2 text-warning"></i>Salary History
            </div>
            <div class="card-body p-0">
                <?php if (empty($salaryHistory)): ?>
                <p class="text-muted text-center py-3 mb-0">No salary changes recorded.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr>
                            <th>Effective Date</th>
                            <th>Previous</th>
                            <th>New Salary</th>
                            <th>Reason</th>
                            <th>Changed By</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($salaryHistory as $sh): ?>
                        <tr>
                            <td><?= date('M j, Y', strtotime($sh['effective_date'])) ?></td>
                            <td class="text-muted">₱ <?= number_format($sh['previous_salary'], 2) ?></td>
                            <td class="text-success fw-semibold">₱ <?= number_format($sh['new_salary'], 2) ?></td>
                            <td class="text-muted small"><?= esc($sh['reason'] ?? '—') ?></td>
                            <td><?= esc($sh['changed_by_name'] ?? '—') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Attendance (30 days) -->
        <div class="card">
            <div class="card-header">
                <i class="fa fa-calendar-check me-2 text-success"></i>Last 30 Days Attendance
            </div>
            <div class="card-body p-0">
                <?php if (empty($attendance30)): ?>
                <p class="text-muted text-center py-3 mb-0">No attendance records in the last 30 days.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>OT Hours</th>
                            <th>Remarks</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($attendance30 as $att): ?>
                        <?php
                            $typeLabel = match($att['attendance_type']) {
                                'whole_day' => '<span class="badge att-badge-whole">Whole Day</span>',
                                'half_am'   => '<span class="badge att-badge-half">Half AM</span>',
                                'half_pm'   => '<span class="badge att-badge-half">Half PM</span>',
                                'absent'    => '<span class="badge att-badge-absent">Absent</span>',
                                default     => esc($att['attendance_type']),
                            };
                        ?>
                        <tr>
                            <td><?= date('D, M j', strtotime($att['attendance_date'])) ?></td>
                            <td><?= $typeLabel ?></td>
                            <td><?= $att['overtime_hours'] > 0 ? $att['overtime_hours'] . ' hrs' : '—' ?></td>
                            <td class="text-muted small"><?= esc($att['remarks'] ?? '—') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
