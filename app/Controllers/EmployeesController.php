<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\EmployeeModel;
use App\Models\SalaryHistoryModel;
use App\Models\AttendanceModel;
use App\Models\AuditLogModel;
use App\Models\BenefitModel;

/**
 * EmployeesController – full CRUD for employee records.
 */
class EmployeesController extends Controller
{
    protected EmployeeModel $model;
    protected AuditLogModel $audit;

    public function __construct()
    {
        $this->model = new EmployeeModel();
        $this->audit = new AuditLogModel();
    }

    public function index()
    {
        $search   = $this->request->getGet('q');
        // Respect user's branch restriction; allow further filter by URL only within restriction
        $userBranch = user_branch_id();
        $branchId   = $userBranch ?? ((int) $this->request->getGet('branch_id') ?: null);

        if ($search) {
            $employees = $this->model->search($search, $branchId);
        } elseif ($branchId) {
            $employees = $this->model->where('branch_id', $branchId)
                ->orderBy('FIELD(status,"active","inactive")', 'ASC', false)
                ->orderBy('full_name', 'ASC')->findAll();
        } else {
            $employees = $this->model
                ->orderBy('FIELD(status,"active","inactive")', 'ASC', false)
                ->orderBy('full_name', 'ASC')->findAll();
        }

        $branches = (new \App\Models\BranchModel())->getActiveList();

        return view('employees/index', [
            'title'       => 'Employees',
            'employees'   => $employees,
            'search'      => $search,
            'branches'    => $branches,
            'branchId'    => $branchId,
            'userBranch'  => $userBranch,
        ]);
    }

    public function create()
    {
        $userBranch = user_branch_id();
        return view('employees/create', [
            'title'       => 'Add Employee',
            'departments' => (new \App\Models\DepartmentModel())->getActiveList(),
            'branches'    => (new \App\Models\BranchModel())->getActiveList(),
            'userBranch'  => $userBranch,
        ]);
    }

    public function store()
    {
        $rules = [
            'full_name'      => 'required|min_length[3]|max_length[150]',
            'position'       => 'required|min_length[2]',
            'monthly_salary' => 'required|decimal|greater_than[0]',
            'date_hired'     => 'required|valid_date[Y-m-d]',
            'status'         => 'required|in_list[active,inactive]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->with('errors', $this->validator->getErrors())->withInput();
        }

        $monthly = (float) $this->request->getPost('monthly_salary');
        $userBranch = user_branch_id();
        $branchId = $userBranch ?? ((int) $this->request->getPost('branch_id') ?: null);
        $data = [
            'employee_code'     => $this->model->generateEmployeeCode(),
            'full_name'         => $this->request->getPost('full_name', FILTER_SANITIZE_SPECIAL_CHARS),
            'position'          => $this->request->getPost('position', FILTER_SANITIZE_SPECIAL_CHARS),
            'department'        => $this->request->getPost('department', FILTER_SANITIZE_SPECIAL_CHARS),
            'branch_id'         => $branchId,
            'monthly_salary'    => $monthly,
            'daily_rate'        => round($monthly / 22, 4),
            'date_hired'        => $this->request->getPost('date_hired'),
            'gender'            => $this->request->getPost('gender') ?: null,
            'sss_number'        => $this->request->getPost('sss_number', FILTER_SANITIZE_SPECIAL_CHARS),
            'sss_contribution'  => (float) $this->request->getPost('sss_contribution'),
            'philhealth_number' => $this->request->getPost('philhealth_number', FILTER_SANITIZE_SPECIAL_CHARS),
            'philhealth_contribution' => (float) $this->request->getPost('philhealth_contribution'),
            'pagibig_number'    => $this->request->getPost('pagibig_number', FILTER_SANITIZE_SPECIAL_CHARS),
            'pagibig_contribution'    => (float) $this->request->getPost('pagibig_contribution'),
            'tin_number'        => $this->request->getPost('tin_number', FILTER_SANITIZE_SPECIAL_CHARS),
            'status'            => $this->request->getPost('status'),
        ];

        $id = $this->model->insert($data);

        // Create per-employee benefit records
        $benefitModel = new BenefitModel();
        $benefitModel->upsertForEmployee($id, 'SSS',       $data['sss_contribution']);
        $benefitModel->upsertForEmployee($id, 'PhilHealth', $data['philhealth_contribution']);
        $benefitModel->upsertForEmployee($id, 'Pag-IBIG',  $data['pagibig_contribution']);
        $this->audit->logAction('Employees', 'create', $id, null, $data,
            "Added employee '{$data['full_name']}' ({$data['employee_code']}) — {$data['department']} — ₱" . number_format($data['monthly_salary'], 2) . "/mo");

        return redirect()->to(site_url('employees'))->with('success', 'Employee added successfully.');
    }

    public function view(int $id)
    {
        $employee = $this->model->find($id);
        if (! $employee || ! can_access_branch($employee['branch_id'])) {
            return redirect()->to(site_url('employees'))->with('error', 'Employee not found.');
        }

        $salaryHistory = (new SalaryHistoryModel())->getByEmployee($id);
        $attendance30  = (new AttendanceModel())
            ->where('employee_id', $id)
            ->where('attendance_date >=', date('Y-m-d', strtotime('-30 days')))
            ->orderBy('attendance_date', 'DESC')
            ->findAll();

        return view('employees/view', [
            'title'         => $employee['full_name'],
            'employee'      => $employee,
            'salaryHistory' => $salaryHistory,
            'attendance30'  => $attendance30,
        ]);
    }

