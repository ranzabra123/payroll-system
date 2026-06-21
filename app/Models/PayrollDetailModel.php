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
        'payroll_id', 'employee_id', 'employee_salary', 'working_days', 'days_worked',
        'whole_days', 'half_days', 'absent_days', 'overtime_hours',
        'basic_pay', 'overtime_pay', 'special_adjustments', 'gross_pay',
        'absent_deduction', 'pharmacy_deduction',
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
     *
     * working_days   = calendar-based cutoff period days (15 or 16). Stored for display.
     * deptWorkingDays = department working days / month. Used ONLY for daily rate.
     *
     * daily_salary = monthly_salary / dept_working_days
     * Absent deduction = daily_salary × (absent_days + half_days × 0.5)
     *
     * @param  array $employee         Employee row (needs monthly_salary, daily_rate)
     * @param  array $attendance       Result of AttendanceModel::summarize()
     * @param  float $workingDays      Calendar working days for the cutoff period (15 or 16)
     * @param  float $deptWorkingDays  Dept working days/month (for daily rate calculation)
     * @param  float $specialAdjust   Special day bonus/adjustment
     * @param  float $sssAmount        Employee SSS contribution (0 for cutoff 1)
     * @param  float $phAmount         Employee PhilHealth contribution (0 for cutoff 1)
     * @param  float $piAmount         Employee Pag-IBIG contribution (0 for cutoff 1)
     * @param  float $otherDed         Other employee-specific deductions (loans, CA, etc.)
     * @param  float $pharmacyDed      Pharmacy deductions
     * @return array
     */
    public static function compute(
        array $employee,
        array $attendance,
        float $workingDays,
        float $deptWorkingDays,
        float $specialAdjust = 0.0,
        float $sssAmount = 0.0,
        float $phAmount  = 0.0,
        float $piAmount  = 0.0,
        float $otherDed  = 0.0,
        float $pharmacyDed = 0.0
    ): array {
        $monthlySalary = (float) $employee['monthly_salary'];
        $cutoffSalary  = $monthlySalary / 2;

        // Effective working days = cutoff period days − day-offs − (Sunday half-days × 0.5)
        // A day_off removes a full day. A Sunday half-day removes only 0.5 because the
        // employee was present for half the day (paid, no deduction).
        $dayOff          = (int)   ($attendance['day_off']          ?? 0);
        $sundayHalfDays  = (float) ($attendance['sunday_half_days'] ?? 0);
        $workingDays     = max(0.0, $workingDays - $dayOff - ($sundayHalfDays * 0.5));

        // Basic pay = full semi-monthly salary (rounded to whole peso)
        $basicPay = round($cutoffSalary);

        // Overtime: (daily_rate / 8) * 1.25 per OT hour
        $dailyRate   = (float) $employee['daily_rate'];
        $hourlyRate  = $dailyRate / 8;
        $otHours     = (float) $attendance['overtime_hours'];
        $overtimePay = round($hourlyRate * 1.25 * $otHours);

        $grossPay = round($basicPay + $overtimePay + $specialAdjust);

        // Attendance-based deduction:
        //   daily_salary = monthly_salary / dept_working_days  (NOT cutoff_salary / cutoff_days)
        //   Absent (whole day) = 1.0 × daily_salary
        //   Half day           = 0.5 × daily_salary  (only non-Sunday half-days)
        //   Day off / Sunday half-day = 0  (paid, no deduction)
        $absentDays         = (float) $attendance['absent_days'];
        $halfDays           = (float) $attendance['half_days'];
        $sundayHalfDays     = (float) ($attendance['sunday_half_days'] ?? 0);
        $deductableHalfDays = max(0.0, $halfDays - $sundayHalfDays); // Sundays are not deducted
        $absentDed          = 0.0;
        $deductUnits        = $absentDays + ($deductableHalfDays * 0.5);
        if ($deductUnits > 0 && $deptWorkingDays > 0) {
            $dailySalary = $monthlySalary / $deptWorkingDays;
            $absentDed   = round($dailySalary * $deductUnits, 2);
        }

        $sssDed   = round($sssAmount);
        $phDed    = round($phAmount);
        $piDed    = round($piAmount);
        $pharmDed = round($pharmacyDed);
        $othDed   = round($otherDed);
        $totalDed = round($absentDed + $sssDed + $phDed + $piDed + $pharmDed + $othDed);
        $netPay   = round($grossPay - $totalDed);

        return [
            'employee_salary'      => $monthlySalary,
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
            'pharmacy_deduction'   => $pharmDed,
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

