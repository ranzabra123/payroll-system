<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\PayrollDetailModel;
use App\Models\PayrollModel;
use App\Models\AuditLogModel;

/**
 * PayslipController – individual payslip view (print-ready).
 */
class PayslipController extends Controller
{
    /** Payslip for a single payroll_detail row. */
    public function view(int $detailId)
    {
        $detail = (new PayrollDetailModel())->getDetailWithEmployee($detailId);
        if (! $detail) {
            return redirect()->to(site_url('payroll'))->with('error', 'Payslip not found.');
        }

        $payroll = (new PayrollModel())->find($detail['payroll_id']);

        (new AuditLogModel())->logAction('Payslip', 'view', $detailId);

        return view('payslip/view', [
            'title'   => 'Payslip',
            'detail'  => $detail,
            'payroll' => $payroll,
        ]);
    }

    /** Bulk payslip list for a payroll run. */
    public function bulk(int $payrollId)
    {
        $payroll = (new PayrollModel())->find($payrollId);
        if (! $payroll) {
            return redirect()->to(site_url('payroll'))->with('error', 'Payroll not found.');
        }

        $details = (new PayrollDetailModel())->getByPayroll($payrollId);

        return view('payslip/bulk', [
            'title'   => 'Bulk Payslips – ' . PayrollModel::periodLabel($payroll),
            'payroll' => $payroll,
            'details' => $details,
        ]);
    }
}
