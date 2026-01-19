<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HRApiEndpointsTest extends TestCase
{
    use RefreshDatabase;

    private ApiToken $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->token = ApiToken::factory()->create();
    }

    /** @test */
    public function it_gets_attendances_with_valid_token()
    {
        // Create test data
        Attendance::factory()->count(5)->create([
            'timestamp' => '2025-11-15 08:00:00',
        ]);

        // Make request
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token->token)
            ->getJson('/api/v1/hr/attendances?start_date=2025-11-15 00:00:00&end_date=2025-11-15 23:59:59');

        // Assert response
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'employee_id',
                        'timestamp',
                        'device_sn',
                        'status' => [
                            'status1',
                            'status2',
                            'status3',
                            'status4',
                            'status5',
                        ],
                        'created_at',
                    ],
                ],
                'meta' => [
                    'total',
                    'count',
                    'per_page',
                    'current_page',
                    'total_pages',
                ],
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals(5, $response->json('meta.total'));
    }

    /** @test */
    public function it_filters_attendances_by_employee_id()
    {
        // Create test data
        Attendance::factory()->create([
            'employee_id' => 191,
            'timestamp' => '2025-11-15 08:00:00',
        ]);
        Attendance::factory()->create([
            'employee_id' => 192,
            'timestamp' => '2025-11-15 09:00:00',
        ]);

        // Make request with employee_id filter
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token->token)
            ->getJson('/api/v1/hr/attendances?start_date=2025-11-15 00:00:00&end_date=2025-11-15 23:59:59&employee_id=191');

        // Assert response
        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('meta.total'));
        $this->assertEquals(191, $response->json('data.0.employee_id'));
    }

    /** @test */
    public function it_paginates_attendances()
    {
        // Create test data
        Attendance::factory()->count(10)->create([
            'timestamp' => '2025-11-15 08:00:00',
        ]);

        // Make request with pagination
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token->token)
            ->getJson('/api/v1/hr/attendances?start_date=2025-11-15 00:00:00&end_date=2025-11-15 23:59:59&limit=5');

        // Assert response
        $response->assertStatus(200);
        $this->assertEquals(10, $response->json('meta.total'));
        $this->assertEquals(5, $response->json('meta.count'));
        $this->assertEquals(5, $response->json('meta.per_page'));
    }

    /** @test */
    public function it_gets_single_attendance_by_id()
    {
        // Create test data
        $attendance = Attendance::factory()->create([
            'employee_id' => 191,
        ]);

        // Make request
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token->token)
            ->getJson("/api/v1/hr/attendances/{$attendance->id}");

        // Assert response
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'employee_id',
                    'timestamp',
                    'device_sn',
                    'status',
                    'created_at',
                ],
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals($attendance->id, $response->json('data.id'));
        $this->assertEquals(191, $response->json('data.employee_id'));
    }

    /** @test */
    public function it_returns_404_for_invalid_attendance_id()
    {
        // Make request with non-existent ID
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token->token)
            ->getJson('/api/v1/hr/attendances/99999');

        // Assert response
        $response->assertStatus(404)
            ->assertJsonStructure([
                'success',
                'error' => [
                    'code',
                    'message',
                ],
            ]);

        $this->assertFalse($response->json('success'));
    }

    /** @test */
    public function it_gets_attendances_by_employee()
    {
        // Create test data
        Attendance::factory()->count(3)->create([
            'employee_id' => 191,
            'timestamp' => '2025-11-15 08:00:00',
        ]);
        Attendance::factory()->create([
            'employee_id' => 192,
            'timestamp' => '2025-11-15 09:00:00',
        ]);

        // Make request
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token->token)
            ->getJson('/api/v1/hr/employees/191/attendances');

        // Assert response
        $response->assertStatus(200);
        $this->assertEquals(3, $response->json('meta.total'));
        
        // Verify all returned attendances belong to 191
        $data = $response->json('data');
        foreach ($data as $attendance) {
            $this->assertEquals(191, $attendance['employee_id']);
        }
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

        // Make request with date filter
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token->token)
            ->getJson('/api/v1/hr/employees/191/attendances?start_date=2025-11-14 00:00:00&end_date=2025-11-16 23:59:59');

        // Assert response
        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('meta.total'));
    }

    /** @test */
    public function it_rejects_request_without_token()
    {
        // Make request without token
        $response = $this->getJson('/api/v1/hr/attendances?start_date=2025-11-15 00:00:00&end_date=2025-11-15 23:59:59');

        // Assert response
        $response->assertStatus(401)
            ->assertJsonStructure([
                'success',
                'error' => [
                    'code',
                    'message',
                ],
            ]);

        $this->assertFalse($response->json('success'));
    }

    /** @test */
    public function it_rejects_request_with_invalid_token()
    {
        // Make request with invalid token
        $response = $this->withHeader('Authorization', 'Bearer invalid-token-12345')
            ->getJson('/api/v1/hr/attendances?start_date=2025-11-15 00:00:00&end_date=2025-11-15 23:59:59');

        // Assert response
        $response->assertStatus(401);
        $this->assertFalse($response->json('success'));
    }

    /** @test */
    public function it_rejects_request_with_expired_token()
    {
        // Create expired token
        $expiredToken = ApiToken::factory()->expired()->create();

        // Make request with expired token
        $response = $this->withHeader('Authorization', 'Bearer ' . $expiredToken->token)
            ->getJson('/api/v1/hr/attendances?start_date=2025-11-15 00:00:00&end_date=2025-11-15 23:59:59');

        // Assert response
        $response->assertStatus(401);
        $this->assertFalse($response->json('success'));
    }

    /** @test */
    public function it_rejects_request_with_inactive_token()
    {
        // Create inactive token
        $inactiveToken = ApiToken::factory()->inactive()->create();

        // Make request with inactive token
        $response = $this->withHeader('Authorization', 'Bearer ' . $inactiveToken->token)
            ->getJson('/api/v1/hr/attendances?start_date=2025-11-15 00:00:00&end_date=2025-11-15 23:59:59');

        // Assert response
        $response->assertStatus(401);
        $this->assertFalse($response->json('success'));
    }

    /** @test */
    public function it_validates_required_date_parameters()
    {
        // Make request without required dates
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token->token)
            ->getJson('/api/v1/hr/attendances');

        // Assert validation error
        $response->assertStatus(400);
        $this->assertFalse($response->json('success'));
    }

    /** @test */
    public function it_validates_date_format()
    {
        // Make request with invalid date format
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token->token)
            ->getJson('/api/v1/hr/attendances?start_date=invalid-date&end_date=2025-11-15');

        // Assert validation error
        $response->assertStatus(400);
        $this->assertFalse($response->json('success'));
    }
}
