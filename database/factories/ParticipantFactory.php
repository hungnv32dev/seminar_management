<?php

namespace Database\Factories;

use App\Models\Participant;
use App\Models\Workshop;
use App\Models\TicketType;
use Illuminate\Database\Eloquent\Factories\Factory;

class ParticipantFactory extends Factory
{
    protected $model = Participant::class;

    public function definition(): array
    {
        return [
            'workshop_id' => Workshop::factory(),
            'ticket_type_id' => TicketType::factory(),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'occupation' => $this->faker->jobTitle(),
            'address' => $this->faker->address(),
            'company' => $this->faker->company(),
            'position' => $this->faker->jobTitle(),
            'ticket_code' => $this->faker->unique()->regexify('[A-Z0-9]{8}'),
            'is_paid' => $this->faker->boolean(70), // 70% chance of being paid
            'is_checked_in' => $this->faker->boolean(30), // 30% chance of being checked in
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_paid' => true,
        ]);
    }

    public function unpaid(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_paid' => false,
        ]);
    }

    public function checkedIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_checked_in' => true,
        ]);
    }

    public function notCheckedIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_checked_in' => false,
        ]);
    }

    public function paidAndCheckedIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_paid' => true,
            'is_checked_in' => true,
        ]);
    }

    public function fromCompany(string $company): static
    {
        return $this->state(fn (array $attributes) => [
            'company' => $company,
        ]);
    }

    public function withPosition(string $position): static
    {
        return $this->state(fn (array $attributes) => [
            'position' => $position,
        ]);
    }
}