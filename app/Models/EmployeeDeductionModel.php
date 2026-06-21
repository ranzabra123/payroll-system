<?php

namespace App\Models;

use CodeIgniter\Model;

class EmployeeDeductionModel extends Model
{
    protected $table         = 'employee_deductions';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'employee_id', 'type', 'description', 'total_amount',
        'amount_per_cutoff', 'cutoff', 'remaining_balance',
        'status', 'is_enabled', 'start_date', 'notes',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getWithEmployee(int $id): ?array
    {
        return $this->select('employee_deductions.*, employees.full_name, employees.employee_code, employees.position, employees.department')
                    ->join('employees', 'employees.id = employee_deductions.employee_id')
                    ->where('employee_deductions.id', $id)
                    ->first();
    }

    public function listWithEmployee(array $filters = []): array
    {
        $this->select('employee_deductions.*, employees.full_name, employees.employee_code')
             ->join('employees', 'employees.id = employee_deductions.employee_id');

        if (! empty($filters['search'])) {
            $s = $filters['search'];
            $this->groupStart()
                     ->like('employees.full_name', $s)
                     ->orLike('employees.employee_code', $s)
                     ->orLike('employee_deductions.description', $s)
                 ->groupEnd();
        }
        if (! empty($filters['type'])) {
            $this->where('employee_deductions.type', $filters['type']);
        }
        if (array_key_exists('status', $filters)) {
            if ($filters['status'] !== '') {
                $this->where('employee_deductions.status', $filters['status']);
            }
        } else {
            $this->where('employee_deductions.status', 'active');
        }
        if (! empty($filters['cutoff'])) {
            $this->where('employee_deductions.cutoff', $filters['cutoff']);
        }

        return $this->orderBy('employee_deductions.created_at', 'DESC')->findAll();
    }

    /**
     * Get active deductions for a cutoff period (used by payroll).
     * $payrollCutoff: integer 1 (1–15) or 2 (16–30/31).
     * $periodEnd: Y-m-d date string — only apply deductions whose start_date is on or before this date.
     */
    public function listSummary(array $filters = []): array
    {
        $year   = $filters['year']   ?? date('Y');
        $month  = $filters['month']  ?? '';
        $search = $filters['search'] ?? '';

        $this->select('employee_deductions.*, employees.full_name, employees.employee_code, employees.department')
             ->join('employees', 'employees.id = employee_deductions.employee_id');

        if ($year !== '') {
            $this->where('YEAR(employee_deductions.start_date)', $year);
        }
        if ($month !== '') {
            $this->where('MONTH(employee_deductions.start_date)', $month);
        }
        if (! empty($filters['type'])) {
            $this->where('employee_deductions.type', $filters['type']);
        }
        if (! empty($filters['status'])) {
            $this->where('employee_deductions.status', $filters['status']);
        }
        if (! empty($filters['cutoff'])) {
            $this->where('employee_deductions.cutoff', $filters['cutoff']);
        }
        if ($search !== '') {
            $this->groupStart()
                     ->like('employees.full_name', $search)
                     ->orLike('employees.employee_code', $search)
                     ->orLike('employee_deductions.description', $search)
                 ->groupEnd();
        }

        return $this->orderBy('employees.full_name', 'ASC')
                    ->orderBy('employee_deductions.start_date', 'ASC')
                    ->findAll();
    }

    public function getActiveForCutoff(int $empId, int $payrollCutoff, string $periodEnd): array
    {
        $cutoffVal = $payrollCutoff === 1 ? '15' : '30';

        return $this->where('employee_id', $empId)
                    ->groupStart()
                        ->where('cutoff', $cutoffVal)
                        ->orWhere('cutoff', 'both')
                        ->orWhere('cutoff', 'full')
                    ->groupEnd()
                    ->where('status', 'active')
                    ->where('is_enabled', 1)
                    ->where('remaining_balance >', 0)
                    ->where('start_date <=', $periodEnd)
                    ->findAll();
    }

    /**
     * Get deductions for payslip display (no balance/status filter — amounts may
     * already be reduced after payroll generation).
     */
    public function getForPayslipDisplay(int $empId, int $payrollCutoff, string $periodEnd, int $payrollId = 0): array
    {
        $cutoffVal = $payrollCutoff === 1 ? '15' : '30';
        $builder   = $this->builder();

        $builder->select('employee_deductions.*');

        if ($payrollId > 0) {
            $builder->join(
                'deduction_history',
                "deduction_history.employee_deduction_id = employee_deductions.id AND deduction_history.payroll_id = {$payrollId} AND deduction_history.payroll_cutoff = {$payrollCutoff}",
                'left'
            );
        }

        $builder->where('employee_id', $empId)
                ->groupStart()
                    ->where('cutoff', $cutoffVal)
                    ->orWhere('cutoff', 'both')
                    ->orWhere('cutoff', 'full')
                ->groupEnd()
                ->groupStart()
                    ->where('employee_deductions.status', 'active');

        if ($payrollId > 0) {
            $builder->orWhere('deduction_history.id IS NOT NULL');
        }

        $builder->groupEnd()
                ->where('is_enabled', 1)
                ->where('amount_per_cutoff >', 0)
                ->where('start_date <=', $periodEnd);

        if ($payrollId > 0) {
            $builder->groupBy('employee_deductions.id');
        }

        return $builder->get()->getResultArray();
    }
}
