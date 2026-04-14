<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-semibold">Employees <?php if ($search): ?><span class="text-muted small">– "<?= esc($search) ?>"</span><?php endif; ?></h5>
    <?php if (can_do('employees', 'add')): ?>
    <a href="<?= site_url('employees/create') ?>" class="btn btn-primary btn-sm">
        <i class="fa fa-user-plus me-1"></i>Add Employee
    </a>
    <?php endif; ?>
</div>

<!-- Search -->
<div class="card mb-3 p-2">
    <form method="get" class="d-flex flex-wrap gap-2 align-items-center">
        <input type="text" name="q" class="form-control form-control-sm flex-grow-1"
               placeholder="Search by name, position, department…"
               value="<?= esc($search ?? '') ?>"/>
        <select name="branch_id" class="form-select form-select-sm" style="max-width:200px">
            <option value="">All Branches</option>
            <?php foreach ($branches as $b): ?>
            <option value="<?= $b['id'] ?>" <?= ($branchId ?? null) == $b['id'] ? 'selected' : '' ?>>
                <?= esc($b['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-sm btn-primary">Search</button>
        <?php if ($search || $branchId): ?>
        <a href="<?= site_url('employees') ?>" class="btn btn-sm btn-outline-secondary">Clear</a>
        <?php endif; ?>
    </form>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Position</th>
                        <th>Department</th>
                        <th>Branch</th>
                        <th>Monthly Salary</th>
                        <th>Daily Rate</th>
                        <th>Date Hired</th>
                        <th>Service</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($employees)): ?>
                    <tr><td colspan="11" class="text-center text-muted py-4">No employees found.</td></tr>
                <?php else: ?>
                    <?php foreach ($employees as $emp): ?>
                    <tr>
                        <td class="font-monospace small"><?= esc($emp['employee_code']) ?></td>
                        <td>
                            <a href="<?= site_url('employees/view/' . $emp['id']) ?>" class="fw-semibold text-decoration-none">
                                <?= esc($emp['full_name']) ?>
                            </a>
                        </td>
                        <td><?= esc($emp['position']) ?></td>
                        <td><?= esc($emp['department'] ?? '—') ?></td>
                        <td><?php
                            if (!empty($emp['branch_id'])):
                                $bName = '';
                                foreach ($branches as $b) { if ($b['id'] == $emp['branch_id']) { $bName = $b['name']; break; } }
                                echo esc($bName ?: '—');
                            else: echo '—'; endif; ?></td>
                        <td class="text-end">₱ <?= number_format($emp['monthly_salary'], 2) ?></td>
                        <td class="text-end">₱ <?= number_format($emp['daily_rate'], 2) ?></td>
                        <td><?= date('M j, Y', strtotime($emp['date_hired'])) ?></td>
                        <td class="text-muted small"><?= \App\Models\EmployeeModel::yearsOfService($emp['date_hired']) ?></td>
                        <td>
                            <span class="badge <?= $emp['status'] === 'active' ? 'badge-active' : 'badge-inactive' ?>">
                                <?= ucfirst($emp['status']) ?>
                            </span>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="<?= site_url('employees/view/' . $emp['id']) ?>"
                                   class="btn btn-sm btn-outline-info" title="View">
                                    <i class="fa fa-eye"></i>
                                </a>
                                <?php if (can_do('employees', 'edit')): ?>
                                <a href="<?= site_url('employees/edit/' . $emp['id']) ?>"
                                   class="btn btn-sm btn-outline-primary" title="Edit">
                                    <i class="fa fa-pen-to-square"></i>
                                </a>
                                <?php endif; ?>
                                <a href="<?= site_url('employees/dtr/' . $emp['id']) ?>"
                                   class="btn btn-sm btn-outline-secondary" title="DTR">
                                    <i class="fa fa-calendar-days"></i>
                                </a>
                                <?php if (can_do('employees', 'delete')): ?>
                                <a href="<?= site_url('employees/delete/' . $emp['id']) ?>"
                                   class="btn btn-sm btn-outline-danger" title="Delete"
                                   data-confirm="Delete employee <?= esc($emp['full_name']) ?>?">
                                    <i class="fa fa-trash"></i>
                                </a>
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

<?= $this->endSection() ?>
