<?php

namespace App\Models;

use CodeIgniter\Model;

class BenefitAssignmentModel extends Model
{
    protected $table         = 'benefit_assignments';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'benefit_id', 'scope', 'department', 'employee_id',
        'cutoff', 'effective_date', 'status', 'notes',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /** All assignments for a specific benefit, with employee/dept info. */
    public function listForBenefit(int $benefitId): array
    {
        return $this->db->table('benefit_assignments ba')
            ->select([
                'ba.*',
                'e.full_name AS employee_name',
                'e.employee_code',
                'e.department AS employee_department',
            ])
            ->join('employees e', 'e.id = ba.employee_id', 'left')
            ->where('ba.benefit_id', $benefitId)
            ->orderBy('ba.scope', 'ASC')
            ->orderBy('ba.department', 'ASC')
            ->orderBy('e.full_name', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get all active benefit assignments applicable to an employee in a given payroll cutoff.
     *
     * @param int    $empId        Employee ID
     * @param string $department   Employee's department name
     * @param int    $payrollCutoff 1 = 1st cutoff (15th), 2 = 2nd cutoff (end of month)
     */
    public function getForEmployee(int $empId, string $department, int $payrollCutoff): array
    {
        // Determine which cutoff values to include
        $cutoffValues = ['both'];
        if ($payrollCutoff === 1) {
            $cutoffValues[] = '15';
        } else {
            $cutoffValues[] = '30';
        }

        return $this->db->table('benefit_assignments ba')
            ->select([
                'ba.*',
                'b.name AS benefit_name',
                'b.employee_share AS default_employee_share',
                'b.employer_share AS default_employer_share',
            ])
            ->join('benefits b', 'b.id = ba.benefit_id')
            ->where('ba.status', 'active')
            ->where('b.is_active', 1)
            ->groupStart()
                ->groupStart()
                    ->where('ba.scope', 'department')
                    ->where('ba.department', $department)
                ->groupEnd()
                ->orGroupStart()
                    ->where('ba.scope', 'employee')
                    ->where('ba.employee_id', $empId)
                ->groupEnd()
            ->groupEnd()
            ->whereIn('ba.cutoff', $cutoffValues)
            ->get()
            ->getResultArray();
    }

    /**
     * Get benefit contribution rows for the summary report.
     * Pulls actual deductions from payroll_details (SSS / PhilHealth / Pag-IBIG)
     * and uses the benefits table to derive the employer share ratio.
     */
    public function getSummaryRows(array $filters = []): array
    {
        $month       = $filters['month']        ?? date('Y-m');
        $cutoffF     = $filters['cutoff']       ?? '';
        $benefitType = $filters['benefit_type'] ?? '';
        $search      = $filters['search']       ?? '';

        // Build employer-share ratio map from benefits master table
        // e.g. SSS employee_share=1125, employer_share=2375  → ratio 2.111
        $ratioMap  = [];
        $typeNames = [];
        $benefitMaster = $this->db->table('benefits')
            ->where('is_active', 1)
            ->orderBy('name')
            ->get()->getResultArray();
        foreach ($benefitMaster as $b) {
            $typeNames[] = $b['name'];
            $empRef = (float) $b['employee_share'];
            $ratioMap[$b['name']] = $empRef > 0
                ? (float) $b['employer_share'] / $empRef
                : 1.0;
        }

        // Map benefit name → payroll_details column
        // Keys must match benefits.name exactly (case-sensitive)
        $colMap = [
            'SSS'        => 'pd.sss_deduction',
            'PhilHealth' => 'pd.philhealth_deduction',
            'Pag-IBIG'   => 'pd.pagibig_deduction',
        ];

        // Pull raw payroll detail rows for the selected month / cutoff
        $builder = $this->db->table('payroll_details pd')
            ->select('e.employee_code, e.full_name, e.department,
                      pd.sss_deduction, pd.philhealth_deduction, pd.pagibig_deduction,
                      p.cutoff, p.payroll_month')
            ->join('payroll p', 'p.id = pd.payroll_id')
            ->join('employees e', 'e.id = pd.employee_id');

        if ($month !== '') {
            $builder->where('p.payroll_month', $month);
        }
        if ($cutoffF !== '') {
            $cutoffInt = ($cutoffF === '15') ? 1 : 2;
            $builder->where('p.cutoff', $cutoffInt);
        }
        if ($search !== '') {
            $builder->groupStart()
                        ->like('e.full_name', $search)
                        ->orLike('e.employee_code', $search)
                    ->groupEnd();
        }

        $detailRows = $builder->get()->getResultArray();

        // Pivot each detail row into one row per benefit type
        $result = [];
        foreach ($detailRows as $row) {
            $cutoffLabel = ($row['cutoff'] == 1) ? '15' : '30';

            foreach ($colMap as $name => $col) {
                // Filter by benefit type if requested
                if ($benefitType !== '' && $name !== $benefitType) {
                    continue;
                }

                // Column key without table prefix for array access
                $colKey  = ltrim(strstr($col, '.'), '.');
                $empShare = (float) ($row[$colKey] ?? 0);
                if ($empShare <= 0) {
                    continue;
                }

                $ratio    = $ratioMap[$name] ?? 1.0;
                $emrShare = round($empShare * $ratio, 2);

                $result[] = [
                    'benefit_name'   => $name,
                    'employee_code'  => $row['employee_code'],
                    'full_name'      => $row['full_name'],
                    'department'     => $row['department'] ?? '—',
                    'cutoff'         => $cutoffLabel,
                    'employee_share' => $empShare,
                    'employer_share' => $emrShare,
                ];
            }
        }

        usort($result, fn($a, $b) =>
            [$a['benefit_name'], $a['full_name']] <=> [$b['benefit_name'], $b['full_name']]
        );

        return $result;
    }
}
