<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\EmployeeModel;
use App\Models\PayrollModel;
use App\Models\AttendanceModel;
use App\Models\AuditLogModel;

/**
 * Dashboard – overview statistics.
 */
class Dashboard extends Controller
{
    public function index()
    {
        $empModel  = new EmployeeModel();
        $payModel  = new PayrollModel();
        $attModel  = new AttendanceModel();
        $auditModel = new AuditLogModel();

        $isAdmin = session()->get('role') === 'admin';
        $userBranchId = function_exists('user_branch_id') ? user_branch_id() : null;

        $empBuilder = $empModel->where('status', 'active');
        if ($userBranchId !== null) {
            $empBuilder->where('branch_id', $userBranchId);
        }
        $totalEmployees  = $empBuilder->countAllResults();
        $totalPayrolls   = $payModel->countAllResults();
        $finalizedPayrolls = $payModel->where('status', 'finalized')->countAllResults();

        // Latest payroll
        $latestPayroll = $payModel->orderBy('period_start', 'DESC')->first();

        // Today's attendance count
        $todayAttendance = $attModel->where('attendance_date', date('Y-m-d'))->countAllResults();

        // Recent audit logs
        $recentLogs = $auditModel->getRecent(10);

        // Monthly payroll totals (last 6 months)
        $monthlySummary = $payModel->db->table('payroll')
            ->select("payroll_month, SUM(total_gross) AS gross, SUM(total_net) AS net")
            ->where('status', 'finalized')
            ->groupBy('payroll_month')
            ->orderBy('payroll_month', 'DESC')
            ->limit(6)
            ->get()
            ->getResultArray();

        return view('dashboard/index', [
            'title'              => 'Dashboard',
            'isAdmin'            => $isAdmin,
            'totalEmployees'     => $totalEmployees,
            'totalPayrolls'      => $totalPayrolls,
            'finalizedPayrolls'  => $finalizedPayrolls,
            'latestPayroll'      => $latestPayroll,
            'todayAttendance'    => $todayAttendance,
            'recentLogs'         => $recentLogs,
            'monthlySummary'     => array_reverse($monthlySummary),
        ]);
    }
}
