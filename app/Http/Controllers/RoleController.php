<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleRequest;
use App\Models\Role;
use App\Models\Permission;
use App\Services\RolePermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    protected $rolePermissionService;

    public function __construct(RolePermissionService $rolePermissionService)
    {
        $this->rolePermissionService = $rolePermissionService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles = $this->rolePermissionService->getRolesWithUserCount();
        return view('roles.index', compact('roles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $availableRoutes = $this->rolePermissionService->getAvailableRoutes();
        return view('roles.create', compact('availableRoutes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RoleRequest $request)
    {
        DB::transaction(function () use ($request) {
            $role = Role::create($request->validated());
            
            if ($request->has('permissions')) {
                $this->rolePermissionService->assignPermissionsToRole($role, $request->permissions);
            }
        });

        return redirect()->route('roles.index')
            ->with('success', 'Role created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role)
    {
        $role->load('permissions', 'users');
        return view('roles.show', compact('role'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role)
    {
        $role->load('permissions');
        $availableRoutes = $this->rolePermissionService->getAvailableRoutes();
        $rolePermissions = $role->permissions->pluck('route_name')->toArray();
        
        return view('roles.edit', compact('role', 'availableRoutes', 'rolePermissions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RoleRequest $request, Role $role)
    {
        DB::transaction(function () use ($request, $role) {
            $role->update($request->validated());
            
            $this->rolePermissionService->assignPermissionsToRole($role, $request->permissions ?? []);
        });

        return redirect()->route('roles.index')
            ->with('success', 'Role updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        // Check if role can be deleted using service
        if (!$this->rolePermissionService->canDeleteRole($role)) {
            return redirect()->route('roles.index')
                ->with('error', 'Cannot delete role that has users assigned to it.');
        }

        $role->delete();

        return redirect()->route('roles.index')
            ->with('success', 'Role deleted successfully.');
    }


}
