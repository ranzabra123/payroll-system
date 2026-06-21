<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\PayrollModel;
use App\Models\PayrollDetailModel;
use App\Models\EmployeeModel;
use App\Models\AttendanceModel;
use App\Models\DeductionConfigModel;
use App\Models\DepartmentModel;
use App\Models\SpecialDayModel;
use App\Models\BenefitModel;
use App\Models\BenefitAssignmentModel;
use App\Models\EmployeeDeductionModel;
use App\Models\DeductionHistoryModel;
use App\Models\AuditLogModel;

/**
 * PayrollController – generate, view, and finalize payroll.
 */
class PayrollController extends Controller
{
    protected PayrollModel $model;
    protected PayrollDetailModel $detailModel;
    protected AuditLogModel $audit;

    public function __construct()
    {
        $this->model       = new PayrollModel();
        $this->detailModel = new PayrollDetailModel();
        $this->audit       = new AuditLogModel();
    }

    /** List all payroll runs. */
    public function index()
    {
        $year     = $this->request->getGet('year')      ?? '';
        $month    = $this->request->getGet('month')     ?? '';
        $branchId = $this->request->getGet('branch_id') ?? '';

        // Default year to current year if nothing selected
        if ($year === '') {
            $year = date('Y');
        }

        $branches = (new \App\Models\BranchModel())->getActiveList();
        $payrolls = $this->model->getAllWithCreator($year, $month, $branchId);
        return view('payroll/index', [
            'title'     => 'Payroll',
            'payrolls'  => $payrolls,
            'selYear'   => $year,
            'selMonth'  => $month,
            'selBranch' => $branchId,
            'branches'  => $branches,
        ]);
    }

    /** Show form to generate a new payroll. */
    public function create()
    {
        $month = $this->request->getGet('payroll_month') ?? date('Y-m');
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }

        // Get 1st cutoff record for the month
        $firstCutoff = $this->model
            ->where('payroll_month', $month)
            ->where('cutoff', 1)
            ->first();

        $firstCutoffFinalized = false;
        if ($firstCutoff && isset($firstCutoff['status']) && $firstCutoff['status'] === 'finalized') {
            $firstCutoffFinalized = true;
        }

        // Get today's date and current month
        $today = date('Y-m-d');
        $currentYearMonth = date('Y-m');
        $currentDay = (int)date('d');

        // Compute period for 1st and 2nd cutoff
        $period1 = \App\Models\PayrollModel::computePeriod($month, 1);
        $period2 = \App\Models\PayrollModel::computePeriod($month, 2);

