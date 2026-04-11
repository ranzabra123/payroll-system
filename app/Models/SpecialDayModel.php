<?php

namespace App\Models;

use CodeIgniter\Model;

class SpecialDayModel extends Model
{
    protected $table         = 'payroll_special_days';
    protected $primaryKey    = 'id';
    protected $useAutoIncrement = true;
    protected $returnType    = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'employee_id', 'date', 'adjustment_type', 'amount', 'reason', 'status',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'employee_id'     => 'required|integer',
        'date'            => 'required|valid_date[Y-m-d]',
        'adjustment_type' => 'required|in_list[fixed_amount,double_salary]',
        'amount'          => 'permit_empty|decimal|greater_than[0]',
    ];

    /**
     * List all special days with employee info, filtered.
     */
    public function listWithEmployee(array $filters = []): array
    {
        $builder = $this->db->table('payroll_special_days sd')
            ->select('sd.*, e.full_name, e.employee_code, e.department, e.daily_rate')
            ->join('employees e', 'e.id = sd.employee_id')
            ->orderBy('sd.date', 'DESC')
            ->orderBy('e.full_name', 'ASC');

        if (! empty($filters['search'])) {
            $s = $this->db->escapeLikeString($filters['search']);
            $builder->groupStart()
                    ->like('e.full_name', $s)
                    ->orLike('e.employee_code', $s)
                    ->orLike('sd.reason', $s)
                    ->groupEnd();
        }
        if (! empty($filters['employee_id'])) {
            $builder->where('sd.employee_id', (int) $filters['employee_id']);
        }
        if (! empty($filters['adjustment_type'])) {
            $builder->where('sd.adjustment_type', $filters['adjustment_type']);
        }
        if (! empty($filters['status'])) {
            $builder->where('sd.status', $filters['status']);
        }
        if (! empty($filters['date_from'])) {
            $builder->where('sd.date >=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $builder->where('sd.date <=', $filters['date_to']);
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Get all pending special days within a payroll period.
     */
    public function getPendingForPeriod(string $start, string $end): array
    {
        return $this->select('payroll_special_days.*, employees.daily_rate')
                    ->join('employees', 'employees.id = payroll_special_days.employee_id')
                    ->where('payroll_special_days.date >=', $start)
                    ->where('payroll_special_days.date <=', $end)
                    ->where('payroll_special_days.status', 'pending')
                    ->findAll();
    }

    /**
     * Get all pending special days within a payroll period, grouped by employee_id.
     */
    public function getPendingForPeriodGrouped(string $start, string $end): array
    {
        $rows = $this->getPendingForPeriod($start, $end);

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['employee_id']][] = $row;
        }
        return $grouped;
    }

    /**
     * Mark a set of IDs as applied (used after payroll generation).
     */
    public function markApplied(array $ids): void
    {
        if (empty($ids)) {
            return;
        }
        $this->db->table('payroll_special_days')
                 ->whereIn('id', $ids)
                 ->update(['status' => 'applied']);
    }
}
