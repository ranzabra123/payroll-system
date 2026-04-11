<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php
$actionColors = [
    'create'             => 'success',
    'add'                => 'success',
    'store'              => 'success',
    'bulk_create'        => 'success',
    'update'             => 'primary',
    'edit'               => 'primary',
    'field_update'       => 'primary',
    'toggle'             => 'secondary',
    'upload_logo'        => 'info',
    'upload'             => 'info',
    'update_general'     => 'primary',
    'update_colors'      => 'primary',
    'finalize'           => 'dark',
    'complete'           => 'dark',
    'mark_complete'      => 'dark',
    'delete'             => 'danger',
    'remove'             => 'danger',
    'delete-by-date'     => 'danger',
    'remove_logo'        => 'danger',
    'login'              => 'teal',
    'logout'             => 'warning',
    'generate'           => 'info',
    'recalculate'        => 'secondary',
];

function actionBadge(string $action, array $map): string {
    // strip permission prefixes e.g. update_permissions_manager → secondary
    if (str_starts_with($action, 'update_permissions')) {
        return '<span class="badge bg-secondary">permissions</span>';
    }
    $color = $map[$action] ?? 'secondary';
    return '<span class="badge bg-' . $color . '">' . esc($action) . '</span>';
}
?>

<style>
@media print {
    @page { size: landscape; margin: 10mm; }
    body * { visibility: hidden; }
    #printArea, #printArea * { visibility: visible; }
    #printArea { position: absolute; inset: 0; padding: 8px; }
    .no-print { display: none !important; }
    .table { font-size: 9px; }
    td, th { padding: 3px 4px !important; }
    .badge { font-size: 8px; padding: 2px 5px; }
}
</style>

<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <h5 class="mb-0 fw-semibold"><i class="fa fa-list-alt me-1"></i> Audit Logs</h5>
    <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">
        <i class="fa fa-print me-1"></i> Print
    </button>
</div>

