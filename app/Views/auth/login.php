<?= $this->extend('layouts/auth') ?>
<?= $this->section('content') ?>

<?php
$_companyName = setting('company_name', 'PayrollPH');
$_tagline = setting('company_tagline', 'Payroll Management System');
$_logoUrl = setting_logo_url();
?>

<div class="login-card">
    <div class="login-header">
        <div style="display: flex; align-items: center; gap: 0.75rem; justify-content: center; margin-bottom: 0.75rem;">
            <?php if ($_logoUrl): ?>
            <img src="<?= esc($_logoUrl) ?>" alt="<?= esc($_companyName) ?> Logo"
                 style="max-height:64px;max-width:150px;object-fit:contain;flex-shrink:0;"/>
            <?php else: ?>
            <div style="font-size:2.5rem;">
                <i class="fa-solid fa-peso-sign"></i>
            </div>
            <?php endif; ?>
            <div>
                <h3 class="fw-bold mb-0"><?= esc($_companyName) ?></h3>
                <p class="mb-0 opacity-75 small"><?= esc($_tagline) ?></p>
            </div>
        </div>
    </div>
    <div class="login-body">
        <form action="<?= site_url('login') ?>" method="POST">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label" for="username">
                    <i class="fa fa-user text-muted me-1"></i>Username
                </label>
                <input type="text" name="username" id="username"
                       class="form-control"
                       value="<?= old('username') ?>"
                       placeholder="Enter username"
                       required autofocus/>
            </div>
            <div class="mb-4">
                <label class="form-label" for="password">
                    <i class="fa fa-lock text-muted me-1"></i>Password
                </label>
                <div class="input-group">
                    <input type="password" name="password" id="password"
                           class="form-control"
                           placeholder="Enter password"
                           required/>
                    <button type="button" class="btn btn-outline-secondary"
                            onclick="togglePw(this)">
                        <i class="fa fa-eye"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2">
                <i class="fa fa-right-to-bracket me-2"></i>Sign In
            </button>
        </form>

        <div class="text-center mt-3 text-muted small">
            <i class="fa fa-shield-halved me-1"></i>Secured login
        </div>
    </div>
</div>

<script>
function togglePw(btn) {
    const inp = btn.previousElementSibling;
    inp.type = inp.type === 'password' ? 'text' : 'password';
    btn.querySelector('i').classList.toggle('fa-eye');
    btn.querySelector('i').classList.toggle('fa-eye-slash');
}
</script>

<?= $this->endSection() ?>
