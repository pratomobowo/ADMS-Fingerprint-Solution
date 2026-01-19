<?php

namespace Database\Factories;

use App\Models\Attendance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attendance>
 */
class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sn' => fake()->bothify('DEVICE###'),
            'table' => 'ATTLOG',
            'stamp' => fake()->numerify('##########'),
            'employee_id' => fake()->numerify('###'),
            'timestamp' => fake()->dateTimeBetween('-30 days', 'now'),
            'status1' => fake()->boolean(70),
            'status2' => fake()->boolean(30),
            'status3' => fake()->boolean(20),
            'status4' => fake()->boolean(10),
            'status5' => fake()->boolean(10),
        ];
    }
}
