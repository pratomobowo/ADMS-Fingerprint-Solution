<?php

namespace Tests\Unit;

use App\Models\Attendance;
use App\Services\AttendanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceServiceTest extends TestCase
{
    use RefreshDatabase;

    private AttendanceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AttendanceService();
    }

    /** @test */
    public function it_gets_attendances_by_date_range()
    {
        // Create test data
        Attendance::factory()->create([
            'employee_id' => 101,
            'timestamp' => '2025-11-10 08:00:00',
        ]);
        Attendance::factory()->create([
            'employee_id' => 102,
            'timestamp' => '2025-11-15 09:00:00',
        ]);
        Attendance::factory()->create([
            'employee_id' => 103,
            'timestamp' => '2025-11-20 10:00:00',
        ]);

        // Test date range filtering
        $result = $this->service->getAttendancesByDateRange(
            '2025-11-14 00:00:00',
            '2025-11-16 23:59:59'
        );

        $this->assertEquals(1, $result->total());
        $this->assertEquals(102, $result->first()->employee_id);
    }

    /** @test */
    public function it_filters_attendances_by_employee_id_in_date_range()
    {
        // Create test data
        Attendance::factory()->create([
            'employee_id' => 191,
            'timestamp' => '2025-11-15 08:00:00',
        ]);
        Attendance::factory()->create([
            'employee_id' => 191,
            'timestamp' => '2025-11-15 17:00:00',
        ]);
        Attendance::factory()->create([
            'employee_id' => 192,
            'timestamp' => '2025-11-15 09:00:00',
        ]);

        // Test with employee_id filter
        $result = $this->service->getAttendancesByDateRange(
            '2025-11-15 00:00:00',
            '2025-11-15 23:59:59',
            ['employee_id' => 191]
        );

        $this->assertEquals(2, $result->total());
        $this->assertTrue($result->every(fn($a) => $a->employee_id === 191));
    }

    /** @test */
    public function it_filters_attendances_by_device_sn_in_date_range()
    {
        // Create test data
        Attendance::factory()->create([
            'employee_id' => 191,
            'device_sn' => 'BWNF184660256',
            'timestamp' => '2025-11-15 08:00:00',
        ]);
        Attendance::factory()->create([
            'employee_id' => 192,
            'device_sn' => 'SPK7245000764',
            'timestamp' => '2025-11-15 09:00:00',
        ]);

        // Test with device_sn filter
        $result = $this->service->getAttendancesByDateRange(
            '2025-11-15 00:00:00',
            '2025-11-15 23:59:59',
            ['device_sn' => 'BWNF184660256']
        );

        $this->assertEquals(1, $result->total());
        $this->assertEquals('BWNF184660256', $result->first()->device_sn);
    }

    /** @test */
    public function it_paginates_attendances_by_date_range()
    {
        // Create 10 test records
        for ($i = 1; $i <= 10; $i++) {
            Attendance::factory()->create([
                'employee_id' => 100 + $i,
                'timestamp' => '2025-11-15 08:00:00',
            ]);
        }

        // Test pagination with limit
        $result = $this->service->getAttendancesByDateRange(
            '2025-11-15 00:00:00',
            '2025-11-15 23:59:59',
            ['limit' => 5]
        );

        $this->assertEquals(10, $result->total());
        $this->assertEquals(5, $result->count());
        $this->assertEquals(5, $result->perPage());
    }

    /** @test */
    public function it_gets_attendances_by_employee()
    {
        // Create test data
        Attendance::factory()->create([
            'employee_id' => 191,
            'timestamp' => '2025-11-15 08:00:00',
        ]);
        Attendance::factory()->create([
            'employee_id' => 191,
            'timestamp' => '2025-11-15 17:00:00',
        ]);
        Attendance::factory()->create([
            'employee_id' => 192,
            'timestamp' => '2025-11-15 09:00:00',
        ]);

        // Test employee filtering
        $result = $this->service->getAttendancesByEmployee(191);

        $this->assertEquals(2, $result->total());
        $this->assertTrue($result->every(fn($a) => $a->employee_id === 191));
    }

    /** @test */
    public function it_filters_employee_attendances_by_date_range()
    {
        // Create test data
        Attendance::factory()->create([
            'employee_id' => 191,
            'timestamp' => '2025-11-10 08:00:00',
        ]);
        Attendance::factory()->create([
            'employee_id' => 191,
            'timestamp' => '2025-11-15 09:00:00',
        ]);
        Attendance::factory()->create([
            'employee_id' => 191,
            'timestamp' => '2025-11-20 10:00:00',
        ]);

        // Test with date range filter
        $result = $this->service->getAttendancesByEmployee(191, [
            'start_date' => '2025-11-14 00:00:00',
            'end_date' => '2025-11-16 23:59:59',
        ]);

        $this->assertEquals(1, $result->total());
    }

    /** @test */
    public function it_filters_employee_attendances_by_start_date_only()
    {
        // Create test data
        Attendance::factory()->create([
            'employee_id' => 191,
            'timestamp' => '2025-11-10 08:00:00',
        ]);
        Attendance::factory()->create([
            'employee_id' => 191,
            'timestamp' => '2025-11-15 09:00:00',
        ]);

        // Test with start_date only
        $result = $this->service->getAttendancesByEmployee(191, [
            'start_date' => '2025-11-14 00:00:00',
        ]);

        $this->assertEquals(1, $result->total());
    }

    /** @test */
    public function it_formats_attendance_for_api()
    {
        // Create test attendance
        $attendance = Attendance::factory()->create([
            'employee_id' => 191,
            'device_sn' => 'BWNF184660256',
            'timestamp' => '2025-11-15 08:30:00',
            'status1' => true,
            'status2' => false,
            'status3' => false,
            'status4' => false,
            'status5' => false,
        ]);

        // Format for API
        $formatted = $this->service->formatAttendanceForApi($attendance);

        // Assert structure
        $this->assertArrayHasKey('id', $formatted);
        $this->assertArrayHasKey('employee_id', $formatted);
        $this->assertArrayHasKey('timestamp', $formatted);
        $this->assertArrayHasKey('device_sn', $formatted);
        $this->assertArrayHasKey('status', $formatted);
        $this->assertArrayHasKey('created_at', $formatted);

        // Assert values
        $this->assertEquals($attendance->id, $formatted['id']);
        $this->assertEquals(191, $formatted['employee_id']);
        $this->assertEquals('BWNF184660256', $formatted['device_sn']);

        // Assert status structure
        $this->assertIsArray($formatted['status']);
        $this->assertArrayHasKey('status1', $formatted['status']);
        $this->assertTrue($formatted['status']['status1']);
        $this->assertFalse($formatted['status']['status2']);
    }
}
