<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\AttendanceModel;
use App\Models\EmployeeModel;
use App\Models\AuditLogModel;
use App\Models\BranchModel;

/**
 * AttendanceController – daily attendance input and records.
 */
class AttendanceController extends Controller
{
    protected AttendanceModel $model;
    protected EmployeeModel $empModel;
    protected AuditLogModel $audit;

    public function __construct()
    {
        $this->model    = new AttendanceModel();
        $this->empModel = new EmployeeModel();
        $this->audit    = new AuditLogModel();
    }

    /** Attendance dashboard – choose a date to input attendance. */
    public function dashboard()
    {
        $date = $this->request->getGet('date') ?? date('Y-m-d');
        $branchId = $this->request->getGet('branch_id');
        $userBranch = user_branch_id();

        // If user has a branch restriction, force their branch and hide filter
        if ($userBranch) {
            $filterBranch = (int)$userBranch;
            $branchId = $userBranch; // for view
        } else {
            $filterBranch = $branchId ? (int)$branchId : null;
        }

        // Get employees with branch info
        $builder = $this->empModel->select('employees.*, branches.name AS branch_name')
            ->join('branches', 'branches.id = employees.branch_id', 'left')
            ->where('employees.status', 'active');
        if ($filterBranch) {
            $builder->where('employees.branch_id', $filterBranch);
        }
        $employees = $builder->orderBy('employees.full_name', 'ASC')->findAll();

        // Existing attendance for that date
        $existing = $this->model->getByDate($date);
        $existingMap = [];
        foreach ($existing as $row) {
            $existingMap[$row['employee_id']] = $row;
        }

        // All branches for filter dropdown (only show if userBranch is empty)
        $branches = $userBranch ? [] : (new BranchModel())->getActiveList();

        return view('attendance/index', [
            'title'       => 'Daily Attendance',
            'date'        => $date,
            'employees'   => $employees,
            'existingMap' => $existingMap,
            'branches'    => $branches,
            'branchId'    => $branchId,
            'userBranch'  => $userBranch,
        ]);
    }

    /** Delete all attendance records for a specific date. */
    public function deleteByDate()
    {
        $date = $this->request->getPost('date');
        if (! $date || ! strtotime($date)) {
            return redirect()->back()->with('error', 'Invalid date.');
        }

        $deleted = $this->model->where('attendance_date', $date)->delete();
        $this->audit->logAction('Attendance', 'delete-by-date', null,
            ['date' => $date], null,
            "Removed all attendance records for " . date('F j, Y', strtotime($date)));

        return redirect()->to(site_url('attendance?date=' . $date))
                         ->with('success', 'All attendance records for ' . date('F j, Y', strtotime($date)) . ' have been removed.');
    }

    /** Save attendance for a date (bulk). */
    public function store()
    {
        $date = $this->request->getPost('attendance_date');
        if (! $date || ! strtotime($date)) {
            return redirect()->back()->with('error', 'Invalid date.')->withInput();
        }

        $attendance = $this->request->getPost('attendance');  // [employee_id => type]
        $overtime   = $this->request->getPost('overtime');    // [employee_id => hours]

        if (empty($attendance)) {
            return redirect()->back()->with('error', 'No attendance data submitted.')->withInput();
        }

        $savedCount   = 0;
        $sundayAbsent = []; // track names set as absent on Sunday
        $isSunday     = (int) date('N', strtotime($date)) === 7;

        foreach ($attendance as $employeeId => $type) {
            $employeeId = (int) $employeeId;
            if (! in_array($type, ['whole_day', 'half_am', 'half_pm', 'absent', 'day_off'], true)) {
                continue;
            }

            // Branch-scope check: only allow employees the user can access
            $emp = $this->empModel->find($employeeId);
            if (! $emp || ! can_access_branch($emp['branch_id'])) {
                continue;
            }

            $otHours = max(0, (float) ($overtime[$employeeId] ?? 0));

            // Upsert: check if record exists
            $existing = $this->model
                ->where('employee_id', $employeeId)
                ->where('attendance_date', $date)
                ->first();

            $rowData = [
                'employee_id'     => $employeeId,
                'attendance_date' => $date,
                'attendance_type' => $type,
                'overtime_hours'  => $otHours,
                'created_by'      => session()->get('user_id'),
            ];

            // Conflict flag: absent on Sunday
            if ($isSunday && $type === 'absent') {
                $sundayAbsent[] = $emp['full_name'];
            }

            if ($existing) {
                $this->model->update($existing['id'], $rowData);
                $this->audit->logAction('Attendance', 'update', $existing['id'], $existing, $rowData,
                    "Updated attendance for employee #{$employeeId} on {$date}: {$existing['attendance_type']} → {$type}" . ($otHours ? " (OT: {$otHours}h)" : ''));
            } else {
                $id = $this->model->insert($rowData);
                $this->audit->logAction('Attendance', 'create', $id, null, $rowData,
                    "Recorded attendance for employee #{$employeeId} on {$date}: {$type}" . ($otHours ? " (OT: {$otHours}h)" : ''));
            }
            $savedCount++;
        }

        $msg = "Attendance saved for {$savedCount} employee(s).";
        if (! empty($sundayAbsent)) {
            $names = implode(', ', array_map('esc', $sundayAbsent));
            $msg  .= " Warning: the following employee(s) were marked Absent on a Sunday — {$names}.";
        }

        return redirect()->to(site_url('attendance?date=' . $date))->with('success', $msg);
    }

