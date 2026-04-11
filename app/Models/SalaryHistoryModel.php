<?php

namespace App\Models;

use CodeIgniter\Model;

class SalaryHistoryModel extends Model
{
    protected $table         = 'salary_history';
    protected $primaryKey    = 'id';
    protected $useAutoIncrement = true;
    protected $returnType    = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'employee_id', 'previous_salary', 'new_salary',
        'effective_date', 'reason', 'changed_by',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = ''; // no updated_at on this table

    /**
     * Get history for an employee with changer name.
     */
    public function getByEmployee(int $employeeId): array
    {
        return $this->db->table('salary_history sh')
            ->select('sh.*, u.full_name AS changed_by_name')
            ->join('users u', 'u.id = sh.changed_by', 'left')
            ->where('sh.employee_id', $employeeId)
            ->orderBy('sh.effective_date', 'DESC')
            ->get()
            ->getResultArray();
    }
}
