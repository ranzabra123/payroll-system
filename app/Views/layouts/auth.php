<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <?php $_companyName = setting('company_name', 'PayrollPH'); 
          $_logoUrl = setting_logo_url(); ?>
    <title><?= esc($title ?? 'Login') ?> – <?= esc($_companyName) ?></title>
    <?php if ($_logoUrl): ?><link rel="icon" type="image/png" href="<?= esc($_logoUrl) ?>"><?php endif; ?>
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.min.css') ?>"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous"/>
    <link rel="stylesheet" href="<?= base_url('assets/css/custom.css') ?>"/>
    <style>
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

<div class="login-page">
    <?= $this->renderSection('content') ?>
</div>
<script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
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
</body>
</html>