    public function edit(int $id)
    {
        $employee = $this->model->find($id);
        if (! $employee || ! can_access_branch($employee['branch_id'])) {
            return redirect()->to(site_url('employees'))->with('error', 'Employee not found.');
        }
        return view('employees/edit', [
            'title'       => 'Edit Employee',
            'employee'    => $employee,
            'departments' => (new \App\Models\DepartmentModel())->getActiveList(),
            'branches'    => (new \App\Models\BranchModel())->getActiveList(),
        ]);
    }

    public function update(int $id)
    {
        $employee = $this->model->find($id);
        if (! $employee || ! can_access_branch($employee['branch_id'])) {
            return redirect()->to(site_url('employees'))->with('error', 'Employee not found.');
        }

        $rules = [
            'full_name'      => 'required|min_length[3]|max_length[150]',
            'position'       => 'required|min_length[2]',
            'monthly_salary' => 'required|decimal|greater_than[0]',
            'date_hired'     => 'required|valid_date[Y-m-d]',
            'status'         => 'required|in_list[active,inactive]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->with('errors', $this->validator->getErrors())->withInput();
        }

        $monthly = (float) $this->request->getPost('monthly_salary');
        $userBranch = user_branch_id();
        $branchId = $userBranch ?? ((int) $this->request->getPost('branch_id') ?: null);
        $data = [
            'full_name'               => $this->request->getPost('full_name', FILTER_SANITIZE_SPECIAL_CHARS),
            'position'                => $this->request->getPost('position', FILTER_SANITIZE_SPECIAL_CHARS),
            'department'              => $this->request->getPost('department', FILTER_SANITIZE_SPECIAL_CHARS),
            'branch_id'               => $branchId,
            'monthly_salary'          => $monthly,
            'daily_rate'              => round($monthly / 22, 4),
            'date_hired'              => $this->request->getPost('date_hired'),
            'gender'                  => $this->request->getPost('gender') ?: null,
            'sss_number'              => $this->request->getPost('sss_number', FILTER_SANITIZE_SPECIAL_CHARS),
            'sss_contribution'        => (float) $this->request->getPost('sss_contribution'),
            'philhealth_number'       => $this->request->getPost('philhealth_number', FILTER_SANITIZE_SPECIAL_CHARS),
            'philhealth_contribution' => (float) $this->request->getPost('philhealth_contribution'),
            'pagibig_number'          => $this->request->getPost('pagibig_number', FILTER_SANITIZE_SPECIAL_CHARS),
            'pagibig_contribution'    => (float) $this->request->getPost('pagibig_contribution'),
            'tin_number'              => $this->request->getPost('tin_number', FILTER_SANITIZE_SPECIAL_CHARS),
            'status'                  => $this->request->getPost('status'),
        ];

        // Track salary change
        if ((float) $employee['monthly_salary'] !== $monthly) {
            (new SalaryHistoryModel())->insert([
                'employee_id'     => $id,
                'previous_salary' => $employee['monthly_salary'],
                'new_salary'      => $monthly,
                'effective_date'  => date('Y-m-d'),
                'reason'          => 'Salary updated by ' . session()->get('full_name'),
                'changed_by'      => session()->get('user_id'),
            ]);
        }

        $this->model->update($id, $data);

        // Sync per-employee benefit records
        $benefitModel = new BenefitModel();
        $benefitModel->upsertForEmployee($id, 'SSS',       $data['sss_contribution']);
        $benefitModel->upsertForEmployee($id, 'PhilHealth', $data['philhealth_contribution']);
        $benefitModel->upsertForEmployee($id, 'Pag-IBIG',  $data['pagibig_contribution']);

        $summaryParts = ["Updated employee #{$id} '{$data['full_name']}'"];
        if ((float)$employee['monthly_salary'] !== (float)$data['monthly_salary']) {
            $summaryParts[] = 'salary: ₱' . number_format((float)$employee['monthly_salary'], 2) . ' → ₱' . number_format((float)$data['monthly_salary'], 2);
        }
        if ($employee['status'] !== $data['status']) {
            $summaryParts[] = 'status: ' . $employee['status'] . ' → ' . $data['status'];
        }
        $this->audit->logAction('Employees', 'update', $id, $employee, $data,
            implode(' — ', $summaryParts));

        return redirect()->to(site_url('employees'))->with('success', 'Employee updated successfully.');
    }

    public function delete(int $id)
    {
        $employee = $this->model->find($id);
        if (! $employee || ! can_access_branch($employee['branch_id'])) {
            return redirect()->to(site_url('employees'))->with('error', 'Employee not found.');
        }

        $this->model->delete($id);
        $this->audit->logAction('Employees', 'delete', $id, $employee, null,
            "Deleted employee '{$employee['full_name']}' ({$employee['employee_code']})");

        return redirect()->to(site_url('employees'))->with('success', 'Employee deleted successfully.');
    }

    /** Employee DTR (daily time record) for current month. */
    public function dtr(int $id)
    {
        $employee = $this->model->find($id);
        if (! $employee || ! can_access_branch($employee['branch_id'])) {
            return redirect()->to(site_url('employees'))->with('error', 'Employee not found.');
        }

        $month = $this->request->getGet('month') ?? date('Y-m');
        $start = $month . '-01';
        $end   = date('Y-m-t', strtotime($start));

        $records = (new AttendanceModel())
            ->where('employee_id', $id)
            ->where('attendance_date >=', $start)
            ->where('attendance_date <=', $end)
            ->orderBy('attendance_date', 'ASC')
            ->findAll();

        return view('employees/dtr', [
            'title'    => 'DTR – ' . $employee['full_name'],
            'employee' => $employee,
            'records'  => $records,
            'month'    => $month,
            'start'    => $start,
            'end'      => $end,
        ]);
    }
}
