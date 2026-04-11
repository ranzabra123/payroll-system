<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\EmployeeDeductionModel;
use App\Models\EmployeeModel;
use App\Models\AuditLogModel;

class DeductionsController extends Controller
{
    protected EmployeeDeductionModel $model;
    protected AuditLogModel $audit;

    public function __construct()
    {
        $this->model = new EmployeeDeductionModel();
        $this->audit = new AuditLogModel();
    }

    public function index()
    {
        $filters = [
            'search' => $this->request->getGet('q'),
            'type'   => $this->request->getGet('type'),
            'status' => $this->request->getGet('status'),
            'cutoff' => $this->request->getGet('cutoff'),
        ];

        return view('deductions/index', [
            'title'      => 'Employee Deductions',
            'deductions' => $this->model->listWithEmployee($filters),
            'filters'    => $filters,
        ]);
    }

    public function create()
    {
        return view('deductions/create', [
            'title'     => 'Add Deduction',
            'employees' => (new EmployeeModel())->where('status', 'active')->orderBy('full_name')->findAll(),
            'empId'     => $this->request->getGet('employee_id'),
        ]);
    }

    public function store()
    {
        $rules = [
            'employee_id'      => 'required|is_natural_no_zero',
            'type'             => 'required|in_list[Cash Advance,Debt]',
            'total_amount'     => 'required|decimal|greater_than[0]',
            'amount_per_cutoff'=> 'required|decimal|greater_than[0]',
            'cutoff'           => 'required|in_list[15,30,both]',
            'start_date'       => 'required|valid_date[Y-m-d]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->with('errors', $this->validator->getErrors())->withInput();
        }

        $total = (float) $this->request->getPost('total_amount');
        $data  = [
            'employee_id'       => (int) $this->request->getPost('employee_id'),
            'type'              => $this->request->getPost('type'),
            'description'       => $this->request->getPost('description', FILTER_SANITIZE_SPECIAL_CHARS),
            'total_amount'      => $total,
            'amount_per_cutoff' => (float) $this->request->getPost('amount_per_cutoff'),
            'cutoff'            => $this->request->getPost('cutoff'),
            'remaining_balance' => $total,
            'status'            => 'active',
            'start_date'        => $this->request->getPost('start_date'),
            'notes'             => $this->request->getPost('notes', FILTER_SANITIZE_SPECIAL_CHARS),
        ];

        $id = $this->model->insert($data);
        $this->audit->logAction('Deductions', 'create', $id, null, $data,
            "Created {$data['type']} deduction for employee #{$data['employee_id']}: ₱" . number_format($data['total_amount'], 2) . " (₱" . number_format($data['amount_per_cutoff'], 2) . "/cutoff)");

        return redirect()->to(site_url('deductions'))->with('success', 'Deduction recorded successfully.');
    }

    public function view(int $id)
    {
        $deduction = $this->model->getWithEmployee($id);
        if (! $deduction) {
            return redirect()->to(site_url('deductions'))->with('error', 'Deduction not found.');
        }

        $paidAmount    = (float) $deduction['total_amount'] - (float) $deduction['remaining_balance'];
        $progressPct   = $deduction['total_amount'] > 0
                         ? round(($paidAmount / $deduction['total_amount']) * 100, 1)
                         : 0;
        $termsLeft     = $deduction['amount_per_cutoff'] > 0
                         ? ceil($deduction['remaining_balance'] / $deduction['amount_per_cutoff'])
                         : 0;

        return view('deductions/view', [
            'title'       => 'Deduction Detail',
            'deduction'   => $deduction,
            'paidAmount'  => $paidAmount,
            'progressPct' => $progressPct,
            'termsLeft'   => $termsLeft,
        ]);
    }

