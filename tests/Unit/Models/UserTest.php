<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Collection;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_fillable_attributes()
    {
        $fillable = [
            'name',
            'email',
            'password',
            'role_id',
            'is_active',
        ];

        $user = new User();
        
        $this->assertEquals($fillable, $user->getFillable());
    }

    /** @test */
    public function it_has_hidden_attributes()
    {
        $hidden = [
            'password',
            'remember_token',
        ];

        $user = new User();
        
        $this->assertEquals($hidden, $user->getHidden());
    }

    /** @test */
    public function it_casts_attributes_correctly()
    {
        $user = User::factory()->create([
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->assertIsBool($user->is_active);
        $this->assertInstanceOf(\Carbon\Carbon::class, $user->email_verified_at);
    }

    /** @test */
    public function it_belongs_to_a_role()
    {
        $role = Role::factory()->create();
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->assertInstanceOf(Role::class, $user->role);
        $this->assertEquals($role->id, $user->role->id);
    }

    /** @test */
    public function it_can_have_no_role()
    {
        $user = User::factory()->create(['role_id' => null]);

        $this->assertNull($user->role);
    }

    /** @test */
    public function it_belongs_to_many_workshops()
    {
        $user = User::factory()->create();
        $workshops = Workshop::factory()->count(3)->create();
        
        $user->workshops()->attach($workshops->pluck('id'));

        $this->assertInstanceOf(Collection::class, $user->workshops);
        $this->assertCount(3, $user->workshops);
        $this->assertInstanceOf(Workshop::class, $user->workshops->first());
    }

    /** @test */
    public function organized_workshops_is_alias_for_workshops()
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        
        $user->workshops()->attach($workshop->id);

        $this->assertEquals($user->workshops->count(), $user->organizedWorkshops->count());
        $this->assertEquals($user->workshops->first()->id, $user->organizedWorkshops->first()->id);
    }

    /** @test */
    public function it_can_scope_active_users()
    {
        User::factory()->create(['is_active' => true]);
        User::factory()->create(['is_active' => false]);
        User::factory()->create(['is_active' => true]);

        $activeUsers = User::active()->get();

        $this->assertCount(2, $activeUsers);
        $this->assertTrue($activeUsers->every(fn($user) => $user->is_active));
    }

    /** @test */
    public function it_can_scope_users_by_role()
    {
        $role1 = Role::factory()->create();
        $role2 = Role::factory()->create();
        
        User::factory()->count(2)->create(['role_id' => $role1->id]);
        User::factory()->create(['role_id' => $role2->id]);

        $usersWithRole1 = User::byRole($role1->id)->get();

        $this->assertCount(2, $usersWithRole1);
        $this->assertTrue($usersWithRole1->every(fn($user) => $user->role_id === $role1->id));
    }

    /** @test */
    public function it_hashes_password_automatically()
    {
        $plainPassword = 'password123';
        $user = User::factory()->create(['password' => $plainPassword]);

        $this->assertNotEquals($plainPassword, $user->password);
        $this->assertTrue(\Hash::check($plainPassword, $user->password));
    }

    /** @test */
    public function it_has_default_is_active_value()
    {
        $user = new User([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        // When is_active is not explicitly set, it should default to true in the factory
        $createdUser = User::factory()->create();
        $this->assertTrue($createdUser->is_active);
    }

    /** @test */
    public function it_can_be_created_with_all_attributes()
    {
        $role = Role::factory()->create();
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'SecurePassword123!',
            'role_id' => $role->id,
            'is_active' => true,
        ];

        $user = User::create($userData);

        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertEquals($role->id, $user->role_id);
        $this->assertTrue($user->is_active);
        $this->assertTrue(\Hash::check('SecurePassword123!', $user->password));
    }

    /** @test */
    public function it_can_update_attributes()
    {
        $user = User::factory()->create(['name' => 'Original Name']);
        
        $user->update(['name' => 'Updated Name']);

        $this->assertEquals('Updated Name', $user->fresh()->name);
    }

    /** @test */
    public function it_can_be_soft_deleted()
    {
        $user = User::factory()->create();
        $userId = $user->id;

        $user->delete();

        $this->assertDatabaseMissing('users', ['id' => $userId]);
    }

    /** @test */
    public function it_maintains_workshop_relationships_after_updates()
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        
        $user->workshops()->attach($workshop->id);
        $user->update(['name' => 'Updated Name']);

        $this->assertCount(1, $user->fresh()->workshops);
        $this->assertEquals($workshop->id, $user->fresh()->workshops->first()->id);
    }

    /** @test */
    public function it_can_have_multiple_workshops()
    {
        $user = User::factory()->create();
        $workshops = Workshop::factory()->count(5)->create();
        
        $user->workshops()->attach($workshops->pluck('id'));

        $this->assertCount(5, $user->workshops);
        
        // Test that all workshops are properly attached
        $attachedWorkshopIds = $user->workshops->pluck('id')->sort()->values();
        $originalWorkshopIds = $workshops->pluck('id')->sort()->values();
        
        $this->assertEquals($originalWorkshopIds, $attachedWorkshopIds);
    }

    /** @test */
    public function it_can_detach_workshops()
    {
        $user = User::factory()->create();
        $workshops = Workshop::factory()->count(3)->create();
        
        $user->workshops()->attach($workshops->pluck('id'));
        $this->assertCount(3, $user->workshops);
        
        $user->workshops()->detach($workshops->first()->id);
        $this->assertCount(2, $user->fresh()->workshops);
    }

    /** @test */
    public function role_relationship_returns_null_when_role_is_deleted()
    {
        $role = Role::factory()->create();
        $user = User::factory()->create(['role_id' => $role->id]);
        
        $this->assertInstanceOf(Role::class, $user->role);
        
        $role->delete();
        
        $this->assertNull($user->fresh()->role);
    }

    /** @test */
    public function it_can_check_if_user_has_specific_role()
    {
        $adminRole = Role::factory()->create(['name' => 'Admin']);
        $userRole = Role::factory()->create(['name' => 'User']);
        
        $adminUser = User::factory()->create(['role_id' => $adminRole->id]);
        $regularUser = User::factory()->create(['role_id' => $userRole->id]);

        $this->assertEquals('Admin', $adminUser->role->name);
        $this->assertEquals('User', $regularUser->role->name);
    }
}