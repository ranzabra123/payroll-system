<?php

namespace App\Models;

use CodeIgniter\Model;

class DepartmentModel extends Model
{
    protected $table         = 'departments';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['name', 'description', 'working_days', 'is_active'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'name' => 'required|min_length[2]|max_length[100]',
    ];

    /** Return active departments as simple name list. */
    public function getActiveList(): array
    {
        return $this->where('is_active', 1)->orderBy('name', 'ASC')->findAll();
    }

    /**
     * Return map of department name => working_days_per_month.
     * Used by PayrollController to get per-dept working days.
     */
    public function getWorkingDaysMap(): array
    {
        $rows = $this->select('name, working_days')->findAll();
        $map  = [];
        foreach ($rows as $row) {
            $map[$row['name']] = (int) ($row['working_days'] ?: 26);
        }
        return $map;
    }
}
