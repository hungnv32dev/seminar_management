<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Role;
use App\Models\User;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Collection;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_fillable_attributes()
    {
        $fillable = [
            'name',
            'description',
        ];

        $role = new Role();
        
        $this->assertEquals($fillable, $role->getFillable());
    }

    /** @test */
    public function it_has_many_users()
    {
        $role = Role::factory()->create();
        $users = User::factory()->count(3)->create(['role_id' => $role->id]);

        $this->assertInstanceOf(Collection::class, $role->users);
        $this->assertCount(3, $role->users);
        $this->assertInstanceOf(User::class, $role->users->first());
    }

    /** @test */
    public function it_has_many_permissions()
    {
        $role = Role::factory()->create();
        $permissions = Permission::factory()->count(3)->create(['role_id' => $role->id]);

        $this->assertInstanceOf(Collection::class, $role->permissions);
        $this->assertCount(3, $role->permissions);
        $this->assertInstanceOf(Permission::class, $role->permissions->first());
    }

    /** @test */
    public function it_can_check_if_role_has_permission()
    {
        $role = Role::factory()->create();
        Permission::factory()->create([
            'role_id' => $role->id,
            'route_name' => 'users.index'
        ]);
        Permission::factory()->create([
            'role_id' => $role->id,
            'route_name' => 'workshops.create'
        ]);

        $this->assertTrue($role->hasPermission('users.index'));
        $this->assertTrue($role->hasPermission('workshops.create'));
        $this->assertFalse($role->hasPermission('admin.settings'));
    }

    /** @test */
    public function it_can_get_route_permissions()
    {
        $role = Role::factory()->create();
        $routes = ['users.index', 'users.create', 'workshops.index'];
        
        foreach ($routes as $route) {
            Permission::factory()->create([
                'role_id' => $role->id,
                'route_name' => $route
            ]);
        }

        $permissions = $role->getRoutePermissions();

        $this->assertCount(3, $permissions);
        $this->assertContains('users.index', $permissions);
        $this->assertContains('users.create', $permissions);
        $this->assertContains('workshops.index', $permissions);
    }

    /** @test */
    public function it_can_be_created_with_name_and_description()
    {
        $roleData = [
            'name' => 'Administrator',
            'description' => 'Full system access',
        ];

        $role = Role::create($roleData);

        $this->assertEquals('Administrator', $role->name);
        $this->assertEquals('Full system access', $role->description);
    }

    /** @test */
    public function it_can_be_updated()
    {
        $role = Role::factory()->create(['name' => 'Original Name']);
        
        $role->update(['name' => 'Updated Name']);

        $this->assertEquals('Updated Name', $role->fresh()->name);
    }

    /** @test */
    public function it_maintains_user_relationships_when_updated()
    {
        $role = Role::factory()->create();
        $users = User::factory()->count(2)->create(['role_id' => $role->id]);
        
        $role->update(['description' => 'Updated description']);

        $this->assertCount(2, $role->fresh()->users);
    }

    /** @test */
    public function it_can_be_deleted()
    {
        $role = Role::factory()->create();
        $roleId = $role->id;

        $role->delete();

        $this->assertDatabaseMissing('roles', ['id' => $roleId]);
    }

    /** @test */
    public function deleting_role_affects_users()
    {
        $role = Role::factory()->create();
        $user = User::factory()->create(['role_id' => $role->id]);
        
        $role->delete();

        // User should still exist but role_id should be null or handled by foreign key constraint
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertNull($user->fresh()->role);
    }

    /** @test */
    public function deleting_role_deletes_associated_permissions()
    {
        $role = Role::factory()->create();
        $permissions = Permission::factory()->count(3)->create(['role_id' => $role->id]);
        
        $role->delete();

        foreach ($permissions as $permission) {
            $this->assertDatabaseMissing('permissions', ['id' => $permission->id]);
        }
    }

    /** @test */
    public function it_can_add_permissions()
    {
        $role = Role::factory()->create();
        
        $role->permissions()->create(['route_name' => 'users.index']);
        $role->permissions()->create(['route_name' => 'users.create']);

        $this->assertCount(2, $role->permissions);
        $this->assertTrue($role->hasPermission('users.index'));
        $this->assertTrue($role->hasPermission('users.create'));
    }

    /** @test */
    public function it_can_remove_permissions()
    {
        $role = Role::factory()->create();
        $permission = Permission::factory()->create([
            'role_id' => $role->id,
            'route_name' => 'users.index'
        ]);
        
        $this->assertCount(1, $role->permissions);
        
        $permission->delete();
        
        $this->assertCount(0, $role->fresh()->permissions);
        $this->assertFalse($role->fresh()->hasPermission('users.index'));
    }

    /** @test */
    public function has_permission_returns_false_for_empty_route()
    {
        $role = Role::factory()->create();
        
        $this->assertFalse($role->hasPermission(''));
        $this->assertFalse($role->hasPermission(null));
    }

    /** @test */
    public function get_route_permissions_returns_empty_array_when_no_permissions()
    {
        $role = Role::factory()->create();
        
        $permissions = $role->getRoutePermissions();
        
        $this->assertIsArray($permissions);
        $this->assertEmpty($permissions);
    }

    /** @test */
    public function it_can_have_duplicate_route_names_across_different_roles()
    {
        $role1 = Role::factory()->create(['name' => 'Admin']);
        $role2 = Role::factory()->create(['name' => 'User']);
        
        Permission::factory()->create([
            'role_id' => $role1->id,
            'route_name' => 'users.index'
        ]);
        Permission::factory()->create([
            'role_id' => $role2->id,
            'route_name' => 'users.index'
        ]);

        $this->assertTrue($role1->hasPermission('users.index'));
        $this->assertTrue($role2->hasPermission('users.index'));
    }

    /** @test */
    public function it_prevents_duplicate_permissions_for_same_role()
    {
        $role = Role::factory()->create();
        
        Permission::factory()->create([
            'role_id' => $role->id,
            'route_name' => 'users.index'
        ]);

        // This should fail due to unique constraint
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Permission::factory()->create([
            'role_id' => $role->id,
            'route_name' => 'users.index'
        ]);
    }

    /** @test */
    public function it_can_count_users_with_role()
    {
        $role = Role::factory()->create();
        User::factory()->count(5)->create(['role_id' => $role->id]);

        $this->assertEquals(5, $role->users()->count());
    }

    /** @test */
    public function it_can_count_permissions_for_role()
    {
        $role = Role::factory()->create();
        Permission::factory()->count(3)->create(['role_id' => $role->id]);

        $this->assertEquals(3, $role->permissions()->count());
    }
}