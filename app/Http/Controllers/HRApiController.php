<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttendanceQueryRequest;
use App\Models\Attendance;
use App\Services\AttendanceService;
use App\Services\LoggingService;
use App\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="ADMS HR API",
 *     version="1.0.0",
 *     description="API untuk integrasi data absensi antara ADMS dan aplikasi HR eksternal",
 *     @OA\Contact(
 *         email="admin@adms.com"
 *     )
 * )
 * 
 * @OA\Server(
 *     url="/api/v1",
 *     description="API Server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="apiKey",
 *     name="Authorization",
 *     in="header",
 *     description="Enter token in format: Bearer {token}"
 * )
 */
class HRApiController extends Controller
{
    public function __construct(
        private AttendanceService $attendanceService,
        private LoggingService $loggingService
    ) {}

    /**
     * @OA\Get(
     *     path="/hr/attendances",
     *     summary="Get attendances with filters",
     *     description="Retrieve attendance records with optional filtering by date range and employee ID",
     *     operationId="getAttendances",
     *     tags={"Attendances"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Start date for filtering (format: Y-m-d)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2025-11-01")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="End date for filtering (format: Y-m-d)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2025-11-15")
     *     ),
     *     @OA\Parameter(
     *         name="employee_id",
     *         in="query",
     *         description="Filter by employee ID",
     *         required=false,
     *         @OA\Schema(type="string", example="12345")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of records per page (default: 50, max: 100)",
     *         required=false,
     *         @OA\Schema(type="integer", example=50)
     *     ),
     *     @OA\Parameter(
     *         name="offset",
     *         in="query",
     *         description="Number of records to skip (default: 0)",
     *         required=false,
     *         @OA\Schema(type="integer", example=0)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Attendance")
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="total", type="integer", example=150),
     *                 @OA\Property(property="count", type="integer", example=50),
     *                 @OA\Property(property="per_page", type="integer", example=50),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="total_pages", type="integer", example=3)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid request parameters",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing token",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=429,
     *         description="Rate limit exceeded",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function getAttendances(AttendanceQueryRequest $request): JsonResponse
    {
        $startTime = microtime(true);
        $statusCode = 200;

        try {
            $validated = $request->validated();

            // Prepare filters
            $filters = [
                'employee_id' => $validated['employee_id'] ?? null,
                'limit' => $validated['limit'] ?? 50,
                'offset' => $validated['offset'] ?? 0,
            ];

            // Get paginated attendances
            $attendances = $this->attendanceService->getAttendancesByDateRange(
                $validated['start_date'] ?? null,
                $validated['end_date'] ?? null,
                $filters
            );

            // Format data for API response
            $data = $attendances->map(function ($attendance) {
                return $this->attendanceService->formatAttendanceForApi($attendance);
            });

            $meta = [
                'total' => $attendances->total(),
                'count' => $attendances->count(),
                'per_page' => $attendances->perPage(),
                'current_page' => $attendances->currentPage(),
                'total_pages' => $attendances->lastPage(),
            ];

            $this->logRequest($request, $statusCode, $startTime);
            return ApiResponse::success($data, $meta);
        } catch (\Exception $e) {
            $statusCode = 500;
            $this->logRequest($request, $statusCode, $startTime);
            
            return ApiResponse::error(
                'INTERNAL_ERROR',
                'An error occurred while processing your request',
                [],
                500
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/hr/attendances/{id}",
     *     summary="Get single attendance by ID",
     *     description="Retrieve a specific attendance record by its ID",
     *     operationId="getAttendanceById",
     *     tags={"Attendances"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Attendance ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Attendance")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing token",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Attendance record not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function getAttendanceById(Request $request, int $id): JsonResponse
    {
        $startTime = microtime(true);
        $statusCode = 200;

        try {
            $attendance = Attendance::find($id);

            if (!$attendance) {
                $statusCode = 404;
                $this->logRequest($request, $statusCode, $startTime);
                
                return ApiResponse::error(
                    'RESOURCE_NOT_FOUND',
                    'Attendance record not found',
                    [],
                    404
                );
            }

            $this->logRequest($request, $statusCode, $startTime);
            return ApiResponse::success(
                $this->attendanceService->formatAttendanceForApi($attendance)
            );
        } catch (\Exception $e) {
            $statusCode = 500;
            $this->logRequest($request, $statusCode, $startTime);
            
            return ApiResponse::error(
                'INTERNAL_ERROR',
                'An error occurred while processing your request',
                [],
                500
            );
        }
    }

    /**
     * @OA\Get(
     *     path="/hr/employees/{employee_id}/attendances",
     *     summary="Get attendances for specific employee",
     *     description="Retrieve attendance records for a specific employee with optional date filtering",
     *     operationId="getAttendancesByEmployee",
     *     tags={"Attendances"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="employee_id",
     *         in="path",
     *         description="Employee ID",
     *         required=true,
     *         @OA\Schema(type="string", example="12345")
     *     ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Start date for filtering (format: Y-m-d)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2025-11-01")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="End date for filtering (format: Y-m-d)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2025-11-15")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of records per page (default: 50, max: 100)",
     *         required=false,
     *         @OA\Schema(type="integer", example=50)
     *     ),
     *     @OA\Parameter(
     *         name="offset",
     *         in="query",
     *         description="Number of records to skip (default: 0)",
     *         required=false,
     *         @OA\Schema(type="integer", example=0)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Attendance")
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="total", type="integer", example=150),
     *                 @OA\Property(property="count", type="integer", example=50),
     *                 @OA\Property(property="per_page", type="integer", example=50),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="total_pages", type="integer", example=3)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid employee ID or request parameters",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Invalid or missing token",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=429,
     *         description="Rate limit exceeded",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function getAttendancesByEmployee(AttendanceQueryRequest $request, string $employeeId): JsonResponse
    {
        $startTime = microtime(true);
        $statusCode = 200;

        try {
            // Validate employee_id
            if (empty($employeeId)) {
                $statusCode = 400;
                $this->logRequest($request, $statusCode, $startTime);
                
                return ApiResponse::error(
                    'INVALID_EMPLOYEE_ID',
                    'Employee ID is required',
                    [],
                    400
                );
            }

            $validated = $request->validated();

            // Prepare filters
            $filters = [
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
                'limit' => $validated['limit'] ?? 50,
                'offset' => $validated['offset'] ?? 0,
            ];

            // Get paginated attendances
            $attendances = $this->attendanceService->getAttendancesByEmployee(
                $employeeId,
                $filters
            );

            // Format data for API response
            $data = $attendances->map(function ($attendance) {
                return $this->attendanceService->formatAttendanceForApi($attendance);
            });

            $meta = [
                'total' => $attendances->total(),
                'count' => $attendances->count(),
                'per_page' => $attendances->perPage(),
                'current_page' => $attendances->currentPage(),
                'total_pages' => $attendances->lastPage(),
            ];

            $this->logRequest($request, $statusCode, $startTime);
            return ApiResponse::success($data, $meta);
        } catch (\Exception $e) {
            $statusCode = 500;
            $this->logRequest($request, $statusCode, $startTime);
            
            return ApiResponse::error(
                'INTERNAL_ERROR',
                'An error occurred while processing your request',
                [],
                500
            );
        }
    }

    /**
     * Log API request with response time and status code
     */
    private function logRequest(Request $request, int $statusCode, float $startTime): void
    {
        $responseTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
        $apiToken = $request->attributes->get('api_token');
        
        $this->loggingService->logApiRequest(
            $request,
            $apiToken?->id,
            $statusCode,
            $responseTime
        );
    }
}
