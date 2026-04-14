<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-semibold">Benefits</h5>
    <?php if (can_do('benefits', 'add')): ?>
    <a href="<?= site_url('benefits/create') ?>" class="btn btn-primary btn-sm">
        <i class="fa fa-plus me-1"></i>Add Benefit
    </a>
    <?php endif; ?>
</div>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success py-2"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger py-2"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Benefit Name</th>
                        <th>Description</th>
                        <th class="text-end">Employee Share</th>
                        <th class="text-end">Company Contribution</th>
                        <th class="text-end">Monthly Total</th>
                        <th class="text-center">Assignments</th>
                        <th class="text-center">Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($benefits)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No benefits defined yet.</td></tr>
                <?php else: ?>
                <?php foreach ($benefits as $b): ?>
                <tr>
                    <td class="fw-semibold"><?= esc($b['name']) ?></td>
                    <td class="text-muted small"><?= esc($b['description'] ?: '—') ?></td>
                    <td class="text-end">₱ <?= number_format($b['employee_share'], 2) ?></td>
                    <td class="text-end">₱ <?= number_format($b['employer_share'], 2) ?></td>
                    <td class="text-end fw-semibold">₱ <?= number_format($b['employee_share'] + $b['employer_share'], 2) ?></td>
                    <td class="text-center">
                        <a href="<?= site_url('benefits/assign/' . $b['id']) ?>"
                           class="badge bg-primary text-decoration-none">
                            <?= (int) $b['assignment_count'] ?> assigned
                        </a>
                    </td>
                    <td class="text-center">
                        <span class="badge <?= $b['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                            <?= $b['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="<?= site_url('benefits/assign/' . $b['id']) ?>"
                               class="btn btn-sm btn-outline-info" title="Manage Assignments">
                               <i class="fa fa-users"></i></a>
                            <?php if (can_do('benefits', 'edit')): ?>
                            <a href="<?= site_url('benefits/edit/' . $b['id']) ?>"
                               class="btn btn-sm btn-outline-primary" title="Edit">
                               <i class="fa fa-pen-to-square"></i></a>
                            <?php endif; ?>
                            <?php if (can_do('benefits', 'delete')): ?>
                            <a href="<?= site_url('benefits/delete/' . $b['id']) ?>"
                               class="btn btn-sm btn-outline-danger" title="Delete"
                               onclick="return confirm('Delete this benefit and all its assignments?')">
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

<?= $this->endSection() ?>
