<?php

namespace App\Models;

use CodeIgniter\Model;

class AttendanceModel extends Model
{
    protected $table         = 'attendance';
    protected $primaryKey    = 'id';
    protected $useAutoIncrement = true;
    protected $returnType    = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'employee_id', 'attendance_date', 'attendance_type',
        'overtime_hours', 'is_holiday', 'is_special_holiday', 'remarks', 'created_by',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'employee_id'     => 'required|integer',
        'attendance_date' => 'required|valid_date[Y-m-d]',
        'attendance_type' => 'required|in_list[whole_day,half_am,half_pm,absent]',
        'overtime_hours'  => 'permit_empty|decimal|greater_than_equal_to[0]',
    ];

    /**
     * Get attendance rows for an employee within a date range.
     */
    public function getByEmployeeAndPeriod(int $employeeId, string $start, string $end): array
    {
        return $this->where('employee_id', $employeeId)
                    ->where('attendance_date >=', $start)
                    ->where('attendance_date <=', $end)
                    ->orderBy('attendance_date', 'ASC')
                    ->findAll();
    }

    /**
     * Get all attendance for a period (all employees).
     */
    public function getByPeriod(string $start, string $end): array
    {
        return $this->select('attendance.*, employees.full_name, employees.employee_code, employees.position')
                    ->join('employees', 'employees.id = attendance.employee_id')
                    ->where('attendance_date >=', $start)
                    ->where('attendance_date <=', $end)
                    ->orderBy('attendance_date', 'ASC')
                    ->orderBy('employees.full_name', 'ASC')
                    ->findAll();
    }

    /**
     * Get attendance for a specific date (all employees).
     */
    public function getByDate(string $date): array
    {
        return $this->select('attendance.*, employees.full_name, employees.employee_code')
                    ->join('employees', 'employees.id = attendance.employee_id')
                    ->where('attendance_date', $date)
                    ->orderBy('employees.full_name', 'ASC')
                    ->findAll();
    }

    /**
     * Check if attendance record already exists for employee+date.
     */
    public function recordExists(int $employeeId, string $date, ?int $excludeId = null): bool
    {
        $builder = $this->where('employee_id', $employeeId)
                        ->where('attendance_date', $date);
        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }
        return $builder->countAllResults() > 0;
    }

    /**
     * Summarize attendance for payroll computation.
     * Returns: whole_days, half_days, absent_days, total_overtime, days_worked
     */
    public function summarize(int $employeeId, string $start, string $end): array
    {
        $rows = $this->getByEmployeeAndPeriod($employeeId, $start, $end);

        $summary = [
            'whole_days'     => 0,
            'half_days'      => 0,
            'absent_days'    => 0,
            'overtime_hours' => 0.0,
            'days_worked'    => 0.0,
        ];

        foreach ($rows as $row) {
            switch ($row['attendance_type']) {
                case 'whole_day':
                    $summary['whole_days']++;
                    $summary['days_worked'] += 1.0;
                    break;
                case 'half_am':
                case 'half_pm':
                    $summary['half_days']++;
                    $summary['days_worked'] += 0.5;
                    break;
                case 'absent':
                    $summary['absent_days']++;
                    break;
            }
            $summary['overtime_hours'] += (float) $row['overtime_hours'];
        }

        return $summary;
    }

    /**
     * Monthly summary grouped by employee.
     */
    public function monthlySummary(int $year, int $month, ?int $branchId = null): array
    {
        $start = sprintf('%04d-%02d-01', $year, $month);
        $end   = date('Y-m-t', strtotime($start));

        $q = $this->db->table('attendance a')
            ->select([
                'e.id AS employee_id',
                'e.employee_code',
                'e.full_name',
                'e.position',
                'SUM(CASE WHEN a.attendance_type = "whole_day" THEN 1 ELSE 0 END) AS whole_days',
                'SUM(CASE WHEN a.attendance_type IN ("half_am","half_pm") THEN 1 ELSE 0 END) AS half_days',
                'SUM(CASE WHEN a.attendance_type = "absent" THEN 1 ELSE 0 END) AS absent_days',
                'SUM(a.overtime_hours) AS total_overtime',
            ])
            ->join('employees e', 'e.id = a.employee_id')
            ->where('a.attendance_date >=', $start)
            ->where('a.attendance_date <=', $end)
            ->where('e.deleted_at IS NULL')
            ->groupBy('e.id')
            ->orderBy('e.full_name', 'ASC');

        if ($branchId !== null) {
            $q->where('e.branch_id', $branchId);
        }

        return $q->get()->getResultArray();
    }
}
