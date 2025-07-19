<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->randomElement(['Admin', 'Manager', 'Organizer', 'Staff', 'Viewer']),
            'description' => $this->faker->sentence(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Admin',
            'description' => 'Full system administrator access',
        ]);
    }

    public function manager(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Manager',
            'description' => 'Workshop management access',
        ]);
    }

    public function organizer(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Organizer',
            'description' => 'Workshop organizer access',
        ]);
    }

    public function staff(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Staff',
            'description' => 'Staff member access',
        ]);
    }

    public function viewer(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Viewer',
            'description' => 'Read-only access',
        ]);
    }
}