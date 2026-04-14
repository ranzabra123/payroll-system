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

        $firstCutoff = $this->model
            ->where('payroll_month', $month)
            ->where('cutoff', 1)
            ->where('status', 'finalized')
            ->first();

        return view('payroll/create', [
            'title'               => 'Generate Payroll',
            'selectedMonth'       => $month,
            'firstCutoffFinalized' => (bool) $firstCutoff,
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

            // Dept-specific working days per cutoff (fallback: Mon-Fri count for period)
            $deptWd         = $deptWdMap[$emp['department']] ?? null;
            $empWorkingDays = $deptWd ? (int) ceil($deptWd / 2) : $period['working_days'];

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
            $empDeductions   = $empDeductionModel->getActiveForCutoff($emp['id'], $cutoff, $period['end']);
            $otherDeductions = 0.0;
            foreach ($empDeductions as $ed) {
                $otherDeductions += (float) $ed['amount_per_cutoff'];
            }

            $computed = PayrollDetailModel::compute(
                $emp, $attendance, $empWorkingDays,
                $specialAdjustment, $sssAmt, $phAmt, $piAmt, $otherDeductions
            );

            $computed['payroll_id']  = $payrollId;
            $computed['employee_id'] = $emp['id'];

            $this->detailModel->insert($computed);

            // Reduce remaining_balance on applied employee deductions
            $histModel = new DeductionHistoryModel();
            foreach ($empDeductions as $ed) {
                $applied    = (float) $ed['amount_per_cutoff'];
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

    /** Recalculate a draft payroll (in case attendance was updated). */
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

            $deptWd         = $deptWdMap[$employee['department']] ?? null;
            $empWorkingDays = $deptWd ? (int) ceil($deptWd / 2) : $payroll['working_days'];
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

            $empDeductions   = $empDeductionModel->getActiveForCutoff($employee['id'], $cutoff, $payroll['period_end']);
            $otherDeductions = 0.0;
            foreach ($empDeductions as $ed) {
                $otherDeductions += (float) $ed['amount_per_cutoff'];
            }

            $computed = PayrollDetailModel::compute(
                $employee, $attendance, $empWorkingDays,
                $prevSpecial, $sssAmt, $phAmt, $piAmt, $otherDeductions
            );

            $this->detailModel->update($detail['id'], $computed);

            // Reapply deductions and record history
            foreach ($empDeductions as $ed) {
                // Reload fresh balance after possible restore above
                $fresh      = $empDeductionModel->find($ed['id']);
                $applied    = (float) $ed['amount_per_cutoff'];
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
}
