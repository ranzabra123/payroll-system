<?php
$_companyName = setting('company_name', 'PayrollPH');
$_tagline     = setting('company_tagline', 'Management System');
$_sidebarBg   = setting('sidebar_bg',   '#1e293b');
$_sidebarText = setting('sidebar_text', '#94a3b8');
$_accentColor = setting('accent_color', '#2563eb');
$_topbarBg    = setting('topbar_bg',    '#ffffff');
$_logoUrl     = setting_logo_url();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?= esc($title ?? 'Payroll') ?> – <?= esc($_companyName) ?></title>
    <?php if ($_logoUrl): ?><link rel="icon" type="image/png" href="<?= esc($_logoUrl) ?>"><?php endif; ?>
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.min.css') ?>"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous"/>
    <link rel="stylesheet" href="<?= base_url('assets/css/custom.css') ?>"/>
    <style>
        :root {
            --sidebar-bg:    <?= esc($_sidebarBg) ?>;
            --sidebar-text:  <?= esc($_sidebarText) ?>;
            --accent-color:  <?= esc($_accentColor) ?>;
            --topbar-bg:     <?= esc($_topbarBg) ?>;
        }
        #sidebar { background: var(--sidebar-bg) !important; }
        #sidebar .nav-link, #sidebar .nav-section, #sidebar .sidebar-footer { color: var(--sidebar-text) !important; }
        #sidebar .nav-link:hover, #sidebar .nav-link.active { background: var(--accent-color) !important; color: #fff !important; }
        #sidebar .sidebar-brand h4 { color: #fff !important; }
        #topbar { background: var(--topbar-bg) !important; }
        .btn-primary { background-color: var(--accent-color) !important; border-color: var(--accent-color) !important; }
        a { color: var(--accent-color); }
        .flash-container {
            position: fixed;
            top: 1rem;
            right: 1rem;
            width: min(520px, calc(100vw - 2rem));
            z-index: 2000;
        }
        .flash-container .alert {
            box-shadow: 0 10px 24px rgba(15, 23, 42, .18);
            border-width: 1px;
        }
    </style>
</head>
<body>

