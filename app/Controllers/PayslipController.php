<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\PayrollDetailModel;
use App\Models\PayrollModel;
use App\Models\AuditLogModel;
use App\Models\EmployeeDeductionModel;

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

        $cutoff    = (int) $payroll['cutoff'];
        $empDedModel = new EmployeeDeductionModel();
        $empDeds   = $empDedModel->getForPayslipDisplay(
            (int) $detail['employee_id'], $cutoff, $payroll['period_end']
        );

        return view('payslip/view', [
            'title'   => 'Payslip',
            'detail'  => $detail,
            'payroll' => $payroll,
            'empDeds' => $empDeds,
        ]);
    }

    /** Bulk payslip list for a payroll run. */
    public function bulk(int $payrollId)
    {
        $payroll = (new PayrollModel())->find($payrollId);
        if (! $payroll) {
            return redirect()->to(site_url('payroll'))->with('error', 'Payroll not found.');
        }

        $branchId    = (int) ($this->request->getGet('branch_id') ?? 0);
        $details     = (new PayrollDetailModel())->getByPayroll($payrollId, $branchId);
        $branches    = (new \App\Models\BranchModel())->getActiveList();
        $cutoff      = (int) $payroll['cutoff'];
        $periodEnd   = $payroll['period_end'];
        $empDedModel = new EmployeeDeductionModel();

        // Build a map of employee_id => [deduction records] for payslip display
        $empDedsMap = [];
        foreach ($details as $d) {
            $empId = (int) $d['employee_id'];
            if (! isset($empDedsMap[$empId])) {
                $empDedsMap[$empId] = $empDedModel->getForPayslipDisplay($empId, $cutoff, $periodEnd);
            }
        }

        return view('payslip/bulk', [
            'title'      => 'Bulk Payslips – ' . PayrollModel::periodLabel($payroll),
            'payroll'    => $payroll,
            'details'    => $details,
            'branches'   => $branches,
            'selBranch'  => $branchId,
            'empDedsMap' => $empDedsMap,
        ]);
    }
}
