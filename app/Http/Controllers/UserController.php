<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Http\Requests\UserRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * Display a listing of users with optional role filtering.
     */
    public function index(Request $request): View
    {
        $request->validate([
            'role_id' => 'nullable|exists:roles,id',
            'status' => 'nullable|in:active,inactive',
            'search' => 'nullable|string|max:255',
        ]);

        $query = User::with('role');

        // Apply role filter
        if ($request->filled('role_id')) {
            $query->where('role_id', $request->role_id);
        }

        // Apply status filter
        if ($request->filled('status')) {
            $isActive = $request->status === 'active';
            $query->where('is_active', $isActive);
        }

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15);
        $roles = Role::orderBy('name')->get();

        return view('users.index', compact('users', 'roles'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): View
    {
        $roles = Role::orderBy('name')->get();
        return view('users.create', compact('roles'));
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(UserRequest $request): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $userData = $request->validated();
            $userData['password'] = Hash::make($userData['password']);
            $userData['is_active'] = $request->boolean('is_active', true);

            $user = User::create($userData);

            DB::commit();

            Log::info('User created successfully', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'role_id' => $user->role_id,
                'created_by' => auth()->id(),
            ]);

            return redirect()->route('users.index')
                ->with('success', "User '{$user->name}' has been created successfully.");

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to create user', [
                'error' => $e->getMessage(),
                'request_data' => $request->except('password'),
            ]);

            return redirect()->back()
                ->withInput($request->except('password'))
                ->with('error', 'Failed to create user. Please try again.');
        }
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): View
    {
        $user->load(['role', 'organizedWorkshops']);
        
        // Get user statistics
        $statistics = [
            'workshops_organized' => $user->organizedWorkshops()->count(),
            'active_workshops' => $user->organizedWorkshops()->whereIn('status', ['published', 'ongoing'])->count(),
            'completed_workshops' => $user->organizedWorkshops()->where('status', 'completed')->count(),
            'total_participants' => $user->organizedWorkshops()->withCount('participants')->get()->sum('participants_count'),
        ];

        return view('users.show', compact('user', 'statistics'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): View
    {
        $roles = Role::orderBy('name')->get();
        return view('users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(UserRequest $request, User $user): RedirectResponse
    {
        try {
            DB::beginTransaction();

            $userData = $request->validated();
            
            // Only update password if provided
            if (!empty($userData['password'])) {
                $userData['password'] = Hash::make($userData['password']);
            } else {
                unset($userData['password']);
            }

            $userData['is_active'] = $request->boolean('is_active', $user->is_active);

            $oldRoleId = $user->role_id;
            $user->update($userData);

            DB::commit();

            Log::info('User updated successfully', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'old_role_id' => $oldRoleId,
                'new_role_id' => $user->role_id,
                'updated_by' => auth()->id(),
            ]);

            return redirect()->route('users.index')
                ->with('success', "User '{$user->name}' has been updated successfully.");

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to update user', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'request_data' => $request->except('password'),
            ]);

            return redirect()->back()
                ->withInput($request->except('password'))
                ->with('error', 'Failed to update user. Please try again.');
        }
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        try {
            // Prevent deletion of the current user
            if ($user->id === auth()->id()) {
                return redirect()->back()
                    ->with('error', 'You cannot delete your own account.');
            }

            // Check if user has organized workshops
            $workshopsCount = $user->organizedWorkshops()->count();
            if ($workshopsCount > 0) {
                return redirect()->back()
                    ->with('error', "Cannot delete user '{$user->name}' because they have organized {$workshopsCount} workshop(s). Please reassign or remove their workshops first.");
            }

            $userName = $user->name;
            $user->delete();

            Log::info('User deleted successfully', [
                'user_id' => $user->id,
                'user_name' => $userName,
                'deleted_by' => auth()->id(),
            ]);

            return redirect()->route('users.index')
                ->with('success', "User '{$userName}' has been deleted successfully.");

        } catch (\Exception $e) {
            Log::error('Failed to delete user', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Failed to delete user. Please try again.');
        }
    }

    /**
     * Activate the specified user.
     */
    public function activate(User $user): RedirectResponse
    {
        try {
            if ($user->is_active) {
                return redirect()->back()
                    ->with('warning', "User '{$user->name}' is already active.");
            }

            $user->update(['is_active' => true]);

            Log::info('User activated', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'activated_by' => auth()->id(),
            ]);

            return redirect()->back()
                ->with('success', "User '{$user->name}' has been activated successfully.");

        } catch (\Exception $e) {
            Log::error('Failed to activate user', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Failed to activate user. Please try again.');
        }
    }

    /**
     * Deactivate the specified user.
     */
    public function deactivate(User $user): RedirectResponse
    {
        try {
            // Prevent deactivation of the current user
            if ($user->id === auth()->id()) {
                return redirect()->back()
                    ->with('error', 'You cannot deactivate your own account.');
            }

            if (!$user->is_active) {
                return redirect()->back()
                    ->with('warning', "User '{$user->name}' is already inactive.");
            }

            $user->update(['is_active' => false]);

            Log::info('User deactivated', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'deactivated_by' => auth()->id(),
            ]);

            return redirect()->back()
                ->with('success', "User '{$user->name}' has been deactivated successfully.");

        } catch (\Exception $e) {
            Log::error('Failed to deactivate user', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Failed to deactivate user. Please try again.');
        }
    }

    /**
     * Toggle user activation status.
     */
    public function toggleStatus(User $user): RedirectResponse
    {
        if ($user->is_active) {
            return $this->deactivate($user);
        } else {
            return $this->activate($user);
        }
    }

    /**
     * Bulk activate selected users.
     */
    public function bulkActivate(Request $request): RedirectResponse
    {
        $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
        ]);

        try {
            $userIds = $request->user_ids;
            
            // Exclude current user from bulk operations
            $userIds = array_filter($userIds, function ($id) {
                return $id != auth()->id();
            });

            if (empty($userIds)) {
                return redirect()->back()
                    ->with('warning', 'No valid users selected for activation.');
            }

            $updatedCount = User::whereIn('id', $userIds)
                ->where('is_active', false)
                ->update(['is_active' => true]);

            Log::info('Bulk user activation', [
                'user_ids' => $userIds,
                'updated_count' => $updatedCount,
                'activated_by' => auth()->id(),
            ]);

            return redirect()->back()
                ->with('success', "Successfully activated {$updatedCount} user(s).");

        } catch (\Exception $e) {
            Log::error('Failed to bulk activate users', [
                'user_ids' => $request->user_ids,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Failed to activate users. Please try again.');
        }
    }

    /**
     * Bulk deactivate selected users.
     */
    public function bulkDeactivate(Request $request): RedirectResponse
    {
        $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
        ]);

        try {
            $userIds = $request->user_ids;
            
            // Exclude current user from bulk operations
            $userIds = array_filter($userIds, function ($id) {
                return $id != auth()->id();
            });

            if (empty($userIds)) {
                return redirect()->back()
                    ->with('warning', 'Cannot deactivate your own account or no valid users selected.');
            }

            $updatedCount = User::whereIn('id', $userIds)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            Log::info('Bulk user deactivation', [
                'user_ids' => $userIds,
                'updated_count' => $updatedCount,
                'deactivated_by' => auth()->id(),
            ]);

            return redirect()->back()
                ->with('success', "Successfully deactivated {$updatedCount} user(s).");

        } catch (\Exception $e) {
            Log::error('Failed to bulk deactivate users', [
                'user_ids' => $request->user_ids,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Failed to deactivate users. Please try again.');
        }
    }

    /**
     * Change user role.
     */
    public function changeRole(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
        ]);

        try {
            $oldRole = $user->role;
            $newRole = Role::findOrFail($request->role_id);

            $user->update(['role_id' => $request->role_id]);

            Log::info('User role changed', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'old_role' => $oldRole ? $oldRole->name : 'None',
                'new_role' => $newRole->name,
                'changed_by' => auth()->id(),
            ]);

            return redirect()->back()
                ->with('success', "User '{$user->name}' role has been changed to '{$newRole->name}' successfully.");

        } catch (\Exception $e) {
            Log::error('Failed to change user role', [
                'user_id' => $user->id,
                'role_id' => $request->role_id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->with('error', 'Failed to change user role. Please try again.');
        }
    }

    /**
     * Get users by role (API endpoint).
     */
    public function getUsersByRole(Request $request)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
        ]);

        $users = User::where('role_id', $request->role_id)
            ->where('is_active', true)
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'users' => $users,
        ]);
    }

    /**
     * Search users (API endpoint).
     */
    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2|max:255',
            'role_id' => 'nullable|exists:roles,id',
            'status' => 'nullable|in:active,inactive',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $query = User::with('role');
        $searchTerm = $request->query;

        // Apply search
        $query->where(function ($q) use ($searchTerm) {
            $q->where('name', 'like', "%{$searchTerm}%")
              ->orWhere('email', 'like', "%{$searchTerm}%");
        });

        // Apply filters
        if ($request->filled('role_id')) {
            $query->where('role_id', $request->role_id);
        }

        if ($request->filled('status')) {
            $isActive = $request->status === 'active';
            $query->where('is_active', $isActive);
        }

        $limit = $request->get('limit', 20);
        $users = $query->limit($limit)->get();

        return response()->json([
            'success' => true,
            'users' => $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role ? $user->role->name : 'No Role',
                    'is_active' => $user->is_active,
                    'status' => $user->is_active ? 'Active' : 'Inactive',
                ];
            }),
            'total_found' => $users->count(),
        ]);
    }

    /**
     * Get user statistics (API endpoint).
     */
    public function getStatistics()
    {
        $statistics = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'inactive_users' => User::where('is_active', false)->count(),
            'users_by_role' => Role::withCount('users')->get()->map(function ($role) {
                return [
                    'role_name' => $role->name,
                    'user_count' => $role->users_count,
                ];
            }),
            'recent_registrations' => User::where('created_at', '>=', now()->subDays(30))->count(),
        ];

        return response()->json([
            'success' => true,
            'statistics' => $statistics,
        ]);
    }

    /**
     * Export users data.
     */
    public function export(Request $request)
    {
        $request->validate([
            'format' => 'required|in:csv,json',
            'role_id' => 'nullable|exists:roles,id',
            'status' => 'nullable|in:active,inactive',
        ]);

        $query = User::with('role');

        // Apply filters
        if ($request->filled('role_id')) {
            $query->where('role_id', $request->role_id);
        }

        if ($request->filled('status')) {
            $isActive = $request->status === 'active';
            $query->where('is_active', $isActive);
        }

        $users = $query->orderBy('name')->get();

        if ($request->format === 'csv') {
            return $this->exportToCsv($users);
        }

        return response()->json([
            'success' => true,
            'data' => $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role ? $user->role->name : 'No Role',
                    'status' => $user->is_active ? 'Active' : 'Inactive',
                    'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $user->updated_at->format('Y-m-d H:i:s'),
                ];
            }),
            'export_info' => [
                'total_records' => $users->count(),
                'exported_at' => now()->toISOString(),
                'filters_applied' => array_filter([
                    'role_id' => $request->role_id,
                    'status' => $request->status,
                ]),
            ],
        ]);
    }

    /**
     * Export users to CSV format.
     */
    private function exportToCsv($users)
    {
        $filename = 'users_export_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($users) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'ID', 'Name', 'Email', 'Role', 'Status', 'Created At', 'Updated At'
            ]);

            // CSV data
            foreach ($users as $user) {
                fputcsv($file, [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->role ? $user->role->name : 'No Role',
                    $user->is_active ? 'Active' : 'Inactive',
                    $user->created_at->format('Y-m-d H:i:s'),
                    $user->updated_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}