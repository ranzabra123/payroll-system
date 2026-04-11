<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Error</title>
    <style>
        body{font-family:Arial,sans-serif;background:#f8fafc;color:#0f172a;margin:0}
        .wrap{max-width:760px;margin:8vh auto;padding:0 16px}
        .card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;box-shadow:0 8px 24px rgba(15,23,42,.08)}
        h1{margin:0 0 12px;font-size:1.4rem}
        p{margin:.35rem 0}
        a{color:#2563eb;text-decoration:none}
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>Something went wrong.</h1>
        <p>Please try again in a moment.</p>
        <p><a href="<?= site_url('dashboard') ?>">Go to Dashboard</a></p>
    </div>
</div>
</body>
</html>