    public function edit(int $id)
    {
        $deduction = $this->model->find($id);
        if (! $deduction) {
            return redirect()->to(site_url('deductions'))->with('error', 'Deduction not found.');
        }

        return view('deductions/edit', [
            'title'     => 'Edit Deduction',
            'deduction' => $deduction,
            'employees' => (new EmployeeModel())->where('status', 'active')->orderBy('full_name')->findAll(),
        ]);
    }

    public function update(int $id)
    {
        $deduction = $this->model->find($id);
        if (! $deduction) {
            return redirect()->to(site_url('deductions'))->with('error', 'Deduction not found.');
        }

        $rules = [
            'type'              => 'required|in_list[Cash Advance,Debt]',
            'amount_per_cutoff' => 'required|decimal|greater_than[0]',
            'cutoff'            => 'required|in_list[15,30,both]',
            'remaining_balance' => 'required|decimal|greater_than_equal_to[0]',
            'status'            => 'required|in_list[active,completed,cancelled]',
            'start_date'        => 'required|valid_date[Y-m-d]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->with('errors', $this->validator->getErrors())->withInput();
        }

        $remaining = (float) $this->request->getPost('remaining_balance');
        $status    = $this->request->getPost('status');

        // Auto-complete when balance zeroed out
        if ($remaining <= 0) {
            $remaining = 0;
            $status    = 'completed';
        }

        $data = [
            'type'              => $this->request->getPost('type'),
            'description'       => $this->request->getPost('description', FILTER_SANITIZE_SPECIAL_CHARS),
            'amount_per_cutoff' => (float) $this->request->getPost('amount_per_cutoff'),
            'cutoff'            => $this->request->getPost('cutoff'),
            'remaining_balance' => $remaining,
            'status'            => $status,
            'start_date'        => $this->request->getPost('start_date'),
            'notes'             => $this->request->getPost('notes', FILTER_SANITIZE_SPECIAL_CHARS),
        ];

        $this->model->update($id, $data);
        $this->audit->logAction('Deductions', 'update', $id, $deduction, $data,
            "Updated deduction #{$id} — remaining: ₱" . number_format($remaining, 2) . ", status: {$status}");

        return redirect()->to(site_url('deductions'))->with('success', 'Deduction updated.');
    }

    public function delete(int $id)
    {
        $deduction = $this->model->find($id);
        if (! $deduction) {
            return redirect()->to(site_url('deductions'))->with('error', 'Deduction not found.');
        }

        $this->model->delete($id);
        $this->audit->logAction('Deductions', 'delete', $id, $deduction, null,
            "Deleted deduction #{$id} ({$deduction['type']}) — ₱" . number_format((float)$deduction['total_amount'], 2));

        return redirect()->to(site_url('deductions'))->with('success', 'Deduction deleted.');
    }

    public function markComplete(int $id)
    {
        $deduction = $this->model->find($id);
        if (! $deduction) {
            return redirect()->to(site_url('deductions'))->with('error', 'Not found.');
        }

        $this->model->update($id, ['status' => 'completed', 'remaining_balance' => 0]);
        $this->audit->logAction('Deductions', 'complete', $id, $deduction, null,
            "Marked deduction #{$id} ({$deduction['type']}) as completed");

        return redirect()->to(site_url('deductions'))->with('success', 'Deduction marked as completed.');
    }

    public function summary()
    {
        $year   = $this->request->getGet('year')   ?? date('Y');
        $month  = $this->request->getGet('month')  ?? '';
        $type   = $this->request->getGet('type')   ?? '';
        $status = $this->request->getGet('status') ?? '';
        $cutoff = $this->request->getGet('cutoff') ?? '';
        $search = $this->request->getGet('q')      ?? '';

        if ($year === '') {
            $year = date('Y');
        }

        $filters    = compact('year', 'month', 'type', 'status', 'cutoff', 'search');
        $deductions = $this->model->listSummary($filters);

        return view('deductions/summary', [
            'title'      => 'Deductions Summary',
            'deductions' => $deductions,
            'filters'    => $filters,
        ]);
    }
}