<!-- ============ SIDEBAR ============ -->
<nav id="sidebar">
    <div class="sidebar-brand">
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
            <?php if ($_logoUrl): ?>
            <img src="<?= esc($_logoUrl) ?>" alt="Logo"
                 style="max-height:40px;max-width:100px;object-fit:contain;flex-shrink:0;"/>
            <?php else: ?>
            <i class="fa-solid fa-peso-sign" style="font-size: 1.5rem;"></i>
            <?php endif; ?>
            <h4 style="margin: 0; font-size: 0.95rem;"><?= esc($_companyName) ?></h4>
        </div>
        <small><?= esc($_tagline) ?></small>
    </div>

    <div class="sidebar-menu">
        <p class="nav-section">Main</p>
        <a class="nav-link" href="<?= site_url('dashboard') ?>">
            <i class="fa fa-gauge-high"></i> Dashboard
        </a>

        <p class="nav-section">People</p>
        <?php if (can_do('employees', 'view')): ?>
        <a class="nav-link" href="<?= site_url('employees') ?>">
            <i class="fa fa-users"></i> Employees
        </a>
        <?php endif; ?>
        <?php if (session()->get('role') === 'admin'): ?>
        <a class="nav-link" href="<?= site_url('users') ?>">
            <i class="fa fa-user-shield"></i> Users
        </a>
        <?php endif; ?>

        <p class="nav-section">Time & Pay</p>
        <?php if (can_do('attendance', 'view')): ?>
        <a class="nav-link" href="<?= site_url('attendance') ?>">
            <i class="fa fa-calendar-check"></i> Attendance
        </a>
        <a class="nav-link" href="<?= site_url('attendance/records') ?>">
            <i class="fa fa-table-list"></i> Attendance Records
        </a>
        <?php endif; ?>
        <?php if (can_do('payroll', 'view')): ?>
        <a class="nav-link" href="<?= site_url('payroll') ?>">
            <i class="fa fa-money-bill-wave"></i> Payroll
        </a>
        <?php endif; ?>

        <p class="nav-section">Finance</p>
        <?php if (can_do('deductions', 'view')): ?>
        <a class="nav-link" href="<?= site_url('deductions') ?>">
            <i class="fa fa-circle-minus"></i> Deductions
        </a>
        <?php endif; ?>
        <?php if (can_do('benefits', 'view')): ?>
        <a class="nav-link" href="<?= site_url('benefits') ?>">
            <i class="fa fa-hand-holding-heart"></i> Benefits
        </a>
        <a class="nav-link" href="<?= site_url('benefits/summary') ?>">
            <i class="fa fa-chart-pie"></i> Benefits Summary
        </a>
        <?php endif; ?>
        <?php if (can_do('special_days', 'view')): ?>
        <a class="nav-link" href="<?= site_url('special-days') ?>">
            <i class="fa fa-calendar-day"></i> Special Days
        </a>
        <?php endif; ?>

        <p class="nav-section">Analytics</p>
        <?php if (can_do('reports', 'view')): ?>
        <a class="nav-link" href="<?= site_url('reports') ?>">
            <i class="fa fa-chart-bar"></i> Reports
        </a>
        <?php endif; ?>

        <?php if (session()->get('role') === 'admin'): ?>
        <p class="nav-section">System</p>
        <a class="nav-link" href="<?= site_url('settings') ?>">
            <i class="fa fa-sliders"></i> Control Panel
        </a>
        <a class="nav-link" href="<?= site_url('logs') ?>">
            <i class="fa fa-list-alt"></i> Audit Logs
        </a>
        <?php endif; ?>
    </div>

    <div class="sidebar-footer">
        <div class="d-flex align-items-center gap-2">
            <div style="width:32px;height:32px;border-radius:50%;background:#2563eb;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.8rem;">
                <?= strtoupper(substr(session()->get('full_name') ?? 'U', 0, 1)) ?>
            </div>
            <div>
                <div style="color:#e2e8f0;font-size:.82rem;font-weight:500;"><?= esc(session()->get('full_name') ?? '') ?></div>
                <div style="font-size:.72rem;"><?= ucfirst(session()->get('role') ?? '') ?></div>
            </div>
        </div>
    </div>
</nav>

<!-- ============ MAIN CONTENT ============ -->
<div id="main-content">

    <!-- Topbar -->
    <div id="topbar">
        <button id="sidebar-toggle" title="Toggle sidebar">
            <i class="fa fa-bars"></i>
        </button>
        <span class="topbar-title"><?= esc($title ?? '') ?></span>
        <div class="user-badge">
            <i class="fa fa-clock text-muted me-1"></i>
            <span class="text-muted small"><?= date('D, M j Y') ?></span>
            <a href="<?= site_url('logout') ?>" class="btn btn-sm btn-outline-danger ms-3">
                <i class="fa fa-right-from-bracket me-1"></i>Logout
            </a>
        </div>
    </div>

    <!-- Flash messages -->
    <div class="flash-container">
        <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa fa-circle-check me-2"></i><?= esc(session()->getFlashdata('success')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa fa-circle-xmark me-2"></i><?= esc(session()->getFlashdata('error')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('alert')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa fa-triangle-exclamation me-2"></i><?= esc(session()->getFlashdata('alert')) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('errors')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa fa-triangle-exclamation me-2"></i>
            <ul class="mb-0">
                <?php foreach (session()->getFlashdata('errors') as $err): ?>
                <li><?= esc($err) ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
    </div>

    <!-- Page body -->
    <div class="page-body">
        <?= $this->renderSection('content') ?>
    </div>

</div><!-- /#main-content -->

<script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= base_url('assets/js/custom.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.flash-container .alert').forEach(function (el) {
        setTimeout(function () {
            const alert = bootstrap.Alert.getOrCreateInstance(el);
            alert.close();
        }, 5000);
    });
});
</script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