<!-- Filters -->
<div class="card mb-3 no-print">
    <div class="card-body py-2">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small mb-1 fw-medium">Module</label>
                <select name="module" class="form-select form-select-sm">
                    <option value="">All Modules</option>
                    <?php foreach ($modules as $m): ?>
                        <option value="<?= esc($m['module']) ?>" <?= $filters['module'] === $m['module'] ? 'selected' : '' ?>>
                            <?= esc($m['module']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1 fw-medium">Action</label>
                <select name="action" class="form-select form-select-sm">
                    <option value="">All Actions</option>
                    <?php foreach (['login','logout','create','update','delete','generate','finalize','complete','toggle','bulk_create','field_update','delete-by-date','upload_logo','remove_logo','update_general','update_colors'] as $a): ?>
                        <option value="<?= $a ?>" <?= $filters['action'] === $a ? 'selected' : '' ?>>
                            <?= $a ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1 fw-medium">Username</label>
                <input type="text" name="username" class="form-control form-control-sm"
                       placeholder="e.g. admin" value="<?= esc($filters['username']) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1 fw-medium">Date From</label>
                <input type="date" name="date_from" class="form-control form-control-sm"
                       value="<?= esc($filters['date_from']) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1 fw-medium">Date To</label>
                <input type="date" name="date_to" class="form-control form-control-sm"
                       value="<?= esc($filters['date_to']) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-1 fw-medium">Search</label>
                <div class="input-group input-group-sm">
                    <input type="text" name="q" class="form-control" placeholder="Keyword…"
                           value="<?= esc($filters['q']) ?>">
                    <button class="btn btn-primary btn-sm" type="submit">
                        <i class="fa fa-search"></i>
                    </button>
                </div>
            </div>
            <?php if (array_filter($filters)): ?>
            <div class="col-12 text-end">
                <a href="<?= site_url('logs') ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="fa fa-times me-1"></i> Clear Filters
                </a>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Result count -->
<div class="d-flex justify-content-between align-items-center mb-2 no-print">
    <small class="text-muted">
        Showing <?= count($logs) ?> of <?= number_format($total) ?> records
    </small>
    <?php if ($pages > 1): ?>
    <nav>
        <ul class="pagination pagination-sm mb-0">
            <?php for ($p = 1; $p <= $pages; $p++): ?>
                <?php $q = array_merge($filters, ['page' => $p]); ?>
                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                    <a class="page-link" href="<?= site_url('logs?') . http_build_query($q) ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<!-- Table -->
<div id="printArea">
    <div class="d-none d-print-block mb-2">
        <h5 class="fw-bold mb-0">Audit Logs</h5>
        <small class="text-muted">Printed: <?= date('F j, Y g:i A') ?></small>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-3">#</th>
                        <th>Date &amp; Time</th>
                        <th>Username</th>
                        <th>Module</th>
                        <th>Action</th>
                        <th>Summary / Details</th>
                        <th>Record ID</th>
                        <th>IP Address</th>
                        <th class="no-print">URL</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <i class="fa fa-inbox fa-2x mb-2 d-block"></i>
                                No audit log records found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $rowNum = ($page - 1) * $perPage + 1; ?>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td class="ps-3 text-muted small"><?= $rowNum++ ?></td>
                            <td class="text-nowrap small">
                                <span class="fw-medium"><?= date('M j, Y', strtotime($log['created_at'])) ?></span><br>
                                <span class="text-muted"><?= date('g:i:s A', strtotime($log['created_at'])) ?></span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <i class="fa fa-user me-1"></i><?= esc($log['username'] ?? '—') ?>
                                </span>
                            </td>
                            <td><span class="fw-medium small"><?= esc($log['module']) ?></span></td>
                            <td><?= actionBadge($log['action'], $actionColors) ?></td>
                            <td class="small" style="max-width:340px;">
                                <?php if (! empty($log['summary'])): ?>
                                    <div><?= esc($log['summary']) ?></div>
                                <?php endif; ?>
                                <?php
                                    $old = $log['old_values'] ? json_decode($log['old_values'], true) : null;
                                    $new = $log['new_values'] ? json_decode($log['new_values'], true) : null;
                                ?>
                                <?php if ($old || $new): ?>
                                <details class="no-print mt-1">
                                    <summary class="text-muted" style="cursor:pointer;font-size:.75rem;">Show raw data</summary>
                                    <div class="mt-1 d-flex gap-2" style="font-size:.72rem;">
                                        <?php if ($old): ?>
                                        <div class="flex-fill">
                                            <div class="text-muted fw-bold mb-1">Before</div>
                                            <?php foreach ($old as $k => $v): ?>
                                                <?php if (in_array($k, ['password', 'created_at', 'updated_at'])) continue; ?>
                                                <div><span class="text-danger"><?= esc($k) ?>:</span> <?= esc((string)$v) ?></div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($new): ?>
                                        <div class="flex-fill">
                                            <div class="text-muted fw-bold mb-1">After</div>
                                            <?php foreach ($new as $k => $v): ?>
                                                <?php if (in_array($k, ['password', 'created_at', 'updated_at'])) continue; ?>
                                                <?php $changed = $old && array_key_exists($k, $old) && (string)$old[$k] !== (string)$v; ?>
                                                <div class="<?= $changed ? 'text-success fw-semibold' : '' ?>">
                                                    <span class="<?= $changed ? 'text-success' : 'text-primary' ?>"><?= esc($k) ?>:</span> <?= esc((string)$v) ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </details>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted small"><?= $log['record_id'] ? '#' . $log['record_id'] : '—' ?></td>
                            <td class="small text-muted text-nowrap"><?= esc($log['ip_address'] ?? '—') ?></td>
                            <td class="small no-print" style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                <?php if (! empty($log['url'])): ?>
                                    <a href="<?= esc($log['url']) ?>" class="text-muted" target="_blank"
                                       title="<?= esc($log['url']) ?>"><?= esc($log['url']) ?></a>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Bottom pagination -->
<?php if ($pages > 1): ?>
<div class="d-flex justify-content-center mt-3 no-print">
    <nav>
        <ul class="pagination pagination-sm mb-0">
            <?php for ($p = 1; $p <= $pages; $p++): ?>
                <?php $q = array_merge($filters, ['page' => $p]); ?>
                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                    <a class="page-link" href="<?= site_url('logs?') . http_build_query($q) ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
</div>
<?php endif; ?>

<?= $this->endSection() ?>
