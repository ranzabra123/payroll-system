<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\SpecialDayModel;
use App\Models\EmployeeModel;
use App\Models\DepartmentModel;
use App\Models\AuditLogModel;

/**
 * SpecialDaysController – manage special day payroll adjustments.
 * A special day can add a fixed amount or double the daily salary for a specific date.
 */
class SpecialDaysController extends Controller
{
    protected SpecialDayModel  $model;
    protected EmployeeModel    $empModel;
    protected DepartmentModel  $deptModel;
    protected AuditLogModel    $audit;

    public function __construct()
    {
        $this->model     = new SpecialDayModel();
        $this->empModel  = new EmployeeModel();
        $this->deptModel = new DepartmentModel();
        $this->audit     = new AuditLogModel();
    }

    /** List all special day adjustments. */
    public function index()
    {
        $filters = [
            'search'          => $this->request->getGet('search'),
            'employee_id'     => $this->request->getGet('employee_id'),
            'adjustment_type' => $this->request->getGet('adjustment_type'),
            'status'          => $this->request->getGet('status'),
            'date_from'       => $this->request->getGet('date_from'),
            'date_to'         => $this->request->getGet('date_to'),
        ];

        return view('special_days/index', [
            'title'     => 'Special Day Adjustments',
            'records'   => $this->model->listWithEmployee($filters),
            'employees' => $this->empModel->getActiveList(),
            'filters'   => $filters,
        ]);
    }

    /** Show create form. */
    public function create()
    {
        return view('special_days/create', [
            'title'       => 'Add Special Day Adjustment',
            'employees'   => $this->empModel->getActiveList(),
            'departments' => $this->deptModel->getActiveList(),
            'deptCounts'  => $this->empModel->getCountsByDepartment(),
        ]);
    }

    /** Store new special day adjustment (bulk by dept or single employee). */
    public function store()
    {
        $scope  = $this->request->getPost('scope');           // 'department' or 'employee'
        $type   = $this->request->getPost('adjustment_type');
        $date   = $this->request->getPost('date');
        $amount = $type === 'fixed_amount' ? $this->request->getPost('amount') : null;
        $reason = $this->request->getPost('reason');

        // Common validation
        $rules = [
            'date'            => 'required|valid_date[Y-m-d]',
            'adjustment_type' => 'required|in_list[fixed_amount,double_salary]',
        ];
        if ($type === 'fixed_amount') {
            $rules['amount'] = 'required|decimal|greater_than[0]';
        }

        if ($scope === 'department') {
            $selectedDepts = (array) $this->request->getPost('departments');
            if (empty($selectedDepts)) {
                return redirect()->back()
                    ->with('errors', ['Please select at least one department.'])
                    ->withInput();
            }
        } else {
            $rules['employee_id'] = 'required|integer';
        }

        if (! $this->validate($rules)) {
            return redirect()->back()
                ->with('errors', $this->validator->getErrors())
                ->withInput();
        }

        if ($scope === 'department') {
            $employees = $this->empModel->getActiveByDepartments($selectedDepts);
            if (empty($employees)) {
                return redirect()->back()
                    ->with('errors', ['No active employees found in the selected departments.'])
                    ->withInput();
            }

            $rows = [];
            $now  = date('Y-m-d H:i:s');
            foreach ($employees as $emp) {
                $rows[] = [
                    'employee_id'     => $emp['id'],
                    'date'            => $date,
                    'adjustment_type' => $type,
                    'amount'          => $amount,
                    'reason'          => $reason,
                    'status'          => 'pending',
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ];
            }
            $this->db->table('payroll_special_days')->insertBatch($rows);
            $count = count($rows);
            $deptNames = implode(', ', $selectedDepts);
            $this->audit->logAction('SpecialDays', 'bulk_create', null, null,
                ['departments' => $selectedDepts],
                "Bulk special day ({$type}) on {$date} — {$count} employees in: {$deptNames}");

            return redirect()->to(site_url('special-days'))
                ->with('success', "Special day adjustment applied to {$count} employee(s) across " . count($selectedDepts) . ' department(s).');
        }

        // Single employee
        $singleEmpId = (int) $this->request->getPost('employee_id');
        $singleId = $this->model->insert([
            'employee_id'     => $singleEmpId,
            'date'            => $date,
            'adjustment_type' => $type,
            'amount'          => $amount,
            'reason'          => $reason,
            'status'          => 'pending',
        ]);

        $this->audit->logAction('SpecialDays', 'create', $singleId, null,
            ['employee_id' => $singleEmpId, 'date' => $date, 'adjustment_type' => $type, 'amount' => $amount],
            "Added special day ({$type}) for employee #{$singleEmpId} on {$date}" . ($amount ? ' — ₱' . number_format((float)$amount, 2) : ''));

        return redirect()->to(site_url('special-days'))
            ->with('success', 'Special day adjustment added.');
    }

