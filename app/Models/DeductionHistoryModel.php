<?php

namespace App\Models;

use CodeIgniter\Model;

class DeductionHistoryModel extends Model
{
    protected $table         = 'deduction_history';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'employee_deduction_id', 'payroll_id', 'payroll_cutoff',
        'period_start', 'period_end',
        'amount_deducted', 'balance_before', 'balance_after',
    ];

    protected $useTimestamps = true;
    protected $updatedField  = '';   // no updated_at column

    /**
     * Get all history entries for a specific employee deduction, newest first.
     */
    public function getForDeduction(int $deductionId): array
    {
        return $this->select('deduction_history.*, payroll.payroll_month, payroll.cutoff AS cutoff_num, payroll.status AS payroll_status')
                    ->join('payroll', 'payroll.id = deduction_history.payroll_id')
                    ->where('employee_deduction_id', $deductionId)
                    ->orderBy('deduction_history.created_at', 'DESC')
                    ->findAll();
    }

    /**
     * Get deduction history entries for a payslip cutoff range.
     */
    public function getForPayslip(int $employeeId, int $payrollId, int $payrollCutoff, string $cutoffStart, string $cutoffEnd): array
    {
        return $this->select('deduction_history.*, employee_deductions.description, employee_deductions.type')
                    ->join('employee_deductions', 'employee_deductions.id = deduction_history.employee_deduction_id')
                    ->where('employee_deductions.employee_id', $employeeId)
                    ->where('deduction_history.payroll_id', $payrollId)
                    ->where('deduction_history.payroll_cutoff', $payrollCutoff)
                    ->where('DATE(deduction_history.created_at) >=', $cutoffStart)
                    ->where('DATE(deduction_history.created_at) <=', $cutoffEnd)
                    ->orderBy('deduction_history.created_at', 'ASC')
                    ->findAll();
    }

    /**
     * Remove history entry for a deduction+payroll pair (used when toggling OFF).
     */
    public function removeForPayroll(int $deductionId, int $payrollId): void
    {
        $this->where('employee_deduction_id', $deductionId)
             ->where('payroll_id', $payrollId)
             ->delete();
    }

    /**
     * Check if a history entry exists for this deduction+payroll.
     */
    public function existsForPayroll(int $deductionId, int $payrollId): bool
    {
        return $this->where('employee_deduction_id', $deductionId)
                    ->where('payroll_id', $payrollId)
                    ->countAllResults() > 0;
    }
}
