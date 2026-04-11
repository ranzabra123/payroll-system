<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Fill attendance for all active employees for April 1–11, 2026.
 * Weekdays: whole_day | Weekends: skip (no record inserted)
 */
class AttendanceAprilSeeder extends Seeder
{
    public function run(): void
    {
        $db = \Config\Database::connect();

        // Fetch all active employees
        $employees = $db->table('employees')
                        ->select('id')
                        ->where('status', 'active')
                        ->where('deleted_at IS NULL')
                        ->get()
                        ->getResultArray();

        if (empty($employees)) {
            echo "No active employees found.\n";
            return;
        }

        $dates = [];
        $start = strtotime('2026-04-01');
        $end   = strtotime('2026-04-11');

        for ($ts = $start; $ts <= $end; $ts = strtotime('+1 day', $ts)) {
            $dates[] = date('Y-m-d', $ts);
        }

        $now  = date('Y-m-d H:i:s');
        $rows = [];
        $skipped = 0;

        foreach ($employees as $emp) {
            foreach ($dates as $date) {
                // Skip if record already exists
                $exists = $db->table('attendance')
                             ->where('employee_id', $emp['id'])
                             ->where('attendance_date', $date)
                             ->countAllResults();
                if ($exists) {
                    $skipped++;
                    continue;
                }

                $rows[] = [
                    'employee_id'     => $emp['id'],
                    'attendance_date' => $date,
                    'attendance_type' => 'whole_day',
                    'overtime_hours'  => 0,
                    'is_holiday'      => 0,
                    'is_special_holiday' => 0,
                    'remarks'         => null,
                    'created_by'      => 1,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ];
            }
        }

        if (! empty($rows)) {
            $db->table('attendance')->insertBatch($rows);
        }

        $inserted = count($rows);
        echo "Done. Inserted: {$inserted}, Skipped (already existed): {$skipped}\n";
        echo "Dates filled: " . implode(', ', $dates) . "\n";
    }
}
