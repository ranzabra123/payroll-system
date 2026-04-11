<?php

namespace App\Models;

use CodeIgniter\Model;

class DeductionConfigModel extends Model
{
    protected $table         = 'deductions_config';
    protected $primaryKey    = 'id';
    protected $useAutoIncrement = true;
    protected $returnType    = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'deduction_type', 'rate', 'max_amount', 'min_amount',
        'effective_date', 'is_active',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Get all active configs keyed by deduction_type.
     */
    public function getActiveConfigsKeyed(): array
    {
        $rows   = $this->where('is_active', 1)->findAll();
        $keyed  = [];
        foreach ($rows as $row) {
            $keyed[$row['deduction_type']] = $row;
        }
        return $keyed;
    }
}