    /** Show edit form. */
    public function edit(int $id)
    {
        $record = $this->model->find($id);
        if (! $record) {
            return redirect()->to(site_url('special-days'))->with('error', 'Record not found.');
        }
        if ($record['status'] === 'applied') {
            return redirect()->to(site_url('special-days'))
                ->with('error', 'Cannot edit an adjustment that has already been applied to payroll.');
        }

        return view('special_days/edit', [
            'title'     => 'Edit Special Day Adjustment',
            'record'    => $record,
            'employees' => $this->empModel->getActiveList(),
        ]);
    }

    /** Update special day adjustment. */
    public function update(int $id)
    {
        $record = $this->model->find($id);
        if (! $record) {
            return redirect()->to(site_url('special-days'))->with('error', 'Record not found.');
        }
        if ($record['status'] === 'applied') {
            return redirect()->to(site_url('special-days'))
                ->with('error', 'Cannot edit an adjustment already applied to payroll.');
        }

        $type   = $this->request->getPost('adjustment_type');
        $amount = $type === 'fixed_amount' ? $this->request->getPost('amount') : null;

        $rules = [
            'employee_id'     => 'required|integer',
            'date'            => 'required|valid_date[Y-m-d]',
            'adjustment_type' => 'required|in_list[fixed_amount,double_salary]',
        ];
        if ($type === 'fixed_amount') {
            $rules['amount'] = 'required|decimal|greater_than[0]';
        }

        if (! $this->validate($rules)) {
            return redirect()->back()
                ->with('errors', $this->validator->getErrors())
                ->withInput();
        }

        $this->model->update($id, [
            'employee_id'     => (int) $this->request->getPost('employee_id'),
            'date'            => $this->request->getPost('date'),
            'adjustment_type' => $type,
            'amount'          => $amount,
            'reason'          => $this->request->getPost('reason'),
        ]);

        $updatedType = $this->request->getPost('adjustment_type');
        $updatedDate = $this->request->getPost('date');
        $this->audit->logAction('SpecialDays', 'update', $id,
            ['date' => $record['date'], 'adjustment_type' => $record['adjustment_type'], 'amount' => $record['amount']],
            ['date' => $updatedDate, 'adjustment_type' => $updatedType, 'amount' => $amount],
            "Updated special day #{$id} ({$updatedType}) on {$updatedDate}");

        return redirect()->to(site_url('special-days'))
            ->with('success', 'Special day adjustment updated.');
    }

    /** Delete a special day adjustment. */
    public function delete(int $id)
    {
        $record = $this->model->find($id);
        if (! $record) {
            return redirect()->to(site_url('special-days'))->with('error', 'Record not found.');
        }
        if ($record['status'] === 'applied') {
            return redirect()->to(site_url('special-days'))
                ->with('error', 'Cannot delete an adjustment already applied to payroll.');
        }

        $this->model->delete($id);
        $this->audit->logAction('SpecialDays', 'delete', $id, $record, null,
            "Deleted special day #{$id} ({$record['adjustment_type']}) on {$record['date']}");

        return redirect()->to(site_url('special-days'))
            ->with('success', 'Special day adjustment deleted.');
    }
}
