<?php

namespace App\Services;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\Cache;

class RolePermissionService
{
    /**
     * Check if a user has permission for a specific route.
     */
    public function hasRoutePermission(User $user, string $routeName): bool
    {
        // Check if user is active
        if (!$user->is_active) {
            return false;
        }

        // Check if user has a role
        if (!$user->role) {
            return false;
        }

        // Use caching for performance
        $cacheKey = "user_permission_{$user->id}_{$routeName}";
        
        return Cache::remember($cacheKey, 3600, function () use ($user, $routeName) {
            return $user->role->hasPermission($routeName);
        });
    }

    /**
     * Get all permissions for a user.
     */
    public function getUserPermissions(User $user): array
    {
        if (!$user->role) {
            return [];
        }

        $cacheKey = "user_permissions_{$user->id}";
        
        return Cache::remember($cacheKey, 3600, function () use ($user) {
            return $user->role->getRoutePermissions();
        });
    }

    /**
     * Assign permissions to a role.
     */
    public function assignPermissionsToRole(Role $role, array $routeNames): void
    {
        // Delete existing permissions
        $role->permissions()->delete();
        
        // Create new permissions
        foreach ($routeNames as $routeName) {
            Permission::create([
                'role_id' => $role->id,
                'route_name' => $routeName,
            ]);
        }

        // Clear cache for all users with this role
        $this->clearRolePermissionCache($role);
    }

    /**
     * Remove permissions from a role.
     */
    public function removePermissionsFromRole(Role $role, array $routeNames): void
    {
        $role->permissions()->whereIn('route_name', $routeNames)->delete();
        
        // Clear cache for all users with this role
        $this->clearRolePermissionCache($role);
    }

    /**
     * Clear permission cache for all users with a specific role.
     */
    public function clearRolePermissionCache(Role $role): void
    {
        $userIds = $role->users()->pluck('id');
        
        foreach ($userIds as $userId) {
            Cache::forget("user_permissions_{$userId}");
            
            // Clear individual route permission caches
            $permissions = $role->getRoutePermissions();
            foreach ($permissions as $routeName) {
                Cache::forget("user_permission_{$userId}_{$routeName}");
            }
        }
    }

    /**
     * Clear all permission cache for a user.
     */
    public function clearUserPermissionCache(User $user): void
    {
        Cache::forget("user_permissions_{$user->id}");
        
        if ($user->role) {
            $permissions = $user->role->getRoutePermissions();
            foreach ($permissions as $routeName) {
                Cache::forget("user_permission_{$user->id}_{$routeName}");
            }
        }
    }

    /**
     * Get available system routes for permission assignment.
     */
    public function getAvailableRoutes(): array
    {
        return [
            'dashboard' => 'Dashboard',
            'users.index' => 'View Users',
            'users.create' => 'Create User',
            'users.edit' => 'Edit User',
            'users.destroy' => 'Delete User',
            'roles.index' => 'View Roles',
            'roles.create' => 'Create Role',
            'roles.edit' => 'Edit Role',
            'roles.destroy' => 'Delete Role',
            'workshops.index' => 'View Workshops',
            'workshops.create' => 'Create Workshop',
            'workshops.edit' => 'Edit Workshop',
            'workshops.destroy' => 'Delete Workshop',
            'participants.index' => 'View Participants',
            'participants.create' => 'Create Participant',
            'participants.edit' => 'Edit Participant',
            'participants.destroy' => 'Delete Participant',
            'participants.import' => 'Import Participants',
            'ticket-types.index' => 'View Ticket Types',
            'ticket-types.create' => 'Create Ticket Type',
            'ticket-types.edit' => 'Edit Ticket Type',
            'ticket-types.destroy' => 'Delete Ticket Type',
            'email-templates.index' => 'View Email Templates',
            'email-templates.create' => 'Create Email Template',
            'email-templates.edit' => 'Edit Email Template',
            'email-templates.destroy' => 'Delete Email Template',
            'checkin.index' => 'Check-in System',
        ];
    }

    /**
     * Check if a role can be deleted (no users assigned).
     */
    public function canDeleteRole(Role $role): bool
    {
        return $role->users()->count() === 0;
    }

    /**
     * Get roles with user count.
     */
    public function getRolesWithUserCount()
    {
        return Role::withCount('users')->get();
    }
}