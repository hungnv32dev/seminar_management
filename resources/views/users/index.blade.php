@extends('layouts.app')

@section('title', 'User Management')

@section('toolbar')
    <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
        <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
            User Management
        </h1>
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
            <li class="breadcrumb-item text-muted">
                <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">Home</a>
            </li>
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-400 w-5px h-2px"></span>
            </li>
            <li class="breadcrumb-item text-muted">User Management</li>
        </ul>
    </div>
    
    <div class="d-flex align-items-center gap-2 gap-lg-3">
        <a href="{{ route('users.create') }}" class="btn btn-sm btn-primary">
            <i class="ki-duotone ki-plus fs-2">
                <span class="path1"></span>
                <span class="path2"></span>
                <span class="path3"></span>
            </i>
            Add User
        </a>
    </div>
@endsection

@section('content')

<!-- Filters -->
<div class="card card-flush mb-5">
    <div class="card-header align-items-center py-5 gap-2 gap-md-5">
        <div class="card-title">
            <h3 class="fw-bold">Filters</h3>
        </div>
    </div>
    <div class="card-body pt-0">
        <form method="GET" action="{{ route('users.index') }}" class="d-flex flex-column flex-md-row gap-3">
            <div class="flex-grow-1">
                <label class="form-label">Search Users</label>
                <input type="text" name="search" class="form-control form-control-solid" 
                       placeholder="Search by name or email..." value="{{ request('search') }}">
            </div>
            <div class="min-w-200px">
                <label class="form-label">Role</label>
                <select name="role_id" class="form-select form-select-solid" data-control="select2" data-placeholder="All Roles">
                    <option value="">All Roles</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" {{ request('role_id') == $role->id ? 'selected' : '' }}>
                            {{ $role->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-150px">
                <label class="form-label">Status</label>
                <select name="status" class="form-select form-select-solid" data-control="select2" data-placeholder="All Status">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="ki-duotone ki-magnifier fs-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    Search
                </button>
                <a href="{{ route('users.index') }}" class="btn btn-light">
                    <i class="ki-duotone ki-cross fs-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Users Table -->
<div class="card card-flush">
    <div class="card-header align-items-center py-5 gap-2 gap-md-5">
        <div class="card-title">
            <h3 class="fw-bold">Users ({{ $users->total() }})</h3>
        </div>
        <div class="card-toolbar">
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-light-success" id="bulk-activate-btn" disabled>
                    <i class="ki-duotone ki-check fs-4">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    Bulk Activate
                </button>
                <button type="button" class="btn btn-sm btn-light-warning" id="bulk-deactivate-btn" disabled>
                    <i class="ki-duotone ki-cross fs-4">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    Bulk Deactivate
                </button>
                <a href="{{ route('users.export', ['format' => 'csv'] + request()->query()) }}" class="btn btn-sm btn-light-primary">
                    <i class="ki-duotone ki-exit-down fs-4">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    Export CSV
                </a>
            </div>
        </div>
    </div>
    <div class="card-body pt-0">
        @if($users->count() > 0)
            <div class="table-responsive">
                <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
                    <thead>
                        <tr class="fw-bold text-muted">
                            <th class="w-25px">
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" id="select-all">
                                </div>
                            </th>
                            <th class="min-w-200px">User</th>
                            <th class="min-w-150px">Role</th>
                            <th class="min-w-100px">Status</th>
                            <th class="min-w-100px">Workshops</th>
                            <th class="min-w-100px">Joined</th>
                            <th class="min-w-100px text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                        <tr data-user-id="{{ $user->id }}">
                            <td>
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input user-checkbox" type="checkbox" value="{{ $user->id }}" 
                                           {{ $user->id === auth()->id() ? 'disabled' : '' }}>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-45px me-5">
                                        <div class="symbol-label bg-light-primary text-primary fs-6 fw-bolder">
                                            {{ substr($user->name, 0, 1) }}
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-start flex-column">
                                        <a href="{{ route('users.show', $user) }}" class="text-dark fw-bold text-hover-primary fs-6">
                                            {{ $user->name }}
                                            @if($user->id === auth()->id())
                                                <span class="badge badge-light-info ms-2">You</span>
                                            @endif
                                        </a>
                                        <span class="text-muted fw-semibold text-muted d-block fs-7">{{ $user->email }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($user->role)
                                    <span class="badge badge-light-primary">{{ $user->role->name }}</span>
                                @else
                                    <span class="badge badge-light-secondary">No Role</span>
                                @endif
                            </td>
                            <td>
                                @if($user->is_active)
                                    <span class="badge badge-light-success">
                                        <i class="ki-duotone ki-check fs-7">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        Active
                                    </span>
                                @else
                                    <span class="badge badge-light-danger">
                                        <i class="ki-duotone ki-cross fs-7">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            <td>
                                <span class="text-dark fw-bold fs-6">{{ $user->organizedWorkshops->count() }}</span>
                                <span class="text-muted fs-7">workshops</span>
                            </td>
                            <td>
                                <span class="text-dark fw-bold d-block fs-6">{{ $user->created_at->format('M d, Y') }}</span>
                                <span class="text-muted fw-semibold d-block fs-7">{{ $user->created_at->diffForHumans() }}</span>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end flex-shrink-0">
                                    <!-- Status Toggle -->
                                    @if($user->id !== auth()->id())
                                        <form method="POST" action="{{ route('users.toggle-status', $user) }}" class="d-inline me-1">
                                            @csrf
                                            <button type="submit" class="btn btn-icon btn-bg-light btn-sm" 
                                                    title="{{ $user->is_active ? 'Deactivate' : 'Activate' }} User">
                                                @if($user->is_active)
                                                    <i class="ki-duotone ki-cross fs-3 text-warning">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>
                                                @else
                                                    <i class="ki-duotone ki-check fs-3 text-success">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>
                                                @endif
                                            </button>
                                        </form>
                                    @endif
                                    
                                    <!-- View -->
                                    <a href="{{ route('users.show', $user) }}" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1" title="View User">
                                        <i class="ki-duotone ki-eye fs-3">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                        </i>
                                    </a>
                                    
                                    <!-- Edit -->
                                    <a href="{{ route('users.edit', $user) }}" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1" title="Edit User">
                                        <i class="ki-duotone ki-pencil fs-3">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                    </a>
                                    
                                    <!-- Delete -->
                                    @if($user->id !== auth()->id())
                                        <form method="POST" action="{{ route('users.destroy', $user) }}" class="d-inline" 
                                              onsubmit="return confirm('Are you sure you want to delete this user?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-icon btn-bg-light btn-active-color-danger btn-sm" title="Delete User">
                                                <i class="ki-duotone ki-trash fs-3">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                    <span class="path3"></span>
                                                    <span class="path4"></span>
                                                    <span class="path5"></span>
                                                </i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-5">
                <div class="text-muted">
                    Showing {{ $users->firstItem() }} to {{ $users->lastItem() }} of {{ $users->total() }} users
                </div>
                {{ $users->appends(request()->query())->links() }}
            </div>
        @else
            <div class="text-center py-10">
                <i class="ki-duotone ki-user fs-3x text-gray-400 mb-5">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
                <h4 class="text-gray-600 fw-bold mb-3">No Users Found</h4>
                <p class="text-gray-500 mb-5">
                    @if(request()->hasAny(['search', 'role_id', 'status']))
                        No users match your current filters. Try adjusting your search criteria.
                    @else
                        There are no users in the system yet.
                    @endif
                </p>
                @if(!request()->hasAny(['search', 'role_id', 'status']))
                    <a href="{{ route('users.create') }}" class="btn btn-primary">
                        <i class="ki-duotone ki-plus fs-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                        </i>
                        Add First User
                    </a>
                @endif
            </div>
        @endif
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Select all checkbox
    $('#select-all').change(function() {
        $('.user-checkbox:not(:disabled)').prop('checked', this.checked);
        updateBulkButtons();
    });
    
    // Individual checkboxes
    $(document).on('change', '.user-checkbox', function() {
        updateBulkButtons();
        
        // Update select all checkbox
        const totalCheckboxes = $('.user-checkbox:not(:disabled)').length;
        const checkedCheckboxes = $('.user-checkbox:checked').length;
        $('#select-all').prop('checked', totalCheckboxes === checkedCheckboxes);
    });
    
    // Bulk activate button
    $('#bulk-activate-btn').click(function() {
        const selectedIds = getSelectedUserIds();
        if (selectedIds.length === 0) return;
        
        if (confirm(`Are you sure you want to activate ${selectedIds.length} user(s)?`)) {
            bulkAction('{{ route("users.bulk-activate") }}', selectedIds);
        }
    });
    
    // Bulk deactivate button
    $('#bulk-deactivate-btn').click(function() {
        const selectedIds = getSelectedUserIds();
        if (selectedIds.length === 0) return;
        
        if (confirm(`Are you sure you want to deactivate ${selectedIds.length} user(s)?`)) {
            bulkAction('{{ route("users.bulk-deactivate") }}', selectedIds);
        }
    });
});

function updateBulkButtons() {
    const checkedCount = $('.user-checkbox:checked').length;
    const activateBtn = $('#bulk-activate-btn');
    const deactivateBtn = $('#bulk-deactivate-btn');
    
    if (checkedCount > 0) {
        activateBtn.prop('disabled', false).text(`Bulk Activate (${checkedCount})`);
        deactivateBtn.prop('disabled', false).text(`Bulk Deactivate (${checkedCount})`);
    } else {
        activateBtn.prop('disabled', true).text('Bulk Activate');
        deactivateBtn.prop('disabled', true).text('Bulk Deactivate');
    }
}

function getSelectedUserIds() {
    return $('.user-checkbox:checked').map(function() {
        return this.value;
    }).get();
}

function bulkAction(url, userIds) {
    const form = $('<form>', {
        method: 'POST',
        action: url
    });
    
    form.append($('<input>', {
        type: 'hidden',
        name: '_token',
        value: '{{ csrf_token() }}'
    }));
    
    userIds.forEach(function(id) {
        form.append($('<input>', {
            type: 'hidden',
            name: 'user_ids[]',
            value: id
        }));
    });
    
    $('body').append(form);
    form.submit();
}
</script>
@endpush

@push('styles')
<style>
.table-responsive {
    border-radius: 0.475rem;
}

.user-checkbox:disabled {
    opacity: 0.5;
}

@media (max-width: 768px) {
    .card-toolbar {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .d-flex.gap-2 {
        flex-wrap: wrap;
    }
    
    .btn-sm {
        font-size: 0.75rem;
        padding: 0.375rem 0.75rem;
    }
}
</style>
@endpush