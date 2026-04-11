<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\PayrollModel;
use App\Models\PayrollDetailModel;
use App\Models\EmployeeModel;

/**
 * ReportsController – payroll summary reports with CSV export.
 */
class ReportsController extends Controller
{
    public function index()
    {
        $type  = $this->request->getGet('type')  ?? 'cutoff';
        $month = $this->request->getGet('month') ?? date('Y-m');
        $emp   = (int) $this->request->getGet('employee_id');

        $employees = (new EmployeeModel())->getActiveList();
        $data      = [];

        if ($type === 'cutoff') {
            // All payroll runs (finalized)
            $q = (new PayrollModel())->db->table('payroll p')
                ->select([
                    'p.*',
                    'u.full_name AS created_by_name',
                ])
                ->join('users u', 'u.id = p.created_by', 'left')
                ->where('p.status', 'finalized');

            if ($month) {
                $q->where('p.payroll_month', $month);
            }
            $data = $q->orderBy('p.period_start', 'DESC')->get()->getResultArray();
        } elseif ($type === 'monthly') {
            // Monthly totals
            $q = (new PayrollModel())->db->table('payroll')
                ->select("payroll_month, SUM(total_gross) AS total_gross, SUM(total_deductions) AS total_deductions, SUM(total_net) AS total_net, COUNT(*) AS cutoffs")
                ->where('status', 'finalized')
                ->groupBy('payroll_month')
                ->orderBy('payroll_month', 'DESC');
            $data = $q->get()->getResultArray();
        } elseif ($type === 'employee' && $emp) {
            // Employee-level detail
            $data = (new PayrollModel())->db->table('payroll_details pd')
                ->select([
                    'pd.*',
                    'p.payroll_month',
                    'p.cutoff',
                    'p.period_start',
                    'p.period_end',
                    'e.full_name',
                    'e.employee_code',
                    'e.position',
                ])
                ->join('payroll p', 'p.id = pd.payroll_id')
                ->join('employees e', 'e.id = pd.employee_id')
                ->where('pd.employee_id', $emp)
                ->where('p.status', 'finalized')
                ->orderBy('p.period_start', 'DESC')
                ->get()
                ->getResultArray();
        }

        return view('reports/index', [
            'title'     => 'Reports',
            'type'      => $type,
            'month'     => $month,
            'empId'     => $emp,
            'employees' => $employees,
            'data'      => $data,
        ]);
    }

    /** Export current view as CSV. */
    public function exportCsv()
    {
        $type  = $this->request->getGet('type')  ?? 'cutoff';
        $month = $this->request->getGet('month') ?? date('Y-m');
        $emp   = (int) $this->request->getGet('employee_id');

        // Re-run the same query logic
        $rows = [];
        $headers = [];

        if ($type === 'cutoff') {
            $headers = ['Period', 'Cutoff', 'Start', 'End', 'Working Days', 'Total Gross', 'Total Deductions', 'Total Net', 'Status'];
            $payrolls = (new PayrollModel())->where('status', 'finalized')
                ->where('payroll_month', $month)
                ->orderBy('period_start', 'DESC')
                ->findAll();
            foreach ($payrolls as $p) {
                $rows[] = [
                    $p['payroll_month'],
                    $p['cutoff'] == 1 ? '1st (1-15)' : '2nd (16-end)',
                    $p['period_start'],
                    $p['period_end'],
                    $p['working_days'],
                    $p['total_gross'],
                    $p['total_deductions'],
                    $p['total_net'],
                    $p['status'],
                ];
            }
        } elseif ($type === 'monthly') {
            $headers = ['Month', 'Total Gross', 'Total Deductions', 'Total Net'];
            $monthly = (new PayrollModel())->db->table('payroll')
                ->select("payroll_month, SUM(total_gross) AS total_gross, SUM(total_deductions) AS total_deductions, SUM(total_net) AS total_net")
                ->where('status', 'finalized')
                ->groupBy('payroll_month')
                ->orderBy('payroll_month', 'DESC')
                ->get()->getResultArray();
            foreach ($monthly as $m) {
                $rows[] = [$m['payroll_month'], $m['total_gross'], $m['total_deductions'], $m['total_net']];
            }
        } elseif ($type === 'employee' && $emp) {
            $headers = ['Employee', 'Code', 'Period', 'Cutoff', 'Days Worked', 'Basic Pay', 'OT Pay', 'Gross', 'SSS', 'PhilHealth', 'Pag-IBIG', 'Total Ded', 'Net Pay'];
            $details = (new PayrollModel())->db->table('payroll_details pd')
                ->select(['pd.*','p.payroll_month','p.cutoff','p.period_start','e.full_name','e.employee_code'])
                ->join('payroll p', 'p.id = pd.payroll_id')
                ->join('employees e', 'e.id = pd.employee_id')
                ->where('pd.employee_id', $emp)
                ->where('p.status', 'finalized')
                ->orderBy('p.period_start', 'DESC')
                ->get()->getResultArray();
            foreach ($details as $d) {
                $rows[] = [
                    $d['full_name'], $d['employee_code'],
                    $d['payroll_month'], $d['cutoff'] == 1 ? '1st' : '2nd',
                    $d['days_worked'], $d['basic_pay'], $d['overtime_pay'],
                    $d['gross_pay'], $d['sss_deduction'], $d['philhealth_deduction'],
                    $d['pagibig_deduction'], $d['total_deductions'], $d['net_pay'],
                ];
            }
        }

        $filename = 'payroll_report_' . $type . '_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        fputcsv($out, $headers);
        foreach ($rows as $row) {
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }
}
