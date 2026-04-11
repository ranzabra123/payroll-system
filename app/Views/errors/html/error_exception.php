<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Error') ?></title>
    <style>
        body{font-family:Arial,sans-serif;background:#f8fafc;color:#0f172a;margin:0}
        .wrap{max-width:900px;margin:5vh auto;padding:0 16px}
        .card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;box-shadow:0 8px 24px rgba(15,23,42,.08)}
        h1{margin:0 0 8px;font-size:1.3rem}
        .meta{color:#475569;font-size:.92rem}
        pre{margin-top:14px;background:#0f172a;color:#e2e8f0;padding:12px;border-radius:10px;overflow:auto}
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1><?= esc($type ?? 'Exception') ?> (<?= esc((string) ($code ?? 500)) ?>)</h1>
        <p><?= esc($message ?? 'Unexpected error.') ?></p>
        <p class="meta">File: <?= esc($file ?? '') ?> : <?= esc((string) ($line ?? '')) ?></p>
        <?php if (! empty($trace)): ?>
        <pre><?= esc(is_array($trace) ? print_r($trace, true) : (string) $trace) ?></pre>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
