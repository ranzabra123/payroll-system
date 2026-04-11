<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#dbeafe;">
                    <i class="fa fa-users" style="color:#2563eb;"></i>
                </div>
                <div>
                    <div class="text-muted small">Active Employees</div>
                    <div class="fw-bold fs-3"><?= $totalEmployees ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php if (! empty($isAdmin)): ?>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#dcfce7;">
                    <i class="fa fa-money-bill-wave" style="color:#16a34a;"></i>
                </div>
                <div>
                    <div class="text-muted small">Payroll Runs</div>
                    <div class="fw-bold fs-3"><?= $totalPayrolls ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#fef3c7;">
                    <i class="fa fa-calendar-check" style="color:#d97706;"></i>
                </div>
                <div>
                    <div class="text-muted small">Today's Attendance</div>
                    <div class="fw-bold fs-3"><?= $todayAttendance ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php if (! empty($isAdmin)): ?>
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card p-3">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon" style="background:#ede9fe;">
                    <i class="fa fa-file-invoice-dollar" style="color:#7c3aed;"></i>
                </div>
                <div>
                    <div class="text-muted small">Finalized Payrolls</div>
                    <div class="fw-bold fs-3"><?= $finalizedPayrolls ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="row g-3">
    <?php if (! empty($isAdmin)): ?>
    <!-- Latest Payroll -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fa fa-money-bill-wave me-2 text-success"></i>Latest Payroll</span>
                <a href="<?= site_url('payroll') ?>" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if ($latestPayroll): ?>
                <table class="table table-sm mb-0">
                    <tbody>
                    <tr><td class="text-muted">Period</td>
                        <td class="fw-semibold"><?= esc(\App\Models\PayrollModel::periodLabel($latestPayroll)) ?></td></tr>
                    <tr><td class="text-muted">Status</td>
                        <td><span class="badge <?= $latestPayroll['status'] === 'finalized' ? 'badge-final' : 'badge-draft' ?>">
                            <?= ucfirst($latestPayroll['status']) ?></span></td></tr>
                    <tr><td class="text-muted">Gross Pay</td>
                        <td class="text-success fw-semibold">₱ <?= number_format($latestPayroll['total_gross'], 2) ?></td></tr>
                    <tr><td class="text-muted">Deductions</td>
                        <td class="text-danger">₱ <?= number_format($latestPayroll['total_deductions'], 2) ?></td></tr>
                    <tr><td class="text-muted">Net Pay</td>
                        <td class="fw-bold fs-5">₱ <?= number_format($latestPayroll['total_net'], 2) ?></td></tr>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="text-center text-muted py-4">
                    <i class="fa fa-inbox fa-2x mb-2 d-block opacity-40"></i>No payroll runs yet.
                    <a href="<?= site_url('payroll/create') ?>" class="btn btn-primary btn-sm mt-2 d-block mx-auto" style="width:fit-content;">
                        Generate Payroll
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <i class="fa fa-bolt me-2 text-warning"></i>Quick Actions
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-6">
                        <a href="<?= site_url('attendance?date=' . date('Y-m-d')) ?>"
                           class="btn btn-outline-primary w-100 py-3">
                            <i class="fa fa-calendar-check fa-lg d-block mb-1"></i>
                            <small>Today's Attendance</small>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="<?= site_url('employees/create') ?>"
                           class="btn btn-outline-success w-100 py-3">
                            <i class="fa fa-user-plus fa-lg d-block mb-1"></i>
                            <small>Add Employee</small>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="<?= site_url('payroll/create') ?>"
                           class="btn btn-outline-warning w-100 py-3">
                            <i class="fa fa-file-invoice-dollar fa-lg d-block mb-1"></i>
                            <small>Generate Payroll</small>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="<?= site_url('reports') ?>"
                           class="btn btn-outline-info w-100 py-3">
                            <i class="fa fa-chart-bar fa-lg d-block mb-1"></i>
                            <small>View Reports</small>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (! empty($isAdmin)): ?>
    <!-- Recent Audit Logs -->
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="fa fa-clock-rotate-left me-2 text-info"></i>Recent Activity
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>User</th>
                                <th>Module</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($recentLogs)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">No activity yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentLogs as $log): ?>
                            <tr>
                                <td class="text-muted small"><?= date('M j, g:i a', strtotime($log['created_at'])) ?></td>
                                <td><?= esc($log['user_name'] ?? 'System') ?></td>
                                <td><span class="badge bg-secondary"><?= esc($log['module']) ?></span></td>
                                <td><?= esc(ucfirst($log['action'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
