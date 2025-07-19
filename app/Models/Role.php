<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Get the users for the role.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the permissions for the role.
     */
    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class);
    }

    /**
     * Check if the role has a specific permission.
     */
    public function hasPermission(string $routeName): bool
    {
        return $this->permissions()->where('route_name', $routeName)->exists();
    }

    /**
     * Get all route permissions for this role.
     */
    public function getRoutePermissions(): array
    {
        return $this->permissions()->pluck('route_name')->toArray();
    }
}
