<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php $_companyName = setting('company_name', 'PayrollPH');
          $_logoUrl = setting_logo_url(); ?>
    <title>Payroll Report - <?= esc($label) ?> – <?= esc($_companyName) ?></title>
    <?php if ($_logoUrl): ?><link rel="icon" type="image/png" href="<?= esc($_logoUrl) ?>"><?php endif; ?>
    <link href="<?= site_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= site_url('assets/css/fontawesome.min.css') ?>" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            background: #fff;
        }

        @page {
            margin: 0;
        }

        @media print {
            body {
                background: white;
                padding: 0.1in;
            }
            .no-print {
                display: none !important;
            }
            a {
                text-decoration: none;
                color: #000;
            }
            table {
                border-collapse: collapse;
            }
            thead {
                display: table-header-group;
            }
            tfoot {
                display: table-footer-group;
            }
            tr {
                page-break-inside: avoid;
            }
        }

        .print-header {
            text-align: center;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #000;
            padding-bottom: 0.75rem;
        }

        .company-name {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 0.25rem;
        }

        .report-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .report-period {
            font-size: 0.95rem;
            color: #666;
        }

        .print-content {
            margin-top: 1rem;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .summary-item {
            border: 1px solid #ddd;
            padding: 0.75rem;
            text-align: center;
        }

        .summary-label {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 0.25rem;
        }

        .summary-value {
            font-size: 1.1rem;
            font-weight: bold;
            color: #000;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0.5rem;
        }

        th {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 0.5rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.85rem;
        }

        td {
            border: 1px solid #dee2e6;
            padding: 0.5rem;
            font-size: 0.85rem;
        }

        tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        tfoot td {
            background-color: #e9ecef;
            font-weight: 600;
            border: 1px solid #dee2e6;
        }

        .text-end {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .text-success {
            color: #198754;
        }

        .text-danger {
            color: #dc3545;
        }

        .text-primary {
            color: #0d6efd;
        }

        /* Paper size specific styles */
        body.paper-a4 {
            width: 11.69in;
            height: 8.27in;
        }

        body.paper-short {
            width: 11in;
            height: 8.5in;
        }

        body.paper-legal {
            width: 14in;
            height: 8.5in;
        }

        .print-footer {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 0.8rem;
            color: #666;
        }

        .employee-name {
            font-weight: 600;
        }

        .employee-code {
            font-size: 0.8rem;
            color: #666;
        }
    </style>
</head>
<body>
    <?php $_companyName = setting('company_name', 'PayrollPH'); ?>
    <?php $_tagline = setting('company_tagline', ''); ?>
    <?php $_logoUrl = setting_logo_url(); ?>
    
    <div class="print-header">
        <div style="display: flex; align-items: center; gap: 0.75rem; justify-content: center; margin-bottom: 0.5rem;">
            <?php if ($_logoUrl): ?>
            <img src="<?= esc($_logoUrl) ?>" alt="Logo" style="max-height: 60px; flex-shrink: 0;">
            <?php endif; ?>
            <div>
                <div class="company-name"><?= esc($_companyName) ?></div>
                <?php if ($_tagline): ?>
                <div class="report-period" style="font-style: italic; margin-top: 0.25rem;"><?= esc($_tagline) ?></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="report-title">Payroll Report</div>
        <div class="report-period"><?= esc($label) ?></div>
        <div class="report-period">Period: <?= date('M j, Y', strtotime($payroll['period_start'])) ?> – <?= date('M j, Y', strtotime($payroll['period_end'])) ?></div>
    </div>

    <div class="print-content">
        <!-- Summary -->
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-label">Total Gross Pay</div>
                <div class="summary-value text-success">₱ <?= number_format(round($payroll['total_gross']), 2) ?></div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Total Deductions</div>
                <div class="summary-value text-danger">₱ <?= number_format(round($payroll['total_deductions']), 2) ?></div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Total Net Pay</div>
                <div class="summary-value text-primary">₱ <?= number_format(round($payroll['total_net']), 2) ?></div>
            </div>
        </div>

        <!-- Details Table -->
        <table>
            <thead>
                <tr>
                    <th>Employee</th>
                    <th class="text-center">Days<br/>Worked</th>
                    <th class="text-end">Basic Pay</th>
                    <th class="text-end">Gross</th>
                    <th class="text-end">SSS</th>
                    <th class="text-end">PhilHealth</th>
                    <th class="text-end">Pag-IBIG</th>
                    <th class="text-end">Benefits</th>
                    <th class="text-end">Total Ded.</th>
                    <th class="text-end">Net Pay</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($details)): ?>
                    <?php foreach ($details as $d): ?>
                    <tr>
                        <td>
                            <div class="employee-name"><?= esc($d['full_name']) ?></div>
                            <div class="employee-code"><?= esc($d['employee_code']) ?></div>
                        </td>
                        <td class="text-center">
                            <?= $d['days_worked'] ?>/<?= $d['working_days'] ?>
                            <div style="font-size: 0.7rem; color: #666;">
                                <?= $d['whole_days'] ?>W <?= $d['half_days'] ?>H <?= $d['absent_days'] ?>A
                            </div>
                        </td>
                        <td class="text-end">₱ <?= number_format(round($d['basic_pay']), 2) ?></td>
                        <td class="text-end text-success">₱ <?= number_format(round($d['gross_pay']), 2) ?></td>
                        <td class="text-end text-danger" style="font-size: 0.8rem;">₱ <?= number_format(round($d['sss_deduction']), 2) ?></td>
                        <td class="text-end text-danger" style="font-size: 0.8rem;">₱ <?= number_format(round($d['philhealth_deduction']), 2) ?></td>
                        <td class="text-end text-danger" style="font-size: 0.8rem;">₱ <?= number_format(round($d['pagibig_deduction']), 2) ?></td>
                        <td class="text-end text-danger" style="font-size: 0.8rem;">₱ <?= number_format(round($d['benefits_deduction'] ?? 0), 2) ?></td>
                        <td class="text-end text-danger" style="font-weight: 600;">₱ <?= number_format(round($d['total_deductions']), 2) ?></td>
                        <td class="text-end" style="font-weight: bold; color: #0d6efd;">₱ <?= number_format(round($d['net_pay']), 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5">Totals</td>
                    <td class="text-end">₱ <?= number_format(round($payroll['total_gross']), 2) ?></td>
                    <td class="text-end" colspan="4"></td>
                    <td class="text-end">₱ <?= number_format(round($payroll['total_deductions']), 2) ?></td>
                    <td class="text-end">₱ <?= number_format(round($payroll['total_net']), 2) ?></td>
                </tr>
            </tfoot>
        </table>

        <div class="print-footer">
            <p>Generated on <?= date('M j, Y \a\t g:i A') ?></p>
            <p>Status: <?= ucfirst($payroll['status']) ?> | Working Days: <?= $payroll['working_days'] ?></p>
        </div>
    </div>

    <script>
        // Get paper size from URL or default to A4
        const paperSize = new URLSearchParams(window.location.search).get('paperSize') || 'a4';
        document.body.classList.add('paper-' + paperSize);
        
        // Auto-print on page load
        window.addEventListener('load', function() {
            setTimeout(() => window.print(), 500);
        });
    </script>
</body>
</html>
