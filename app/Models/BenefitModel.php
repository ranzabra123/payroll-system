<?php

namespace App\Models;

use CodeIgniter\Model;

class BenefitModel extends Model
{
    protected $table         = 'benefits';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'name', 'description', 'employee_share', 'employer_share', 'is_active',
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
            ->groupBy('b.id')
            ->orderBy('b.name', 'ASC')
            ->get()
            ->getResultArray();
    }

    /** Active benefits only. */
    public function getActive(): array
    {
        return $this->where('is_active', 1)->orderBy('name', 'ASC')->findAll();
    }
}
