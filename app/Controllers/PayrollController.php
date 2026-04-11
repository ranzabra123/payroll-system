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
use App\Models\BenefitAssignmentModel;
use App\Models\EmployeeDeductionModel;
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
        $year  = $this->request->getGet('year')  ?? '';
        $month = $this->request->getGet('month') ?? '';

        // Default year to current year if nothing selected
        if ($year === '') {
            $year = date('Y');
        }

        $payrolls = $this->model->getAllWithCreator($year, $month);
        return view('payroll/index', [
            'title'    => 'Payroll',
            'payrolls' => $payrolls,
            'selYear'  => $year,
            'selMonth' => $month,
        ]);
    }

    /** Show form to generate a new payroll. */
    public function create()
    {
        return view('payroll/create', ['title' => 'Generate Payroll']);
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

        $deductions        = (new DeductionConfigModel())->getActiveConfigsKeyed();
        $attModel          = new AttendanceModel();
        $deptWdMap         = (new DepartmentModel())->getWorkingDaysMap();
        $benefitModel      = new BenefitAssignmentModel();
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

            // Benefit deductions for this employee and cutoff
            $benefitAssignments = $benefitModel->getForEmployee($emp['id'], $emp['department'], $cutoff);
            $benefitsDeduction  = 0.0;
            foreach ($benefitAssignments as $ba) {
                $share = (float) $ba['default_employee_share'];
                // 'both' = split monthly amount across two payrolls
                $benefitsDeduction += ($ba['cutoff'] === 'both') ? $share / 2 : $share;
            }

            // Employee-specific deductions (Cash Advance, loans, etc.)
            $empDeductions   = $empDeductionModel->getActiveForCutoff($emp['id'], $cutoff, $period['end']);
            $otherDeductions = 0.0;
            foreach ($empDeductions as $ed) {
                $otherDeductions += (float) $ed['amount_per_cutoff'];
            }

            $computed = PayrollDetailModel::compute($emp, $attendance, $deductions, $empWorkingDays, $specialAdjustment, $benefitsDeduction, $otherDeductions);

            $computed['payroll_id']  = $payrollId;
            $computed['employee_id'] = $emp['id'];

            $this->detailModel->insert($computed);

            // Reduce remaining_balance on applied employee deductions
            foreach ($empDeductions as $ed) {
                $applied     = (float) $ed['amount_per_cutoff'];
                $newBalance  = max(0.0, (float) $ed['remaining_balance'] - $applied);
                $updateData  = ['remaining_balance' => $newBalance];
                if ($newBalance <= 0) {
                    $updateData['status'] = 'inactive';
                }
                $empDeductionModel->update($ed['id'], $updateData);
            }

            $totalGross += $computed['gross_pay'];
            $totalDed   += $computed['total_deductions'];
            $totalNet   += $computed['net_pay'];
        }

        // Mark special days as applied
        $specialDayModel->markApplied($appliedSpecialIds);

        // --- Update payroll totals ---
        $this->model->update($payrollId, [
            'total_gross'      => round($totalGross, 2),
            'total_deductions' => round($totalDed, 2),
            'total_net'        => round($totalNet, 2),
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

        $details = $this->detailModel->getByPayroll($id);

        return view('payroll/view', [
            'title'   => PayrollModel::periodLabel($payroll),
            'payroll' => $payroll,
            'details' => $details,
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

        $deductions   = (new DeductionConfigModel())->getActiveConfigsKeyed();
        $attModel     = new AttendanceModel();
        $emp          = new EmployeeModel();
        $deptWdMap    = (new DepartmentModel())->getWorkingDaysMap();
        $benefitModel = new BenefitAssignmentModel();

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

            // Recompute benefit deductions
            $benefitAssignments = $benefitModel->getForEmployee(
                $employee['id'], $employee['department'], (int) $payroll['cutoff']
            );
            $benefitsDeduction = 0.0;
            foreach ($benefitAssignments as $ba) {
                $share = (float) $ba['default_employee_share'];
                $benefitsDeduction += ($ba['cutoff'] === 'both') ? $share / 2 : $share;
            }

            $computed = PayrollDetailModel::compute(
                $employee, $attendance, $deductions, $empWorkingDays, $prevSpecial, $benefitsDeduction
            );

            $this->detailModel->update($detail['id'], $computed);
            $totalGross += $computed['gross_pay'];
            $totalDed   += $computed['total_deductions'];
            $totalNet   += $computed['net_pay'];
        }

        $this->model->update($id, [
            'total_gross'      => round($totalGross, 2),
            'total_deductions' => round($totalDed, 2),
            'total_net'        => round($totalNet, 2),
        ]);

        $this->audit->logAction('Payroll', 'recalculate', $id);

        return redirect()->to(site_url('payroll/view/' . $id))
                         ->with('success', 'Payroll recalculated.');
    }
}
