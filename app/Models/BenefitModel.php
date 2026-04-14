<?php

namespace App\Models;

use CodeIgniter\Model;

class BenefitModel extends Model
{
    protected $table         = 'benefits';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'name', 'employee_id', 'amount', 'employer_contribution',
        'description', 'employee_share', 'employer_share', 'is_active',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /** All benefits with assignment count. */
    public function listWithCounts(): array
    {
        return $this->db->table('benefits b')
            ->select('b.*, COUNT(ba.id) AS assignment_count')
            ->join('benefit_assignments ba', 'ba.benefit_id = b.id AND ba.status = "active"', 'left')
            ->where('b.employee_id IS NULL')
            ->groupBy('b.id')
            ->orderBy('b.name', 'ASC')
            ->get()
            ->getResultArray();
    }

    /** Active benefits only (global, no employee_id). */
    public function getActive(): array
    {
        return $this->where('is_active', 1)->where('employee_id IS NULL', null, false)->orderBy('name', 'ASC')->findAll();
    }

    /**
     * Get active per-employee benefit records (SSS, PhilHealth, Pag-IBIG).
     */
    public function getByEmployee(int $empId): array
    {
        return $this->where('employee_id', $empId)
                    ->where('is_active', 1)
                    ->findAll();
    }

    /**
     * Create or update a per-employee benefit record.
     * If amount = 0 and record exists, deactivates it.
     */
    public function upsertForEmployee(int $empId, string $name, float $amount): void
    {
        $existing = $this->where('employee_id', $empId)->where('name', $name)->first();

        $data = [
            'employee_id'          => $empId,
            'name'                 => $name,
            'amount'               => $amount,
            'employer_contribution' => $amount,
            'employee_share'       => $amount,
            'employer_share'       => $amount,
            'is_active'            => $amount > 0 ? 1 : 0,
        ];

        if ($existing) {
            $this->update($existing['id'], $data);
        } elseif ($amount > 0) {
            $this->insert($data);
        }
    }
}
