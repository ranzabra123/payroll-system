<?php

namespace App\Models;

use CodeIgniter\Model;

class PayrollDetailModel extends Model
{
    protected $table         = 'payroll_details';
    protected $primaryKey    = 'id';
    protected $useAutoIncrement = true;
    protected $returnType    = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'payroll_id', 'employee_id', 'working_days', 'days_worked',
        'whole_days', 'half_days', 'absent_days', 'overtime_hours',
        'basic_pay', 'overtime_pay', 'special_adjustments', 'gross_pay',
        'absent_deduction',
        'sss_deduction', 'philhealth_deduction', 'pagibig_deduction',
        'other_deductions', 'benefits_deduction', 'total_deductions', 'net_pay',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Get details for a payroll run with employee info.
     */
    public function getByPayroll(int $payrollId, int $branchId = 0): array
    {
        $q = $this->db->table('payroll_details pd')
            ->select([
                'pd.*',
                'e.employee_code',
                'e.full_name',
                'e.position',
                'e.department',
                'e.monthly_salary',
                'e.daily_rate',
                'e.branch_id',
                'br.name AS branch_name',
            ])
            ->join('employees e', 'e.id = pd.employee_id')
            ->join('branches br', 'br.id = e.branch_id', 'left')
            ->where('pd.payroll_id', $payrollId);

        if ($branchId > 0) {
            $q->where('e.branch_id', $branchId);
        }

        return $q->orderBy('br.name', 'ASC')
            ->orderBy('e.full_name', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get a single detail row with employee info.
     */
    public function getDetailWithEmployee(int $detailId): ?array
    {
        return $this->db->table('payroll_details pd')
            ->select([
                'pd.*',
                'e.employee_code',
                'e.full_name',
                'e.position',
                'e.department',
                'e.monthly_salary',
                'e.daily_rate',
                'e.sss_number',
                'e.philhealth_number',
                'e.pagibig_number',
                'e.tin_number',
                'e.date_hired',
            ])
            ->join('employees e', 'e.id = pd.employee_id')
            ->where('pd.id', $detailId)
            ->get()
            ->getRowArray();
    }

    /**
     * Compute payroll for one employee.
     *
     * Basic pay = monthly_salary / 2 (full semi-monthly, before absent deduction).
     * Absent deduction = (basic_pay / working_days) * absent_days.
     * Government contributions (SSS, PhilHealth, Pag-IBIG) are passed in directly
     * from the employee's individual benefit records — they are only applied on
     * the 30th cutoff (cutoff 2) and should be set to 0.0 when called for cutoff 1.
     *
     * @param  array $employee       Employee row (needs monthly_salary, daily_rate)
     * @param  array $attendance     Result of AttendanceModel::summarize()
     * @param  int   $workingDays    Working days in the payroll period
     * @param  float $specialAdjust Special day bonus/adjustment
     * @param  float $sssAmount      Employee SSS contribution (0 for cutoff 1)
     * @param  float $phAmount       Employee PhilHealth contribution (0 for cutoff 1)
     * @param  float $piAmount       Employee Pag-IBIG contribution (0 for cutoff 1)
     * @param  float $otherDed       Other employee-specific deductions (loans, CA, etc.)
     * @return array
     */
    public static function compute(
        array $employee,
        array $attendance,
        int $workingDays,
        float $specialAdjust = 0.0,
        float $sssAmount = 0.0,
        float $phAmount  = 0.0,
        float $piAmount  = 0.0,
        float $otherDed  = 0.0
    ): array {
        $monthlySalary = (float) $employee['monthly_salary'];
        $cutoffSalary  = $monthlySalary / 2;

        // Basic pay = full semi-monthly salary (rounded to whole peso)
        $basicPay = round($cutoffSalary);

        // Overtime: (daily_rate / 8) * 1.25 per OT hour
        $dailyRate   = (float) $employee['daily_rate'];
        $hourlyRate  = $dailyRate / 8;
        $otHours     = (float) $attendance['overtime_hours'];
        $overtimePay = round($hourlyRate * 1.25 * $otHours);

        $grossPay = round($basicPay + $overtimePay + $specialAdjust);

        // Absent deduction: (cutoffSalary / workingDays) * absentDays
        $absentDays = (float) $attendance['absent_days'];
        $absentDed  = 0.0;
        if ($absentDays > 0 && $workingDays > 0) {
            $absentDailyRate = $cutoffSalary / $workingDays;
            $absentDed       = round($absentDailyRate * $absentDays);
        }

        $sssDed   = round($sssAmount);
        $phDed    = round($phAmount);
        $piDed    = round($piAmount);
        $othDed   = round($otherDed);
        $totalDed = round($absentDed + $sssDed + $phDed + $piDed + $othDed);
        $netPay   = round($grossPay - $totalDed);

        return [
            'working_days'         => $workingDays,
            'days_worked'          => (float) $attendance['days_worked'],
            'whole_days'           => $attendance['whole_days'],
            'half_days'            => $attendance['half_days'],
            'absent_days'          => $absentDays,
            'overtime_hours'       => $otHours,
            'basic_pay'            => $basicPay,
            'overtime_pay'         => $overtimePay,
            'special_adjustments'  => round($specialAdjust),
            'gross_pay'            => $grossPay,
            'absent_deduction'     => $absentDed,
            'sss_deduction'        => $sssDed,
            'philhealth_deduction' => $phDed,
            'pagibig_deduction'    => $piDed,
            'other_deductions'     => $othDed,
            'benefits_deduction'   => 0.0,
            'total_deductions'     => $totalDed,
            'net_pay'              => $netPay,
        ];
    }
}

