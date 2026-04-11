<?php

namespace App\Models;

use CodeIgniter\Model;

class PayrollModel extends Model
{
    protected $table         = 'payroll';
    protected $primaryKey    = 'id';
    protected $useAutoIncrement = true;
    protected $returnType    = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'payroll_month', 'cutoff', 'period_start', 'period_end',
        'working_days', 'total_gross', 'total_deductions', 'total_net',
        'status', 'created_by',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Get payroll with creator info, optionally filtered by year and/or month.
     */
    public function getAllWithCreator(string $year = '', string $month = ''): array
    {
        $builder = $this->db->table('payroll p')
            ->select('p.*, u.full_name AS created_by_name')
            ->join('users u', 'u.id = p.created_by', 'left');

        if ($year !== '') {
            $builder->where('YEAR(p.period_start)', $year);
        }
        if ($month !== '') {
            $builder->where('MONTH(p.period_start)', $month);
        }

        return $builder->orderBy('p.period_start', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Period label: e.g. "April 2026 – 1st Cutoff (Apr 1–15)"
     */
    public static function periodLabel(array $payroll): string
    {
        $start  = date('M j', strtotime($payroll['period_start']));
        $end    = date('M j, Y', strtotime($payroll['period_end']));
        $cutoff = $payroll['cutoff'] == 1 ? '1st' : '2nd';
        return "{$cutoff} Cutoff ({$start} – {$end})";
    }

    /**
     * Compute period dates for a given month and cutoff.
     * Returns ['start' => 'Y-m-d', 'end' => 'Y-m-d', 'working_days' => n]
     */
    public static function computePeriod(string $yearMonth, int $cutoff): array
    {
        [$year, $month] = explode('-', $yearMonth);
        if ($cutoff === 1) {
            $start = "{$yearMonth}-01";
            $end   = "{$yearMonth}-15";
        } else {
            $start = "{$yearMonth}-16";
            $end   = date('Y-m-t', strtotime("{$yearMonth}-01")); // last day of month
        }
        // Count Mon-Fri in range (exclude weekends) as working days
        $workingDays = 0;
        $current = strtotime($start);
        $endTs   = strtotime($end);
        while ($current <= $endTs) {
            $dow = (int) date('N', $current); // 1=Mon…7=Sun
            if ($dow <= 5) {
                $workingDays++;
            }
            $current = strtotime('+1 day', $current);
        }
        return ['start' => $start, 'end' => $end, 'working_days' => $workingDays];
    }
}