        // Remove cutoff period restrictions: allow user to generate payroll 1 month before and after current month
        $disableFirst = false;
        $disableSecond = false;

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'firstCutoffFinalized' => $firstCutoffFinalized,
                'disableFirst'         => $disableFirst,
                'disableSecond'        => $disableSecond,
            ]);
        }

        return view('payroll/create', [
            'title'               => 'Generate Payroll',
            'selectedMonth'       => $month,
            'firstCutoffFinalized' => $firstCutoffFinalized,
            'disableFirst'         => $disableFirst,
            'disableSecond'        => $disableSecond,
        ]);
    }

    /** Generate (store) payroll for a cutoff. */
    public function generate()
    {
        $month  = $this->request->getPost('payroll_month');  // e.g. 2026-04
        $cutoff = (int) $this->request->getPost('cutoff');   // 1 or 2

        if (! preg_match('/^\d{4}-\d{2}$/', $month) || ! in_array($cutoff, [1, 2], true)) {
            return redirect()->back()->with('error', 'Invalid payroll period.')->withInput();
        }

        // Check duplicate
        $existing = $this->model
            ->where('payroll_month', $month)
            ->where('cutoff', $cutoff)
            ->first();
        if ($existing) {
            return redirect()->to(site_url('payroll/view/' . $existing['id']))
                             ->with('error', 'Payroll for this period already exists.');
        }

        $period    = PayrollModel::computePeriod($month, $cutoff);
        $employees = (new EmployeeModel())->getActiveList();

        if (empty($employees)) {
            return redirect()->back()->with('error', 'No active employees found.');
        }

        $attModel          = new AttendanceModel();
        $deptWdMap         = (new DepartmentModel())->getWorkingDaysMap();
        $benefitModel      = new BenefitModel();
        $empDeductionModel = new EmployeeDeductionModel();

        // Gather special days applicable to this period, grouped by employee
        $specialDayModel   = new SpecialDayModel();
        $specialDays       = $specialDayModel->getPendingForPeriod($period['start'], $period['end']);
        $specialDaysByEmp  = [];
        foreach ($specialDays as $sd) {
            $specialDaysByEmp[$sd['employee_id']][] = $sd;
        }
        $appliedSpecialIds = [];

        // --- Insert payroll header ---
        $payrollId = $this->model->insert([
            'payroll_month' => $month,
            'cutoff'        => $cutoff,
            'period_start'  => $period['start'],
            'period_end'    => $period['end'],
            'working_days'  => $period['working_days'],
            'status'        => 'draft',
            'created_by'    => session()->get('user_id'),
        ]);

        $totalGross = 0;
        $totalDed   = 0;
        $totalNet   = 0;

        // --- Compute per employee ---
        foreach ($employees as $emp) {
            $attendance = $attModel->summarize($emp['id'], $period['start'], $period['end']);

            // Calendar-based cutoff working days (for display / days_worked tracking).
            // 28 or 30-day month: both cutoffs = 15 days.
            // 31-day month: 1st cutoff = 15 days, 2nd cutoff = 16 days.
            $calendarDays      = (int) date('t', strtotime($month . '-01'));
            $empWorkingDays    = ($calendarDays === 31 && $cutoff === 2) ? 16.0 : 15.0;

            // Dept working days used ONLY as the divisor for the daily rate deduction.
            // Falls back to 26 if the employee's department is not mapped.
            $deptWd = (float) ($deptWdMap[$emp['department']] ?? 26);

            // Special day adjustments for this employee
            $empSpecialDays    = $specialDaysByEmp[$emp['id']] ?? [];
            $specialAdjustment = 0.0;
            foreach ($empSpecialDays as $sd) {
                if ($sd['adjustment_type'] === 'fixed_amount') {
                    $specialAdjustment += (float) $sd['amount'];
                } else {
                    $specialAdjustment += (float) $emp['daily_rate'];
                }
                $appliedSpecialIds[] = $sd['id'];
            }

            // Government contributions from employee record.
            // Only deduct on cutoff 2 (the 30th / end-of-month run).
            $sssAmt = 0.0;
            $phAmt  = 0.0;
            $piAmt  = 0.0;
            if ($cutoff === 2) {
                $sssAmt = (float) ($emp['sss_contribution']      ?? 0);
                $phAmt  = (float) ($emp['philhealth_contribution'] ?? 0);
                $piAmt  = (float) ($emp['pagibig_contribution']   ?? 0);
            }

            // Employee-specific deductions (Cash Advance, loans, etc.)
            $empDeductions      = $empDeductionModel->getActiveForCutoff($emp['id'], $cutoff, $period['end']);
            $otherDeductions    = 0.0;
            $pharmacyDeductions = 0.0;
            foreach ($empDeductions as $ed) {
                $applied = $this->getPayrollDeductionAmount($ed);
                if (($ed['type'] ?? '') === 'Pharmacy') {
                    $pharmacyDeductions += $applied;
                } else {
                    $otherDeductions += $applied;
                }
            }

            $computed = PayrollDetailModel::compute(
                $emp, $attendance, $empWorkingDays, $deptWd,
                $specialAdjustment, $sssAmt, $phAmt, $piAmt, $otherDeductions, $pharmacyDeductions
            );

            $computed['payroll_id']  = $payrollId;
            $computed['employee_id'] = $emp['id'];

            $this->detailModel->insert($computed);

            // Reduce remaining_balance on applied employee deductions
            $histModel = new DeductionHistoryModel();
            foreach ($empDeductions as $ed) {
                $applied    = $this->getPayrollDeductionAmount($ed);
                $balBefore  = (float) $ed['remaining_balance'];
                $newBalance = max(0.0, $balBefore - $applied);
                $updateData = ['remaining_balance' => $newBalance];
                if ($newBalance <= 0) {
                    $updateData['status'] = 'completed';
                }
                $empDeductionModel->update($ed['id'], $updateData);

                // Record history entry
                $histModel->insert([
                    'employee_deduction_id' => $ed['id'],
                    'payroll_id'            => $payrollId,
                    'payroll_cutoff'        => $cutoff,
                    'period_start'          => $period['start'],
                    'period_end'            => $period['end'],
                    'amount_deducted'       => $applied,
                    'balance_before'        => $balBefore,
                    'balance_after'         => $newBalance,
                ]);
            }

            $totalGross += $computed['gross_pay'];
            $totalDed   += $computed['total_deductions'];
            $totalNet   += $computed['net_pay'];
        }

        // Mark special days as applied
        $specialDayModel->markApplied($appliedSpecialIds);

        // --- Update payroll totals ---
        $this->model->update($payrollId, [
            'total_gross'      => round($totalGross),
            'total_deductions' => round($totalDed),
            'total_net'        => round($totalNet),
        ]);

        $cutoffLabel = $cutoff === '1' ? '1st Cutoff' : '2nd Cutoff';
        $this->audit->logAction('Payroll', 'generate', $payrollId, null,
            ['month' => $month, 'cutoff' => $cutoff],
            "Generated payroll: " . date('F Y', strtotime($month . '-01')) . " — {$cutoffLabel}");

        return redirect()->to(site_url('payroll/view/' . $payrollId))
                         ->with('success', 'Payroll generated successfully.');
    }

    /** View payroll run details. */
    public function view(int $id)
    {
        $payroll = $this->model->find($id);
        if (! $payroll) {
            return redirect()->to(site_url('payroll'))->with('error', 'Payroll not found.');
        }

        $branchId = (int) ($this->request->getGet('branch_id') ?? 0);
        $selYear  = $this->request->getGet('year')  ?? '';
        $selMonth = $this->request->getGet('month') ?? '';
        $branches = (new \App\Models\BranchModel())->getActiveList();
        $details  = $this->detailModel->getByPayroll($id, $branchId);

        // Compute summary from filtered details
        $filteredGross = array_sum(array_column($details, 'gross_pay'));
        $filteredDed   = array_sum(array_column($details, 'total_deductions'));
        $filteredNet   = array_sum(array_column($details, 'net_pay'));

        return view('payroll/view', [
            'title'         => PayrollModel::periodLabel($payroll),
            'payroll'       => $payroll,
            'details'       => $details,
            'branches'      => $branches,
            'selBranch'     => $branchId,
            'selYear'       => $selYear,
            'selMonth'      => $selMonth,
            'filteredGross' => $filteredGross,
            'filteredDed'   => $filteredDed,
            'filteredNet'   => $filteredNet,
        ]);
    }

    /** Return JSON snapshot of payroll totals + detail rows for live polling. */
    public function pollData(int $id)
    {
        $payroll = $this->model->find($id);
        if (! $payroll) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Not found']);
        }

        $branchId = (int) ($this->request->getGet('branch_id') ?? 0);
        $details  = $this->detailModel->getByPayroll($id, $branchId);
        $detailRows = [];
        foreach ($details as $d) {
            $absentDed = (float)($d['absent_deduction'] ?? 0);
            if ($absentDed == 0 && ($d['benefits_deduction'] ?? 0) > 0) {
                $absentDed = (float)$d['benefits_deduction'];
            }
            $detailRows[] = [
                'id'                   => (int) $d['id'],
                'gross_pay'            => (float) $d['gross_pay'],
                'absent_deduction'     => $absentDed,
                'pharmacy_deduction'   => (float) $d['pharmacy_deduction'],
                'sss_deduction'        => (float) $d['sss_deduction'],
                'philhealth_deduction' => (float) $d['philhealth_deduction'],
                'pagibig_deduction'    => (float) $d['pagibig_deduction'],
                'other_deductions'     => (float) $d['other_deductions'],
                'total_deductions'     => (float) $d['total_deductions'],
                'net_pay'              => (float) $d['net_pay'],
            ];
        }

        $filteredGross = array_sum(array_column($details, 'gross_pay'));
        $filteredDed   = array_sum(array_column($details, 'total_deductions'));
        $filteredNet   = array_sum(array_column($details, 'net_pay'));

        return $this->response->setJSON([
            'total_gross'      => $filteredGross,
            'total_deductions' => $filteredDed,
            'total_net'        => $filteredNet,
            'details'          => $detailRows,
        ]);
    }

    /** Print payroll report in various paper sizes. */
    public function print(int $id)
    {
        $payroll = $this->model->find($id);
        if (! $payroll) {
            return redirect()->to(site_url('payroll'))->with('error', 'Payroll not found.');
        }

        $details = $this->detailModel->getByPayroll($id);

        return view('payroll/print', [
            'title'   => PayrollModel::periodLabel($payroll),
            'label'   => PayrollModel::periodLabel($payroll),
            'payroll' => $payroll,
            'details' => $details,
        ]);
    }

    /** Finalize a payroll run. */
    public function finalize(int $id)
    {
        $payroll = $this->model->find($id);
        if (! $payroll || $payroll['status'] === 'finalized') {
            return redirect()->to(site_url('payroll'))->with('error', 'Payroll not found or already finalized.');
        }

        $this->model->update($id, ['status' => 'finalized']);

        // Re-enable all active deductions so next payroll run picks them up
        (new EmployeeDeductionModel())->where('status', 'active')->set('is_enabled', 1)->update();

        $periodLabel = \App\Models\PayrollModel::periodLabel($payroll);
        $this->audit->logAction('Payroll', 'finalize', $id, null, null,
            "Finalized payroll: {$periodLabel}");

        return redirect()->to(site_url('payroll/view/' . $id))
                         ->with('success', 'Payroll finalized successfully.');
    }

    /** Delete a draft payroll. */
    public function delete(int $id)
    {
        $payroll = $this->model->find($id);
        if (! $payroll) {
            return redirect()->to(site_url('payroll'))->with('error', 'Payroll not found.');
        }
        if ($payroll['status'] === 'finalized') {
            return redirect()->to(site_url('payroll'))->with('error', 'Cannot delete a finalized payroll.');
        }

        // Details are deleted via cascade
        $this->model->delete($id);
        $periodLabel = \App\Models\PayrollModel::periodLabel($payroll);
        $this->audit->logAction('Payroll', 'delete', $id, $payroll, null,
            "Deleted payroll: {$periodLabel}");

        return redirect()->to(site_url('payroll'))->with('success', 'Payroll deleted.');
    }

    /** Deduction breakdown report for printing. */
    public function deductionReport(int $id)
    {
        $payroll = $this->model->find($id);
        if (! $payroll) {
            return redirect()->to(site_url('payroll'))->with('error', 'Payroll not found.');
        }

        $details    = $this->detailModel->getByPayroll($id);
        $cutoffVal  = (int)$payroll['cutoff'] === 1 ? '15' : '30';
        $edModel    = new \App\Models\EmployeeDeductionModel();

        $deductionRows = [];

        foreach ($details as $d) {
            $empName = $d['full_name'];

            // Pharmacy (from payroll_details aggregate)
            $pharmDed = (float)($d['pharmacy_deduction'] ?? 0);
            if ($pharmDed > 0) {
                $deductionRows[] = ['employee' => $empName, 'type' => 'Pharmacy', 'amount' => $pharmDed];
            }

            // Government contributions (cutoff 2 only)
            if ((int)$payroll['cutoff'] === 2) {
                if ((float)$d['sss_deduction'] > 0) {
                    $deductionRows[] = ['employee' => $empName, 'type' => 'SSS', 'amount' => (float)$d['sss_deduction']];
                }
                if ((float)$d['philhealth_deduction'] > 0) {
                    $deductionRows[] = ['employee' => $empName, 'type' => 'PhilHealth', 'amount' => (float)$d['philhealth_deduction']];
                }
                if ((float)$d['pagibig_deduction'] > 0) {
                    $deductionRows[] = ['employee' => $empName, 'type' => 'Pag-IBIG', 'amount' => (float)$d['pagibig_deduction']];
                }
            }
        }

        // Cash Advance & Debt — fetch individual items from employee_deductions
        $empIds = array_column($details, 'employee_id');
        if (! empty($empIds)) {
            $individualDeds = $edModel
                ->select('employee_deductions.type, employee_deductions.amount_per_cutoff, employee_deductions.remaining_balance, employees.full_name')
                ->join('employees', 'employees.id = employee_deductions.employee_id')
                ->whereIn('employee_deductions.employee_id', $empIds)
                ->whereIn('employee_deductions.type', ['Cash Advance', 'Debt'])
                ->groupStart()
                    ->where('employee_deductions.cutoff', $cutoffVal)
                    ->orWhere('employee_deductions.cutoff', 'both')
                    ->orWhere('employee_deductions.cutoff', 'full')
                ->groupEnd()
                ->where('employee_deductions.start_date <=', $payroll['period_end'])
                ->where('employee_deductions.status', 'active')
                ->where('employee_deductions.is_enabled', 1)
                ->orderBy('employees.full_name', 'ASC')
                ->findAll();

            foreach ($individualDeds as $ed) {
                $amt = min((float)$ed['amount_per_cutoff'], (float)$ed['remaining_balance']);
                if ($amt > 0) {
                    $deductionRows[] = ['employee' => $ed['full_name'], 'type' => $ed['type'], 'amount' => $amt];
                }
            }
        }

        // Group by deduction type
        $grouped = [];
        foreach ($deductionRows as $row) {
            $grouped[$row['type']][] = $row;
        }

        // Sort each group by employee name
        foreach ($grouped as &$rows) {
            usort($rows, fn($a, $b) => strcmp($a['employee'], $b['employee']));
        }
        unset($rows);

        // Enforce display order
        $typeOrder = ['Pharmacy', 'Cash Advance', 'Debt', 'SSS', 'PhilHealth', 'Pag-IBIG'];
        uksort($grouped, function ($a, $b) use ($typeOrder) {
            $ai = array_search($a, $typeOrder);
            $bi = array_search($b, $typeOrder);
            $ai = $ai === false ? 99 : $ai;
            $bi = $bi === false ? 99 : $bi;
            return $ai - $bi;
        });

        return view('payroll/deduction_report', [
            'payroll' => $payroll,
            'label'   => \App\Models\PayrollModel::periodLabel($payroll),
            'grouped' => $grouped,
        ]);
    }


    public function recalculate(int $id)
    {
        $payroll = $this->model->find($id);
        if (! $payroll || $payroll['status'] === 'finalized') {
            return redirect()->to(site_url('payroll'))->with('error', 'Cannot recalculate.');
        }

        $attModel        = new AttendanceModel();
        $emp             = new EmployeeModel();
        $deptWdMap       = (new DepartmentModel())->getWorkingDaysMap();
        $empDeductionModel = new EmployeeDeductionModel();
        $cutoff          = (int) $payroll['cutoff'];

        $details = $this->detailModel->getByPayroll($id);
        $totalGross = $totalDed = $totalNet = 0;

        foreach ($details as $detail) {
            $employee   = $emp->find($detail['employee_id']);
            $attendance = $attModel->summarize(
                $detail['employee_id'],
                $payroll['period_start'],
                $payroll['period_end']
            );

            $calendarDays      = (int) date('t', strtotime($payroll['payroll_month'] . '-01'));
            $empWorkingDays    = ($calendarDays === 31 && $cutoff === 2) ? 16.0 : 15.0;
            $deptWd            = (float) ($deptWdMap[$employee['department']] ?? 26);
            $prevSpecial    = (float) ($detail['special_adjustments'] ?? 0);

            // Government contributions from employee record (cutoff 2 only)
            $sssAmt = 0.0;
            $phAmt  = 0.0;
            $piAmt  = 0.0;
            if ($cutoff === 2) {
                $sssAmt = (float) ($employee['sss_contribution']       ?? 0);
                $phAmt  = (float) ($employee['philhealth_contribution'] ?? 0);
                $piAmt  = (float) ($employee['pagibig_contribution']    ?? 0);
            }

            // Employee-specific deductions — reverse any existing history first, then reapply
            $histModel     = new DeductionHistoryModel();
            $allDeductions = $empDeductionModel
                ->where('employee_id', $employee['id'])
                ->where('status !=', 'cancelled')
                ->findAll();

            foreach ($allDeductions as $ed) {
                $prevHist = $histModel->where('employee_deduction_id', $ed['id'])
                                      ->where('payroll_id', $id)
                                      ->first();
                if ($prevHist) {
                    // Restore previous balance
                    $restored = (float) $prevHist['balance_before'];
                    $empDeductionModel->update($ed['id'], [
                        'remaining_balance' => $restored,
                        'status'            => 'active',
                    ]);
                    $histModel->where('id', $prevHist['id'])->delete();
                }
            }

            $empDeductions      = $empDeductionModel->getActiveForCutoff($employee['id'], $cutoff, $payroll['period_end']);
            $otherDeductions    = 0.0;
            $pharmacyDeductions = 0.0;
            foreach ($empDeductions as $ed) {
                $applied = $this->getPayrollDeductionAmount($ed);
                if (($ed['type'] ?? '') === 'Pharmacy') {
                    $pharmacyDeductions += $applied;
                } else {
                    $otherDeductions += $applied;
                }
            }

            $computed = PayrollDetailModel::compute(
                $employee, $attendance, $empWorkingDays, $deptWd,
                $prevSpecial, $sssAmt, $phAmt, $piAmt, $otherDeductions, $pharmacyDeductions
            );

            $this->detailModel->update($detail['id'], $computed);

            // Reapply deductions and record history
            foreach ($empDeductions as $ed) {
                // Reload fresh balance after possible restore above
                $fresh      = $empDeductionModel->find($ed['id']);
                $applied    = $this->getPayrollDeductionAmount($fresh);
                $balBefore  = (float) $fresh['remaining_balance'];
                $newBalance = max(0.0, $balBefore - $applied);
                $upd        = ['remaining_balance' => $newBalance];
                if ($newBalance <= 0) {
                    $upd['status'] = 'completed';
                }
                $empDeductionModel->update($ed['id'], $upd);
                $histModel->insert([
                    'employee_deduction_id' => $ed['id'],
                    'payroll_id'            => $id,
                    'payroll_cutoff'        => $cutoff,
                    'period_start'          => $payroll['period_start'],
                    'period_end'            => $payroll['period_end'],
                    'amount_deducted'       => $applied,
                    'balance_before'        => $balBefore,
                    'balance_after'         => $newBalance,
                ]);
            }

            $totalGross += $computed['gross_pay'];
            $totalDed   += $computed['total_deductions'];
            $totalNet   += $computed['net_pay'];
        }

        $this->model->update($id, [
            'total_gross'      => round($totalGross),
            'total_deductions' => round($totalDed),
            'total_net'        => round($totalNet),
        ]);

        $this->audit->logAction('Payroll', 'recalculate', $id);

        return redirect()->to(site_url('payroll/view/' . $id))
                         ->with('success', 'Payroll recalculated.');
    }

    /**
     * Get the deduction amount that should be applied for a payroll cutoff.
     * Uses remaining balance if it is smaller than the per-cutoff amount.
     */
    private function getPayrollDeductionAmount(array $deduction): float
    {
        $amountPerCutoff = (float) ($deduction['amount_per_cutoff'] ?? 0);
        $remainingBalance = (float) ($deduction['remaining_balance'] ?? 0);

        return min($amountPerCutoff, max(0.0, $remainingBalance));
    }
}
