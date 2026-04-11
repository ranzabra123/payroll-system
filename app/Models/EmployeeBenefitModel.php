<?php

namespace App\Models;

use CodeIgniter\Model;

class EmployeeBenefitModel extends Model
{
    protected $table         = 'employee_benefits';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'employee_id', 'benefit_type', 'cutoff',
        'employee_share', 'employer_share', 'effective_date', 'status', 'notes',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getBenefitTypes(): array
    {
        return ['SSS', 'PhilHealth', 'Pag-IBIG', 'HMO', 'Life Insurance', 'Other'];
    }

    public function listWithEmployee(array $filters = []): array
    {
        $this->select('employee_benefits.*, employees.full_name, employees.employee_code, employees.department')
             ->join('employees', 'employees.id = employee_benefits.employee_id');

        if (! empty($filters['search'])) {
            $s = $filters['search'];
            $this->groupStart()
                     ->like('employees.full_name', $s)
                     ->orLike('employees.employee_code', $s)
                     ->orLike('employee_benefits.benefit_type', $s)
                 ->groupEnd();
        }
        if (! empty($filters['benefit_type'])) {
            $this->where('employee_benefits.benefit_type', $filters['benefit_type']);
        }
        if (! empty($filters['status'])) {
            $this->where('employee_benefits.status', $filters['status']);
        }
        if (! empty($filters['cutoff'])) {
            $this->where('employee_benefits.cutoff', $filters['cutoff']);
        }

        return $this->orderBy('employees.full_name', 'ASC')->findAll();
    }

    /**
     * Get summary rows for a given month/cutoff – used by summary report.
     */
    public function getSummary(array $filters = []): array
    {
        $this->select('employee_benefits.*, employees.full_name, employees.employee_code, employees.department')
             ->join('employees', 'employees.id = employee_benefits.employee_id')
             ->where('employee_benefits.status', 'active');

        if (! empty($filters['benefit_type'])) {
            $this->where('employee_benefits.benefit_type', $filters['benefit_type']);
        }
        if (! empty($filters['cutoff'])) {
            $this->where('employee_benefits.cutoff', $filters['cutoff']);
        }
        if (! empty($filters['month'])) {
            // Only include benefits effective on or before last day of the chosen month
            $lastDay = date('Y-m-t', strtotime($filters['month'] . '-01'));
            $this->where('employee_benefits.effective_date <=', $lastDay);
        }
        if (! empty($filters['search'])) {
            $s = $filters['search'];
            $this->groupStart()
                     ->like('employees.full_name', $s)
                     ->orLike('employees.employee_code', $s)
                 ->groupEnd();
        }

        return $this->orderBy('employee_benefits.benefit_type', 'ASC')
                    ->orderBy('employees.full_name', 'ASC')
                    ->findAll();
    }

    /**
     * Get active benefits for a cutoff period (used by payroll).
     */
    public function getActiveForCutoff(int $empId, string $cutoff): array
    {
        return $this->where('employee_id', $empId)
                    ->where('cutoff', $cutoff)
                    ->where('status', 'active')
                    ->findAll();
    }
}
