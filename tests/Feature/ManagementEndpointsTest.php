<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\WebhookConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ManagementEndpointsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_lists_all_webhook_configs()
    {
        // Create test data
        WebhookConfig::factory()->count(3)->create();

        // Make request
        $response = $this->getJson('/api/v1/admin/webhooks');

        // Assert response
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'url',
                        'is_active',
                        'retry_attempts',
                        'timeout',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function it_creates_webhook_config()
    {
        // Prepare data
        $data = [
            'name' => 'Test Webhook',
            'url' => 'https://example.com/webhook',
            'secret_key' => 'test-secret-key-16chars',
            'is_active' => true,
            'retry_attempts' => 3,
            'timeout' => 30,
        ];

        // Make request
        $response = $this->postJson('/api/v1/admin/webhooks', $data);

        // Assert response
        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'url',
                    'is_active',
                ],
            ]);

        $this->assertTrue($response->json('success'));

        // Assert database
        $this->assertDatabaseHas('webhook_configs', [
            'name' => 'Test Webhook',
            'url' => 'https://example.com/webhook',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_validates_webhook_url_must_be_https()
    {
        // Prepare data with HTTP URL
        $data = [
            'name' => 'Test Webhook',
            'url' => 'http://example.com/webhook',
            'secret_key' => 'test-secret-key',
        ];

        // Make request
        $response = $this->postJson('/api/v1/admin/webhooks', $data);

        // Assert validation error
        $response->assertStatus(400);
        $this->assertFalse($response->json('success'));
    }

    /** @test */
    public function it_updates_webhook_config()
    {
        // Create webhook config
        $webhook = WebhookConfig::factory()->create([
            'name' => 'Original Name',
            'is_active' => true,
        ]);

        // Prepare update data
        $data = [
            'name' => 'Updated Name',
            'url' => 'https://newurl.com/webhook',
            'secret_key' => 'new-secret-key-16chars',
            'is_active' => false,
        ];

        // Make request
        $response = $this->putJson("/api/v1/admin/webhooks/{$webhook->id}", $data);

        // Assert response
        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));

        // Assert database
        $this->assertDatabaseHas('webhook_configs', [
            'id' => $webhook->id,
            'name' => 'Updated Name',
            'url' => 'https://newurl.com/webhook',
            'is_active' => false,
        ]);
    }

    /** @test */
    public function it_deletes_webhook_config()
    {
        // Create webhook config
        $webhook = WebhookConfig::factory()->create();

        // Make request
        $response = $this->deleteJson("/api/v1/admin/webhooks/{$webhook->id}");

        // Assert response
        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));

        // Assert database
        $this->assertDatabaseMissing('webhook_configs', [
            'id' => $webhook->id,
        ]);
    }

    /** @test */
    public function it_tests_webhook_configuration()
    {
        // Create webhook config
        $webhook = WebhookConfig::factory()->create([
            'url' => 'https://example.com/webhook',
            'secret_key' => 'test-secret',
        ]);

        // Mock HTTP response
        Http::fake([
            'example.com/*' => Http::response(['status' => 'ok'], 200),
        ]);

        // Make request
        $response = $this->postJson("/api/v1/admin/webhooks/{$webhook->id}/test");

        // Assert response
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'success',
                    'status_code',
                    'response_body',
                ],
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertTrue($response->json('data.success'));
        $this->assertEquals(200, $response->json('data.status_code'));

        // Verify HTTP request was made
        Http::assertSent(function ($request) use ($webhook) {
            $body = json_decode($request->body(), true);
            return $request->url() === $webhook->url &&
                   $body['event'] === 'webhook.test';
        });
    }

    /** @test */
    public function it_lists_all_api_tokens()
    {
        // Create test data
        ApiToken::factory()->count(3)->create();

        // Make request
        $response = $this->getJson('/api/v1/admin/tokens');

        // Assert response
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'last_used_at',
                        'expires_at',
                        'is_active',
                        'created_at',
                    ],
                ],
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertCount(3, $response->json('data'));

        // Verify token is hidden
        $this->assertArrayNotHasKey('token', $response->json('data.0'));
    }

    /** @test */
    public function it_generates_new_api_token()
    {
        // Prepare data
        $data = [
            'name' => 'Test API Token',
            'expires_at' => now()->addYear()->toDateTimeString(),
        ];

        // Make request
        $response = $this->postJson('/api/v1/admin/tokens', $data);

        // Assert response
        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'token',
                    'expires_at',
                    'is_active',
                ],
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertNotEmpty($response->json('data.token'));

        // Assert database
        $this->assertDatabaseHas('api_tokens', [
            'name' => 'Test API Token',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_generates_random_token_string()
    {
        // Create two tokens
        $data = ['name' => 'Token 1'];
        $response1 = $this->postJson('/api/v1/admin/tokens', $data);

        $data = ['name' => 'Token 2'];
        $response2 = $this->postJson('/api/v1/admin/tokens', $data);

        // Assert tokens are different
        $token1 = $response1->json('data.token');
        $token2 = $response2->json('data.token');

        $this->assertNotEquals($token1, $token2);
        $this->assertEquals(64, strlen($token1)); // Standard token length
    }

    /** @test */
    public function it_revokes_api_token()
    {
        // Create token
        $token = ApiToken::factory()->create([
            'is_active' => true,
        ]);

        // Make request
        $response = $this->putJson("/api/v1/admin/tokens/{$token->id}/revoke");

        // Assert response
        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));

        // Assert database
        $this->assertDatabaseHas('api_tokens', [
            'id' => $token->id,
            'is_active' => false,
        ]);
    }

    /** @test */
    public function it_validates_required_fields_for_webhook_creation()
    {
        // Make request with missing fields
        $response = $this->postJson('/api/v1/admin/webhooks', []);

        // Assert validation error
        $response->assertStatus(400);
        $this->assertFalse($response->json('success'));
    }

    /** @test */
    public function it_validates_required_fields_for_token_creation()
    {
        // Make request with missing name
        $response = $this->postJson('/api/v1/admin/tokens', []);

        // Assert validation error
        $response->assertStatus(400);
        $this->assertFalse($response->json('success'));
    }

    /** @test */
    public function it_returns_404_for_non_existent_webhook()
    {
        // Make request with non-existent ID
        $response = $this->getJson('/api/v1/admin/webhooks/99999');

        // Assert response
        $response->assertStatus(404);
    }

    /** @test */
    public function it_returns_404_for_non_existent_token()
    {
        // Make request with non-existent ID
        $response = $this->putJson('/api/v1/admin/tokens/99999/revoke');

        // Assert response
        $response->assertStatus(404);
    }

    /** @test */
    public function it_includes_custom_headers_in_webhook_config()
    {
        // Prepare data with custom headers
        $data = [
            'name' => 'Test Webhook',
            'url' => 'https://example.com/webhook',
            'secret_key' => 'test-secret-key-16chars',
            'headers' => [
                'X-Custom-Header' => 'custom-value',
                'X-API-Key' => 'api-key-123',
            ],
        ];

        // Make request
        $response = $this->postJson('/api/v1/admin/webhooks', $data);

        // Assert response
        $response->assertStatus(201);

        // Assert database
        $webhook = WebhookConfig::where('name', 'Test Webhook')->first();
        $this->assertNotNull($webhook);
        $this->assertIsArray($webhook->headers);
        $this->assertEquals('custom-value', $webhook->headers['X-Custom-Header']);
    }
}
