<?php

namespace Database\Factories;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    public function definition(): array
    {
        $routes = [
            'dashboard',
            'users.index',
            'users.create',
            'users.store',
            'users.show',
            'users.edit',
            'users.update',
            'users.destroy',
            'workshops.index',
            'workshops.create',
            'workshops.store',
            'workshops.show',
            'workshops.edit',
            'workshops.update',
            'workshops.destroy',
            'participants.index',
            'participants.create',
            'participants.store',
            'participants.show',
            'participants.edit',
            'participants.update',
            'participants.destroy',
            'checkin.index',
            'checkin.scan',
            'roles.index',
            'roles.create',
            'roles.store',
            'roles.show',
            'roles.edit',
            'roles.update',
            'roles.destroy',
        ];

        return [
            'role_id' => Role::factory(),
            'route_name' => $this->faker->randomElement($routes),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function forRole(Role $role): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => $role->id,
        ]);
    }

    public function withRoute(string $routeName): static
    {
        return $this->state(fn (array $attributes) => [
            'route_name' => $routeName,
        ]);
    }

    public function adminPermissions(): static
    {
        return $this->state(fn (array $attributes) => [
            'route_name' => $this->faker->randomElement([
                'users.index', 'users.create', 'users.destroy',
                'roles.index', 'roles.create', 'roles.destroy',
                'workshops.index', 'workshops.create', 'workshops.destroy',
            ]),
        ]);
    }

    public function organizerPermissions(): static
    {
        return $this->state(fn (array $attributes) => [
            'route_name' => $this->faker->randomElement([
                'workshops.index', 'workshops.create', 'workshops.edit',
                'participants.index', 'participants.create', 'participants.edit',
                'checkin.index', 'checkin.scan',
            ]),
        ]);
    }

    public function viewerPermissions(): static
    {
        return $this->state(fn (array $attributes) => [
            'route_name' => $this->faker->randomElement([
                'dashboard', 'workshops.index', 'workshops.show',
                'participants.index', 'participants.show',
            ]),
        ]);
    }
}