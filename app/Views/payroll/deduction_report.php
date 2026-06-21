<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Deductions Report — <?= esc($label) ?></title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, sans-serif; font-size: 11pt; color: #111; background: #fff; }

    .page-header { border-bottom: 2px solid #000; padding-bottom: .4rem; margin-bottom: 1.2rem; }
    .page-header h1 { font-size: 14pt; font-weight: 700; }
    .page-header h2 { font-size: 11pt; font-weight: 600; margin-top: .2rem; }
    .page-header p  { font-size: 9pt; color: #555; margin-top: .15rem; }

    .group { margin-bottom: 1.4rem; }
    .group-title {
        font-size: 11pt;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        background: #f0f0f0;
        padding: 3px 6px;
        border-left: 4px solid #333;
        margin-bottom: .3rem;
    }

    table { width: 100%; border-collapse: collapse; font-size: 10pt; }
    thead tr { border-bottom: 1px solid #999; }
    th { text-align: left; padding: 3px 6px; font-weight: 600; font-size: 9pt; color: #444; }
    th.amt { text-align: right; }
    td { padding: 3px 6px; border-bottom: .5px solid #e8e8e8; }
    td.num { text-align: right; font-variant-numeric: tabular-nums; }
    tbody tr:hover { background: #fafafa; }

    .group-total td { font-weight: 700; border-top: 1.5px solid #555; background: #f9f9f9; }

    .no-print { }
    @media print {
        .no-print { display: none !important; }
        body { font-size: 10pt; }
        .group { page-break-inside: avoid; }
        @page { size: A4 portrait; margin: 12mm; }
    }
</style>
</head>
<body>

<div class="no-print" style="padding:10px;background:#f5f5f5;border-bottom:1px solid #ccc;display:flex;gap:10px;align-items:center;">
    <button onclick="window.print()" style="padding:6px 16px;font-size:11pt;cursor:pointer;">&#128438; Print</button>
    <button onclick="window.close()" style="padding:6px 12px;font-size:11pt;cursor:pointer;">&#10005; Close</button>
</div>

<div style="padding:16px 20px;">

<div class="page-header">
    <?php $_cn = setting('company_name', 'PayrollPH'); ?>
    <h1><?= esc($_cn) ?></h1>
    <h2>Deductions Report &mdash; <?= esc($label) ?></h2>
    <p>
        <?= (int)$payroll['cutoff'] === 1 ? '1st Cutoff' : '2nd Cutoff' ?>
        &bull; Period: <?= date('M j, Y', strtotime($payroll['period_start'])) ?>
        &ndash; <?= date('M j, Y', strtotime($payroll['period_end'])) ?>
        &bull; Generated: <?= date('M j, Y') ?>
    </p>
</div>

<?php if (empty($grouped)): ?>
<p style="color:#888;">No deductions found for this payroll period.</p>
<?php endif; ?>

<?php foreach ($grouped as $typeName => $rows): ?>
<?php $typeTotal = array_sum(array_column($rows, 'amount')); ?>
<div class="group">
    <div class="group-title"><?= esc($typeName) ?> <span style="font-size:9pt;font-weight:400;color:#555;">(<?= count($rows) ?> employee<?= count($rows) !== 1 ? 's' : '' ?> &bull; Total: &#8369; <?= number_format($typeTotal, 2) ?>)</span></div>
    <table>
        <thead>
            <tr>
                <th style="width:60%;">Employee</th>
                <th class="amt">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= esc($row['employee']) ?></td>
                <td class="num">&#8369; <?= number_format($row['amount'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="group-total">
                <td>Total &mdash; <?= esc($typeName) ?></td>
                <td class="num">&#8369; <?= number_format($typeTotal, 2) ?></td>
            </tr>
        </tfoot>
    </table>
</div>
<?php endforeach; ?>

<?php
// Grand total
$grandTotal = 0;
foreach ($grouped as $rows) { $grandTotal += array_sum(array_column($rows, 'amount')); }
?>
<?php if (! empty($grouped)): ?>
<div style="border-top:2.5px solid #000;margin-top:.5rem;padding-top:.5rem;font-weight:700;font-size:11pt;text-align:right;">
    Grand Total Deductions: &#8369; <?= number_format($grandTotal, 2) ?>
</div>
<?php endif; ?>

</div><!-- /padding -->
</body>
</html>
