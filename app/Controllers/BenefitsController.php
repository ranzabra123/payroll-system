<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\BenefitModel;
use App\Models\BenefitAssignmentModel;
use App\Models\EmployeeModel;
use App\Models\DepartmentModel;
use App\Models\AuditLogModel;

class BenefitsController extends Controller
{
    protected BenefitModel $model;
    protected BenefitAssignmentModel $assignModel;
    protected AuditLogModel $audit;

    public function __construct()
    {
        $this->model       = new BenefitModel();
        $this->assignModel = new BenefitAssignmentModel();
        $this->audit       = new AuditLogModel();
    }

    // ---------------------------------------------------------------
    // Master benefit CRUD
    // ---------------------------------------------------------------

    public function index()
    {
        return view('benefits/index', [
            'title'    => 'Benefits',
            'benefits' => $this->model->listWithCounts(),
        ]);
    }

    public function create()
    {
        return view('benefits/create', [
            'title' => 'Add Benefit',
        ]);
    }

    public function store()
    {
        $rules = [
            'name'           => 'required|max_length[100]|is_unique[benefits.name]',
            'employee_share' => 'required|decimal|greater_than_equal_to[0]',
            'employer_share' => 'required|decimal|greater_than_equal_to[0]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->with('errors', $this->validator->getErrors())->withInput();
        }

        $data = [
            'name'           => $this->request->getPost('name', FILTER_SANITIZE_SPECIAL_CHARS),
            'description'    => $this->request->getPost('description', FILTER_SANITIZE_SPECIAL_CHARS),
            'employee_share' => (float) $this->request->getPost('employee_share'),
            'employer_share' => (float) $this->request->getPost('employer_share'),
            'is_active'      => 1,
        ];

        $id = $this->model->insert($data);
        $this->audit->logAction('Benefits', 'create', $id, null, $data,
            "Created benefit '{$data['name']}' (employee share: {$data['employee_share']}, employer: {$data['employer_share']})");

        return redirect()->to(site_url('benefits'))->with('success', 'Benefit "' . $data['name'] . '" created.');
    }

    public function edit(int $id)
    {
        $benefit = $this->model->find($id);
        if (! $benefit) {
            return redirect()->to(site_url('benefits'))->with('error', 'Benefit not found.');
        }

        return view('benefits/edit', [
            'title'   => 'Edit Benefit',
            'benefit' => $benefit,
        ]);
    }

    public function update(int $id)
    {
        $benefit = $this->model->find($id);
        if (! $benefit) {
            return redirect()->to(site_url('benefits'))->with('error', 'Benefit not found.');
        }

        $rules = [
            'name'           => "required|max_length[100]|is_unique[benefits.name,id,{$id}]",
            'employee_share' => 'required|decimal|greater_than_equal_to[0]',
            'employer_share' => 'required|decimal|greater_than_equal_to[0]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->with('errors', $this->validator->getErrors())->withInput();
        }

        $data = [
            'name'           => $this->request->getPost('name', FILTER_SANITIZE_SPECIAL_CHARS),
            'description'    => $this->request->getPost('description', FILTER_SANITIZE_SPECIAL_CHARS),
            'employee_share' => (float) $this->request->getPost('employee_share'),
            'employer_share' => (float) $this->request->getPost('employer_share'),
            'is_active'      => (int) $this->request->getPost('is_active'),
        ];

        $this->model->update($id, $data);
        $this->audit->logAction('Benefits', 'update', $id, $benefit, $data,
            "Updated benefit '{$data['name']}'");

        return redirect()->to(site_url('benefits'))->with('success', 'Benefit updated.');
    }

    public function delete(int $id)
    {
        $benefit = $this->model->find($id);
        if (! $benefit) {
            return redirect()->to(site_url('benefits'))->with('error', 'Benefit not found.');
        }

        $this->model->delete($id);
        $this->audit->logAction('Benefits', 'delete', $id, $benefit, null,
            "Deleted benefit '{$benefit['name']}'");

        return redirect()->to(site_url('benefits'))->with('success', 'Benefit deleted.');
    }

    // ---------------------------------------------------------------
    // Assignment management (assign benefit to dept or employee)
    // ---------------------------------------------------------------

    public function assign(int $benefitId)
    {
        $benefit = $this->model->find($benefitId);
        if (! $benefit) {
            return redirect()->to(site_url('benefits'))->with('error', 'Benefit not found.');
        }

        $assignments  = $this->assignModel->listForBenefit($benefitId);
        $departments  = (new DepartmentModel())->getActiveList();
        $employees    = (new EmployeeModel())->where('status', 'active')->orderBy('full_name')->findAll();

        return view('benefits/assign', [
            'title'       => 'Assign: ' . esc($benefit['name']),
            'benefit'     => $benefit,
            'assignments' => $assignments,
            'departments' => $departments,
            'employees'   => $employees,
        ]);
    }

