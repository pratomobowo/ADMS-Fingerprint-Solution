<?php

namespace App\Http\Controllers\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="Attendance",
 *     title="Attendance",
 *     description="Attendance record model",
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         description="Attendance record ID",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="employee_id",
 *         type="string",
 *         description="Employee ID",
 *         example="12345"
 *     ),
 *     @OA\Property(
 *         property="timestamp",
 *         type="string",
 *         format="date-time",
 *         description="Attendance timestamp",
 *         example="2025-11-15T08:30:00Z"
 *     ),
 *     @OA\Property(
 *         property="device_sn",
 *         type="string",
 *         description="Device serial number",
 *         example="BWN001"
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="object",
 *         description="Attendance status flags",
 *         @OA\Property(property="status1", type="boolean", example=true),
 *         @OA\Property(property="status2", type="boolean", example=false),
 *         @OA\Property(property="status3", type="boolean", example=false),
 *         @OA\Property(property="status4", type="boolean", example=false),
 *         @OA\Property(property="status5", type="boolean", example=false)
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Record creation timestamp",
 *         example="2025-11-15T08:30:05Z"
 *     )
 * )
 */
class AttendanceSchema
{
}
