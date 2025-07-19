<?php

namespace Database\Factories;

use App\Models\TicketType;
use App\Models\Workshop;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketTypeFactory extends Factory
{
    protected $model = TicketType::class;

    public function definition(): array
    {
        return [
            'workshop_id' => Workshop::factory(),
            'name' => $this->faker->randomElement(['Standard', 'Premium', 'VIP', 'Early Bird', 'Student']),
            'fee' => $this->faker->randomFloat(2, 0, 500),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function free(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Free',
            'fee' => 0,
        ]);
    }

    public function standard(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Standard',
            'fee' => 100,
        ]);
    }

    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Premium',
            'fee' => 200,
        ]);
    }

    public function vip(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'VIP',
            'fee' => 500,
        ]);
    }
}