    public function assignStore(int $benefitId)
    {
        $benefit = $this->model->find($benefitId);
        if (! $benefit) {
            return redirect()->to(site_url('benefits'))->with('error', 'Benefit not found.');
        }

        $scope = $this->request->getPost('scope');

        $rules = [
            'scope'          => 'required|in_list[department,employee]',
            'cutoff'         => 'required|in_list[both,15,30]',
            'effective_date' => 'required|valid_date[Y-m-d]',
        ];
        if ($scope === 'department') {
            $rules['department'] = 'required|max_length[100]';
        } else {
            $rules['employee_id'] = 'required|is_natural_no_zero';
        }

        if (! $this->validate($rules)) {
            return redirect()->back()->with('errors', $this->validator->getErrors())->withInput();
        }

        $data = [
            'benefit_id'     => $benefitId,
            'scope'          => $scope,
            'department'     => $scope === 'department' ? $this->request->getPost('department', FILTER_SANITIZE_SPECIAL_CHARS) : null,
            'employee_id'    => $scope === 'employee'   ? (int) $this->request->getPost('employee_id') : null,
            'cutoff'         => $this->request->getPost('cutoff'),
            'effective_date' => $this->request->getPost('effective_date'),
            'status'         => 'active',
            'notes'          => $this->request->getPost('notes', FILTER_SANITIZE_SPECIAL_CHARS),
        ];

        $id = $this->assignModel->insert($data);
        $scopeDesc = $scope === 'department' ? $data['department'] : 'Employee #' . $data['employee_id'];
        $this->audit->logAction('BenefitAssignment', 'create', $id, null, $data,
            "Assigned benefit '{$benefit['name']}' to {$scopeDesc}");

        return redirect()->to(site_url('benefits/assign/' . $benefitId))->with('success', 'Assignment added.');
    }

    public function assignDelete(int $assignId)
    {
        $assignment = $this->assignModel->find($assignId);
        if (! $assignment) {
            return redirect()->to(site_url('benefits'))->with('error', 'Assignment not found.');
        }

        $benefitId = $assignment['benefit_id'];
        $this->assignModel->delete($assignId);
        $scopeDesc = $assignment['scope'] === 'department' ? $assignment['department'] : 'Employee #' . $assignment['employee_id'];
        $this->audit->logAction('BenefitAssignment', 'delete', $assignId, $assignment, null,
            "Removed benefit assignment #{$assignId} for {$scopeDesc}");

        return redirect()->to(site_url('benefits/assign/' . $benefitId))->with('success', 'Assignment removed.');
    }

    // ---------------------------------------------------------------
    // Summary report
    // ---------------------------------------------------------------

    public function summary()
    {
        $month       = $this->request->getGet('month')        ?? '';
        $cutoffF     = $this->request->getGet('cutoff')       ?? '';
        $benefitType = $this->request->getGet('benefit_type') ?? '';
        $search      = $this->request->getGet('q')            ?? '';

        if ($month === '') {
            $month = date('Y-m');
        }

        $benefitTypes = $this->model
            ->where('is_active', 1)
            ->orderBy('name')
            ->findColumn('name') ?? [];

        $rows = $this->assignModel->getSummaryRows([
            'month'        => $month,
            'cutoff'       => $cutoffF,
            'benefit_type' => $benefitType,
            'search'       => $search,
        ]);

        $grouped       = [];
        $grandEmpShare = 0.0;
        $grandEmrShare = 0.0;

        foreach ($rows as $r) {
            $type = $r['benefit_name'];
            if (! isset($grouped[$type])) {
                $grouped[$type] = ['rows' => [], 'emp_total' => 0.0, 'emr_total' => 0.0];
            }
            $grouped[$type]['rows'][]      = $r;
            $grouped[$type]['emp_total']  += (float) $r['employee_share'];
            $grouped[$type]['emr_total']  += (float) $r['employer_share'];
            $grandEmpShare += (float) $r['employee_share'];
            $grandEmrShare += (float) $r['employer_share'];
        }

        return view('benefits/summary', [
            'title'        => 'Benefits Summary',
            'filters'      => ['month' => $month, 'cutoff' => $cutoffF, 'benefit_type' => $benefitType, 'search' => $search],
            'benefitTypes' => $benefitTypes,
            'grouped'      => $grouped,
            'grandEmpShare' => $grandEmpShare,
            'grandEmrShare' => $grandEmrShare,
            'grandTotal'    => $grandEmpShare + $grandEmrShare,
        ]);
    }
}