    /** Create form (redirects to dashboard with date param). */
    public function create()
    {
        return view('attendance/create', ['title' => 'Select Attendance Date']);
    }

    /** Attendance records listing / monthly summary. */
    public function records()
    {
        $month = $this->request->getGet('month') ?? date('Y-m');
        [$year, $mon] = explode('-', $month);

        $summary      = $this->model->monthlySummary((int) $year, (int) $mon, user_branch_id());
        $deptWdMap    = (new \App\Models\DepartmentModel())->getWorkingDaysMap();
        $calendarDays = (int) date('t', strtotime($month . '-01'));

        return view('attendance/records', [
            'title'       => 'Attendance Records',
            'month'       => $month,
            'summary'     => $summary,
            'deptWdMap'   => $deptWdMap,
            'calendarDays'=> $calendarDays,
        ]);
    }

    /** View attendance details for one employee for a month. */
    public function view(int $employeeId)
    {
        $employee = $this->empModel->find($employeeId);
        if (! $employee || ! can_access_branch($employee['branch_id'])) {
            return redirect()->to(site_url('attendance/records'))->with('error', 'Employee not found.');
        }

        $month = $this->request->getGet('month') ?? date('Y-m');
        $start = $month . '-01';
        $end   = date('Y-m-t', strtotime($start));

        $records      = $this->model->getByEmployeeAndPeriod($employeeId, $start, $end);
        $deptWdMap    = (new \App\Models\DepartmentModel())->getWorkingDaysMap();
        $calendarDays = (int) date('t', strtotime($start));
        $deptWd       = (float) ($deptWdMap[$employee['department'] ?? ''] ?? 26);
        $monthlyWd    = $deptWd + ($calendarDays - 30);

        return view('attendance/view', [
            'title'     => 'Attendance – ' . $employee['full_name'],
            'employee'  => $employee,
            'records'   => $records,
            'month'     => $month,
            'start'     => $start,
            'end'       => $end,
            'monthlyWd' => $monthlyWd,
        ]);
    }

    /** Inline field update (AJAX). */
    public function updateField()
    {
        $id    = (int) $this->request->getPost('id');
        $field = $this->request->getPost('field');
        $value = $this->request->getPost('value');

        $allowed = ['attendance_type', 'overtime_hours', 'remarks'];
        if (! in_array($field, $allowed, true)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid field.']);
        }

        // Validate value per field
        if ($field === 'attendance_type') {
            if (! in_array($value, ['whole_day', 'half_am', 'half_pm', 'absent', 'day_off'], true)) {
                return $this->response->setJSON(['success' => false, 'message' => 'Invalid attendance type.']);
            }
        } elseif ($field === 'overtime_hours') {
            if (! is_numeric($value) || (float) $value < 0 || (float) $value > 24) {
                return $this->response->setJSON(['success' => false, 'message' => 'Overtime hours must be between 0 and 24.']);
            }
            $value = (float) $value;
        }

        $record = $this->model->find($id);
        if (! $record) {
            return $this->response->setJSON(['success' => false, 'message' => 'Record not found.']);
        }

        $employee = $this->empModel->find((int) $record['employee_id']);
        if (! $employee || ! can_access_branch($employee['branch_id'])) {
            return $this->response->setJSON(['success' => false, 'message' => 'Access denied.']);
        }

        // Conflict check: warn if marking absent on a Sunday (still allowed, but flagged)
        $isSunday = (int) date('N', strtotime($record['attendance_date'])) === 7;
        $warning  = null;
        if ($field === 'attendance_type' && $value === 'absent' && $isSunday) {
            $warning = 'Note: employee marked Absent on a Sunday (normally a half-day).';
        }

        $this->model->update($id, [$field => $value]);
        $oldVal = $record[$field] ?? 'N/A';
        $this->audit->logAction('Attendance', 'field_update', $id, $record, [$field => $value],
            "Changed attendance #{$id} — {$field}: '{$oldVal}' → '{$value}'" . ($warning ? ' [' . $warning . ']' : ''));

        return $this->response->setJSON(['success' => true, 'warning' => $warning]);
    }
}
