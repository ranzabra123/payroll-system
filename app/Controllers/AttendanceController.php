<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use App\Models\AttendanceModel;
use App\Models\EmployeeModel;
use App\Models\AuditLogModel;

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

        // Scope employees to user's branch restriction
        $employees = $this->empModel->getActiveList(user_branch_id());

        // Existing attendance for that date
        $existing = $this->model->getByDate($date);
        $existingMap = [];
        foreach ($existing as $row) {
            $existingMap[$row['employee_id']] = $row;
        }

        return view('attendance/index', [
            'title'       => 'Daily Attendance',
            'date'        => $date,
            'employees'   => $employees,
            'existingMap' => $existingMap,
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

        $savedCount = 0;
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

        return redirect()->to(site_url('attendance?date=' . $date))
                         ->with('success', "Attendance saved for {$savedCount} employee(s).");
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

        $summary = $this->model->monthlySummary((int) $year, (int) $mon, user_branch_id());

        return view('attendance/records', [
            'title'   => 'Attendance Records',
            'month'   => $month,
            'summary' => $summary,
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

        $records = $this->model->getByEmployeeAndPeriod($employeeId, $start, $end);

        return view('attendance/view', [
            'title'    => 'Attendance – ' . $employee['full_name'],
            'employee' => $employee,
            'records'  => $records,
            'month'    => $month,
            'start'    => $start,
            'end'      => $end,
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

        $record = $this->model->find($id);
        if (! $record) {
            return $this->response->setJSON(['success' => false, 'message' => 'Record not found.']);
        }

        $employee = $this->empModel->find((int) $record['employee_id']);
        if (! $employee || ! can_access_branch($employee['branch_id'])) {
            return $this->response->setJSON(['success' => false, 'message' => 'Access denied.']);
        }

        $this->model->update($id, [$field => $value]);
        $oldVal = $record[$field] ?? 'N/A';
        $this->audit->logAction('Attendance', 'field_update', $id, $record, [$field => $value],
            "Changed attendance #{$id} — {$field}: '{$oldVal}' → '{$value}'");

        return $this->response->setJSON(['success' => true]);
    }
}
