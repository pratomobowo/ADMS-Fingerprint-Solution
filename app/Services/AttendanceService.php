<?php

namespace App\Services;

use App\Models\Attendance;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class AttendanceService
{
    /**
     * Get attendances by date range with filtering and pagination
     *
     * @param string $startDate
     * @param string $endDate
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getAttendancesByDateRange(string $startDate, string $endDate, array $filters = []): LengthAwarePaginator
    {
        $query = Attendance::query()
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->orderBy('timestamp', 'desc');

        // Apply employee_id filter if provided
        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        // Apply device_sn filter if provided
        if (!empty($filters['device_sn'])) {
            $query->where('sn', $filters['device_sn']);
        }

        // Get pagination parameters
        $perPage = $filters['limit'] ?? 50;
        $page = isset($filters['offset']) ? (int)($filters['offset'] / $perPage) + 1 : 1;

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get attendances by employee with filtering and pagination
     *
     * @param string $employeeId
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getAttendancesByEmployee(string $employeeId, array $filters = []): LengthAwarePaginator
    {
        $query = Attendance::query()
            ->where('employee_id', $employeeId)
            ->orderBy('timestamp', 'desc');

        // Apply date range filter if provided
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('timestamp', [$filters['start_date'], $filters['end_date']]);
        } elseif (!empty($filters['start_date'])) {
            $query->where('timestamp', '>=', $filters['start_date']);
        } elseif (!empty($filters['end_date'])) {
            $query->where('timestamp', '<=', $filters['end_date']);
        }

        // Apply device_sn filter if provided
        if (!empty($filters['device_sn'])) {
            $query->where('sn', $filters['device_sn']);
        }

        // Get pagination parameters
        $perPage = $filters['limit'] ?? 50;
        $page = isset($filters['offset']) ? (int)($filters['offset'] / $perPage) + 1 : 1;

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Format attendance data for API response
     *
     * @param Attendance $attendance
     * @return array
     */
    public function formatAttendanceForApi(Attendance $attendance): array
    {
        return [
            'id' => $attendance->id,
            'employee_id' => $attendance->employee_id,
            'timestamp' => $attendance->timestamp->toIso8601String(),
            'device_sn' => $attendance->sn ?? null,
            'check_type' => $attendance->status1,
            'check_type_label' => $this->getCheckTypeLabel($attendance->status1),
            'verify_mode' => $attendance->status2,
            'verify_mode_label' => $this->getVerifyModeLabel($attendance->status2),
            'work_code' => $attendance->status3,
            'status' => [
                'status1' => $attendance->status1,
                'status2' => $attendance->status2,
                'status3' => $attendance->status3,
                'status4' => $attendance->status4,
                'status5' => $attendance->status5,
            ],
            'created_at' => $attendance->created_at->toIso8601String(),
        ];
    }

    /**
     * Get human-readable label for check type (status1)
     */
    private function getCheckTypeLabel(?int $status): string
    {
        return match ($status) {
            0 => 'Check In',
            1 => 'Check Out',
            2 => 'Break Out',
            3 => 'Break In',
            4 => 'OT In',
            5 => 'OT Out',
            default => 'Unknown',
        };
    }

    /**
     * Get human-readable label for verify mode (status2)
     */
    private function getVerifyModeLabel(?int $mode): string
    {
        return match ($mode) {
            1 => 'Fingerprint',
            2 => 'Password',
            3 => 'Card',
            15 => 'Face Recognition',
            default => 'Unknown',
        };
    }
}
