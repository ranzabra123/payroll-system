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
    public function getAllWithCreator(string $year = '', string $month = '', string $branchId = ''): array
    {
        $bid = $branchId !== '' ? (int) $branchId : 0;

        // Employee count subquery — branch-scoped when branch filter active
        $empCount = $bid
            ? "(SELECT COUNT(*) FROM payroll_details pd JOIN employees e ON e.id = pd.employee_id WHERE pd.payroll_id = p.id AND e.branch_id = {$bid})"
            : '(SELECT COUNT(*) FROM payroll_details pd WHERE pd.payroll_id = p.id)';

        // Branch-specific financial subqueries (NULL when no branch filter)
        $branchSelect = $bid ? ",
            (SELECT COALESCE(SUM(pd.gross_pay),0)        FROM payroll_details pd JOIN employees e ON e.id = pd.employee_id WHERE pd.payroll_id = p.id AND e.branch_id = {$bid}) AS branch_gross,
            (SELECT COALESCE(SUM(pd.total_deductions),0) FROM payroll_details pd JOIN employees e ON e.id = pd.employee_id WHERE pd.payroll_id = p.id AND e.branch_id = {$bid}) AS branch_deductions,
            (SELECT COALESCE(SUM(pd.net_pay),0)          FROM payroll_details pd JOIN employees e ON e.id = pd.employee_id WHERE pd.payroll_id = p.id AND e.branch_id = {$bid}) AS branch_net" : '';

        $builder = $this->db->table('payroll p')
            ->select("p.*, u.full_name AS created_by_name, {$empCount} AS employee_count" . $branchSelect)
            ->join('users u', 'u.id = p.created_by', 'left');

        if ($year !== '') {
            $builder->where('YEAR(p.period_start)', $year);
        }
        if ($month !== '') {
            $builder->where('MONTH(p.period_start)', $month);
        }
        if ($bid) {
            $builder->where("EXISTS (
                SELECT 1 FROM payroll_details pd
                JOIN employees e ON e.id = pd.employee_id
                WHERE pd.payroll_id = p.id AND e.branch_id = {$bid}
            )");
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
     *
     * Working days are calendar-based (not dept-specific):
     *   28 or 30-day month: both cutoffs = 15 days
     *   31-day month: 1st cutoff = 15 days, 2nd cutoff = 16 days
     */
    public static function computePeriod(string $yearMonth, int $cutoff): array
    {
        if ($cutoff === 1) {
            $start = "{$yearMonth}-01";
            $end   = "{$yearMonth}-15";
        } else {
            $start = "{$yearMonth}-16";
            $end   = date('Y-m-t', strtotime("{$yearMonth}-01")); // last day of month
        }

        $calendarDays = (int) date('t', strtotime("{$yearMonth}-01"));
        if ($calendarDays === 31) {
            $workingDays = ($cutoff === 1) ? 15 : 16;
        } else {
            $workingDays = 15; // 28 and 30-day months: both cutoffs = 15
        }

        return ['start' => $start, 'end' => $end, 'working_days' => $workingDays];
    }
}
