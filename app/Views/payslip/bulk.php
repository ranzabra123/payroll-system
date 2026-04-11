<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Bulk Payslips – <?= esc($title) ?></title>
    <?php $_logoUrl = setting_logo_url();
          $_companyName = setting('company_name', 'PayrollPH'); 
          if ($_logoUrl): ?><link rel="icon" type="image/png" href="<?= esc($_logoUrl) ?>"><?php endif; ?>
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap.min.css') ?>"/>
    <link rel="stylesheet" href="<?= base_url('assets/css/custom.css') ?>"/>
    <style>
        @page {
            size: A4 landscape;
            margin: 0;
        }
        
        @media print {
            .no-print { display: none !important; }
            body { 
                margin: 0; 
                padding: 0.1in;
                background: white;
                height: auto;
                width: 100%;
            }
            
            .payslip-grid {
                display: grid;
                grid-template-columns: 1fr 1fr 1fr;
                grid-auto-rows: minmax(auto, 1fr);
                gap: 0.08rem;
                margin: 0;
                padding: 0;
                width: 100%;
                height: auto;
            }
            
            .payslip-grid.page-break-after {
                page-break-after: always;
            }
            
            .payslip-wrapper {
                margin: 0 !important;
                padding: 0.15rem;
                page-break-inside: avoid;
                font-size: 0.52rem;
                border: 0.5px solid #ccc;
                background: white;
                box-sizing: border-box;
                width: 100%;
                height: auto;
                display: flex;
                flex-direction: column;
            }
            
            .payslip-header { 
                padding: 0.1rem 0; 
                margin-bottom: 0.08rem;
                border-bottom: 0.5px solid #ddd;
            }
            
            .payslip-header h5 { 
                font-size: 0.6rem; 
                margin-bottom: 0 !important;
                margin-top: 0 !important;
            }
            
            .payslip-header small { 
                font-size: 0.45rem; 
                display: block;
                margin-top: 0.02rem;
            }
            
            .payslip-header .row { margin: 0; }
            .payslip-header .col { padding: 0; }
            
            .payslip-row { 
                display: flex; 
                justify-content: space-between; 
                margin-bottom: 0.04rem;
                font-size: 0.48rem;
            }
            
            table { 
                margin-bottom: 0 !important;
                width: 100%;
            }
            
            table td { 
                padding: 0.04rem !important; 
                line-height: 0.95;
                font-size: 0.48rem;
            }
            
            h6 { 
                font-size: 0.45rem !important; 
                margin-bottom: 0.06rem !important;
                font-weight: 700;
            }
            
            .border-bottom, .border-end, .border-top { 
                border-width: 0.5px !important; 
            }
            
            .text-muted { color: #666; }
        }
        
        @media screen {
            .payslip-grid {
                display: grid;
                grid-template-columns: 1fr 1fr 1fr;
                gap: 1rem;
            }
            .payslip-wrapper { margin-bottom: 0; }
            /* Override custom.css large header on screen view */
            .payslip-header {
                padding: 0.6rem 1rem;
            }
        }

        /* Logo wrapper — no background */
        .logo-shadow-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            flex-shrink: 0;
            overflow: hidden;
        }

        .logo-shadow-wrapper img {
            max-height: 36px;
            max-width: 36px;
            width: auto;
            height: auto;
            object-fit: contain;
        }
    </style>
</head>
<body style="background:#f1f5f9;">

<?php $_companyName = setting('company_name', 'PayrollPH'); ?>
<?php $_logoUrl = setting_logo_url(); ?>
<?php $_tagline = setting('company_tagline', 'Payroll Management System'); ?>

