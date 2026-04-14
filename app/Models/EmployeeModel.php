<?php

namespace App\Models;

use CodeIgniter\Model;

class EmployeeModel extends Model
{
    protected $table         = 'employees';
    protected $primaryKey    = 'id';
    protected $useAutoIncrement = true;
    protected $returnType    = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'employee_code', 'full_name', 'position', 'department', 'branch_id',
        'monthly_salary', 'daily_rate', 'date_hired', 'gender',
        'sss_number', 'sss_contribution',
        'philhealth_number', 'philhealth_contribution',
        'pagibig_number', 'pagibig_contribution',
        'tin_number', 'status',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'full_name'      => 'required|min_length[3]|max_length[150]',
        'position'       => 'required|min_length[2]|max_length[100]',
        'monthly_salary' => 'required|decimal|greater_than[0]',
        'date_hired'     => 'required|valid_date[Y-m-d]',
        'status'         => 'required|in_list[active,inactive]',
    ];

    /**
     * Generate next employee code: EMP-001, EMP-002, …
     */
    public function generateEmployeeCode(): string
    {
        $last = $this->withDeleted()
                     ->orderBy('id', 'DESC')
                     ->first();

        $nextNum = $last ? (int) substr($last['employee_code'], 4) + 1 : 1;
        return 'EMP-' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Get active employees for dropdown lists.
     */
    public function getActiveList(?int $branchId = null): array
    {
        $q = $this->where('status', 'active');
        if ($branchId !== null) {
            $q->where('branch_id', $branchId);
        }
        return $q->orderBy('full_name', 'ASC')->findAll();
    }

    /**
     * Search employees by name, position, or department.
     */
    public function search(string $q, ?int $branchId = null): array
    {
        $this->groupStart()
                 ->like('full_name', $q)
                 ->orLike('position', $q)
                 ->orLike('department', $q)
                 ->orLike('employee_code', $q)
             ->groupEnd();
        if ($branchId) {
            $this->where('branch_id', $branchId);
        }
        return $this->orderBy('FIELD(status,"active","inactive")', 'ASC', false)->orderBy('full_name', 'ASC')->findAll();
    }

    /**
     * Calculate years of service from date_hired.
     */
    public static function yearsOfService(string $dateHired): string
    {
        $hired = new \DateTime($dateHired);
        $now   = new \DateTime();
        $diff  = $hired->diff($now);
        return $diff->y . 'y ' . $diff->m . 'm';
    }

    /**
     * Active employee count per department name.
     * Returns: ['Finance' => 12, 'Operations' => 8, …]
     */
    public function getCountsByDepartment(): array
    {
        $rows = $this->db->table('employees')
                         ->select('department, COUNT(*) AS cnt')
                         ->where('status', 'active')
                         ->where('deleted_at IS NULL')
                         ->groupBy('department')
                         ->get()
                         ->getResultArray();
        $map = [];
        foreach ($rows as $row) {
            $map[$row['department'] ?: '(No Dept)'] = (int) $row['cnt'];
        }
        return $map;
    }

    /**
     * Get active employees whose department name is in the given list.
     */
    public function getActiveByDepartments(array $deptNames): array
    {
        if (empty($deptNames)) {
            return [];
        }
        return $this->where('status', 'active')
                    ->whereIn('department', $deptNames)
                    ->orderBy('full_name', 'ASC')
                    ->findAll();
    }
}
