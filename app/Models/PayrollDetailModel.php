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
        'sss_deduction', 'philhealth_deduction', 'pagibig_deduction',
        'other_deductions', 'benefits_deduction', 'total_deductions', 'net_pay',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Get details for a payroll run with employee info.
     */
    public function getByPayroll(int $payrollId): array
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
            ])
            ->join('employees e', 'e.id = pd.employee_id')
            ->where('pd.payroll_id', $payrollId)
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
     * @param  array $employee     Employee row (must have daily_rate, monthly_salary)
     * @param  array $attendance   Attendance summary from AttendanceModel::summarize()
     * @param  array $deductions   Deduction config rows keyed by type
     * @param  int   $workingDays  Total working days in the period
     * @return array               Computed pay values
     */
    public static function compute(
        array $employee,
        array $attendance,
        array $deductions,
        int $workingDays,
        float $specialAdjustment = 0.0,
        float $benefitsDeduction = 0.0,
        float $otherDeductions = 0.0
    ): array {
        $dailyRate     = (float) $employee['daily_rate'];
        $monthlySalary = (float) $employee['monthly_salary'];

        // Basic pay = days_worked * daily_rate
        $daysWorked  = (float) $attendance['days_worked'];
        $basicPay    = round($daysWorked * $dailyRate, 2);

        // Overtime: (daily_rate / 8) * 1.25 per OT hour
        $hourlyRate    = $dailyRate / 8;
        $otHours       = (float) $attendance['overtime_hours'];
        $overtimePay   = round($hourlyRate * 1.25 * $otHours, 2);

        $grossPay = round($basicPay + $overtimePay + $specialAdjustment, 2);

        // ---- Government deductions (monthly amounts halved per cutoff) ----
        $sssConfig  = $deductions['sss']  ?? null;
        $phConfig   = $deductions['philhealth'] ?? null;
        $piConfig   = $deductions['pagibig'] ?? null;

        // SSS
        $sssMonthly = 0.0;
        if ($sssConfig) {
            $rate = (float) $sssConfig['rate'];
            $computed = $monthlySalary * $rate;
            $max = $sssConfig['max_amount'] ? (float) $sssConfig['max_amount'] : PHP_FLOAT_MAX;
            $min = $sssConfig['min_amount'] ? (float) $sssConfig['min_amount'] : 0;
            $sssMonthly = max($min, min($max, $computed));
        }

        // PhilHealth
        $phMonthly = 0.0;
        if ($phConfig) {
            $rate = (float) $phConfig['rate'];
            $computed = $monthlySalary * $rate;
            $max = $phConfig['max_amount'] ? (float) $phConfig['max_amount'] : PHP_FLOAT_MAX;
            $phMonthly = min($max, $computed);
        }

        // Pag-IBIG
        $piMonthly = 0.0;
        if ($piConfig) {
            $rate = (float) $piConfig['rate'];
            $computed = $monthlySalary * $rate;
            $max = $piConfig['max_amount'] ? (float) $piConfig['max_amount'] : PHP_FLOAT_MAX;
            $min = $piConfig['min_amount'] ? (float) $piConfig['min_amount'] : 0;
            $piMonthly = max($min, min($max, $computed));
        }

        // Deductions split per cutoff (monthly / 2)
        $sssDed   = round($sssMonthly / 2, 2);
        $phDed    = round($phMonthly / 2, 2);
        $piDed    = round($piMonthly / 2, 2);
        $benDed   = round($benefitsDeduction, 2);
        $otherDed = round($otherDeductions, 2);
        $totalDed = round($sssDed + $phDed + $piDed + $benDed + $otherDed, 2);
        $netPay   = round($grossPay - $totalDed, 2);

        return [
            'working_days'          => $workingDays,
            'days_worked'           => $daysWorked,
            'whole_days'            => $attendance['whole_days'],
            'half_days'             => $attendance['half_days'],
            'absent_days'           => $attendance['absent_days'],
            'overtime_hours'        => $otHours,
            'basic_pay'             => $basicPay,
            'overtime_pay'          => $overtimePay,
            'special_adjustments'   => round($specialAdjustment, 2),
            'gross_pay'             => $grossPay,
            'sss_deduction'         => $sssDed,
            'philhealth_deduction'  => $phDed,
            'pagibig_deduction'     => $piDed,
            'other_deductions'      => $otherDed,
            'benefits_deduction'    => $benDed,
            'total_deductions'      => $totalDed,
            'net_pay'               => $netPay,
        ];
    }
}