<div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-3 no-print flex-wrap gap-2">
        <h5 class="mb-0"><?= esc($title) ?> (<?= count($details) ?> payslips)</h5>
        <div class="d-flex align-items-center gap-2">
            <input type="text" id="payslip-search" class="form-control form-control-sm" placeholder="Search employee…" style="width:220px;" oninput="filterPayslips(this.value)">
            <button onclick="window.print()" class="btn btn-primary btn-sm">
                <i class="fa fa-print me-1"></i>Print All
            </button>
            <a href="<?= site_url('payroll/view/' . $payroll['id']) ?>" class="btn btn-outline-secondary btn-sm">← Back</a>
        </div>
    </div>

    <?php 
    $totalSlips = count($details);
    $pagesNeeded = ceil($totalSlips / 6);
    
    for ($page = 0; $page < $pagesNeeded; $page++):
        $startIdx = $page * 6;
        $endIdx = min($startIdx + 6, $totalSlips);
        $pageDetails = array_slice($details, $startIdx, $endIdx - $startIdx);
    ?>
    <div class="payslip-grid <?= ($page < $pagesNeeded - 1) ? 'page-break-after' : '' ?>">
        <?php foreach ($pageDetails as $d): ?>
        <div class="payslip-wrapper" data-name="<?= esc(strtolower($d['full_name'])) ?>">
        <div class="payslip-header">
            <div class="row align-items-center">
                <div class="col">
                    <div style="display: flex; align-items: center; gap: 0.3rem;">
                        <?php if ($_logoUrl): ?>
                        <div class="logo-shadow-wrapper">
                            <img src="<?= esc($_logoUrl) ?>" alt="Logo">
                        </div>
                        <?php endif; ?>
                        <div>
                            <h19 class="mb-0 fw-bold lh-1" style="display:block;"><?= esc($_companyName) ?></h19>
                        </div>
                    </div>
                </div>
                <div class="col text-end"><div class="fw-bold small">PAYSLIP</div><small class="opacity-75" style="font-size:0.7rem;"><?= \App\Models\PayrollModel::periodLabel($payroll) ?></small></div>
            </div>
        </div>
        <div style="padding: 0.15rem 0.3rem; border-bottom: 1px solid #dee2e6;">
            <div class="row g-2">
                <div class="col-md-6">
                    <table class="table table-sm table-borderless mb-0" style="font-size:0.5rem;">
                        <tr><td class="text-muted" style="width:60px;">Employee</td><td class="fw-semibold">: <?= esc($d['full_name']) ?></td></tr>
                        <tr><td class="text-muted">ID</td><td class="font-monospace" style="font-size:0.48rem;">: <?= esc($d['employee_code']) ?></td></tr>
                        <tr><td class="text-muted">Position</td><td style="font-size:0.5rem;">: <?= esc($d['position']) ?></td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-sm table-borderless mb-0" style="font-size:0.5rem;">
                        <tr><td class="text-muted" style="width:70px;">Period</td><td style="font-size:0.5rem;">: <?= date('M j', strtotime($payroll['period_start'])) ?>–<?= date('M j, Y', strtotime($payroll['period_end'])) ?></td></tr>
                        <tr><td class="text-muted">Salary</td><td class="fw-semibold" style="font-size:0.5rem;">: ₱ <?= number_format($d['monthly_salary'], 2) ?></td></tr>
                        <tr><td class="text-muted">Days</td><td style="font-size:0.5rem;">: <?= $d['days_worked'] ?>/<?= $d['working_days'] ?></td></tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="row g-0">
            <div class="col-md-6" style="padding: 0.15rem 0.25rem; border-right: 1px solid #dee2e6;">
                <h6 class="text-uppercase small text-muted mb-2" style="font-size:0.52rem;margin-bottom:0.08rem!important;">Earnings</h6>
                <div class="payslip-row" style="font-size:0.52rem;margin-bottom:0.05rem;"><span>Basic Pay</span><span>₱ <?= number_format($d['basic_pay'], 2) ?></span></div>
                <div class="payslip-row" style="font-size:0.52rem;margin-bottom:0.05rem;"><span>OT Pay</span><span><?= $d['overtime_pay'] > 0 ? '₱ ' . number_format($d['overtime_pay'], 2) : '—' ?></span></div>
                <div class="payslip-row payslip-total" style="font-size:0.52rem;border-top:1px solid #dee2e6;padding-top:0.05rem;margin-top:0.05rem;font-weight:bold;"><span>GROSS</span><span class="text-success">₱ <?= number_format($d['gross_pay'], 2) ?></span></div>
            </div>
            <div class="col-md-6" style="padding: 0.15rem 0.25rem;">
                <h6 class="text-uppercase small text-muted mb-2" style="font-size:0.52rem;margin-bottom:0.08rem!important;">Deductions</h6>
                <div class="payslip-row" style="font-size:0.52rem;margin-bottom:0.05rem;"><span>SSS</span><span class="text-danger">₱ <?= number_format($d['sss_deduction'], 2) ?></span></div>
                <div class="payslip-row" style="font-size:0.52rem;margin-bottom:0.05rem;"><span>PhilHealth</span><span class="text-danger">₱ <?= number_format($d['philhealth_deduction'], 2) ?></span></div>
                <div class="payslip-row" style="font-size:0.52rem;margin-bottom:0.05rem;"><span>Pag-IBIG</span><span class="text-danger">₱ <?= number_format($d['pagibig_deduction'], 2) ?></span></div>
                <?php if (($d['other_deductions'] ?? 0) > 0): ?><div class="payslip-row" style="font-size:0.52rem;margin-bottom:0.05rem;"><span>Other Ded.</span><span class="text-danger">₱ <?= number_format($d['other_deductions'], 2) ?></span></div><?php endif; ?>
                <?php if (($d['benefits_deduction'] ?? 0) > 0): ?><div class="payslip-row" style="font-size:0.52rem;margin-bottom:0.05rem;"><span>Benefits</span><span class="text-danger">₱ <?= number_format($d['benefits_deduction'], 2) ?></span></div><?php endif; ?>
                <div class="payslip-row payslip-total" style="font-size:0.52rem;border-top:1px solid #dee2e6;padding-top:0.05rem;margin-top:0.05rem;font-weight:bold;"><span>DED.</span><span class="text-danger">₱ <?= number_format($d['total_deductions'], 2) ?></span></div>
            </div>
        </div>
        <div style="padding: 0.1rem 0.2rem; text-align: center; border-top: 1px solid #dee2e6;">
            <div class="text-muted" style="font-size:0.5rem;">NET PAY</div>
            <div class="fw-bold" style="font-size:0.8rem;color:var(--primary);">₱ <?= number_format($d['net_pay'], 2) ?></div>
        </div>
        </div> <!-- close payslip-wrapper -->
    <?php endforeach; ?>
    </div>
    <?php endfor; ?>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous"/>
<script>
function filterPayslips(query) {
    const q = query.trim().toLowerCase();
    document.querySelectorAll('.payslip-wrapper').forEach(function(el) {
        const name = (el.dataset.name || '').toLowerCase();
        el.style.display = (!q || name.includes(q)) ? '' : 'none';
    });
}
</script>
</body>
</html>
