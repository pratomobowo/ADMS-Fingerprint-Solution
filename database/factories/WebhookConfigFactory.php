<?php

namespace Database\Factories;

use App\Models\WebhookConfig;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WebhookConfig>
 */
class WebhookConfigFactory extends Factory
{
    protected $model = WebhookConfig::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'url' => fake()->url(),
            'is_active' => true,
            'secret_key' => Str::random(32),
            'headers' => null,
            'retry_attempts' => 3,
            'timeout' => 30,
        ];
    }
}
