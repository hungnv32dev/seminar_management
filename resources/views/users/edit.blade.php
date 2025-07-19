@extends('layouts.app')

@section('title', 'Edit User')

@section('toolbar')
    <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
        <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
            Edit User
        </h1>
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
            <li class="breadcrumb-item text-muted">
                <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">Home</a>
            </li>
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-400 w-5px h-2px"></span>
            </li>
            <li class="breadcrumb-item text-muted">
                <a href="{{ route('users.index') }}" class="text-muted text-hover-primary">User Management</a>
            </li>
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-400 w-5px h-2px"></span>
            </li>
            <li class="breadcrumb-item text-muted">Edit User</li>
        </ul>
    </div>
    
    <div class="d-flex align-items-center gap-2 gap-lg-3">
        <a href="{{ route('users.show', $user) }}" class="btn btn-sm btn-light">
            <i class="ki-duotone ki-eye fs-2">
                <span class="path1"></span>
                <span class="path2"></span>
                <span class="path3"></span>
            </i>
            View User
        </a>
        <a href="{{ route('users.index') }}" class="btn btn-sm btn-light">
            <i class="ki-duotone ki-arrow-left fs-2">
                <span class="path1"></span>
                <span class="path2"></span>
            </i>
            Back to Users
        </a>
    </div>
@endsection

@section('content')

<div class="row g-5 g-xl-10">
    <div class="col-xl-8">
        <div class="card card-flush">
            <div class="card-header align-items-center py-5 gap-2 gap-md-5">
                <div class="card-title">
                    <h3 class="fw-bold">Edit User Information</h3>
                </div>
                <div class="card-toolbar">
                    <div class="d-flex align-items-center">
                        @if($user->is_active)
                            <span class="badge badge-light-success me-3">Active</span>
                        @else
                            <span class="badge badge-light-danger me-3">Inactive</span>
                        @endif
                        <span class="text-muted fs-7">User ID: {{ $user->id }}</span>
                    </div>
                </div>
            </div>
            <div class="card-body pt-0">
                <form method="POST" action="{{ route('users.update', $user) }}" id="user-form">
                    @csrf
                    @method('PUT')
                    
                    <div class="row g-5">
                        <!-- Name -->
                        <div class="col-md-6">
                            <label class="form-label required">Full Name</label>
                            <input type="text" name="name" class="form-control form-control-solid @error('name') is-invalid @enderror" 
                                   placeholder="Enter full name" value="{{ old('name', $user->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Email -->
                        <div class="col-md-6">
                            <label class="form-label required">Email Address</label>
                            <input type="email" name="email" class="form-control form-control-solid @error('email') is-invalid @enderror" 
                                   placeholder="Enter email address" value="{{ old('email', $user->email) }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Password -->
                        <div class="col-md-6">
                            <label class="form-label">New Password</label>
                            <div class="position-relative">
                                <input type="password" name="password" class="form-control form-control-solid @error('password') is-invalid @enderror" 
                                       placeholder="Leave blank to keep current password" id="password-input">
                                <button type="button" class="btn btn-sm btn-icon position-absolute translate-middle-y top-50 end-0 me-n2" 
                                        onclick="togglePassword('password-input', this)">
                                    <i class="ki-duotone ki-eye fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                    </i>
                                </button>
                            </div>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Leave blank to keep current password. If changing, must meet security requirements.
                            </div>
                        </div>
                        
                        <!-- Confirm Password -->
                        <div class="col-md-6">
                            <label class="form-label">Confirm New Password</label>
                            <div class="position-relative">
                                <input type="password" name="password_confirmation" class="form-control form-control-solid @error('password_confirmation') is-invalid @enderror" 
                                       placeholder="Confirm new password" id="password-confirm-input">
                                <button type="button" class="btn btn-sm btn-icon position-absolute translate-middle-y top-50 end-0 me-n2" 
                                        onclick="togglePassword('password-confirm-input', this)">
                                    <i class="ki-duotone ki-eye fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                    </i>
                                </button>
                            </div>
                            @error('password_confirmation')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Role -->
                        <div class="col-md-6">
                            <label class="form-label required">Role</label>
                            <select name="role_id" class="form-select form-select-solid @error('role_id') is-invalid @enderror" 
                                    data-control="select2" data-placeholder="Select a role" required>
                                <option value="">Select a role</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" {{ old('role_id', $user->role_id) == $role->id ? 'selected' : '' }}>
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('role_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            @if($user->role)
                                <div class="form-text">
                                    Current role: <strong>{{ $user->role->name }}</strong>
                                </div>
                            @endif
                        </div>
                        
                        <!-- Status -->
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <div class="form-check form-switch form-check-custom form-check-solid mt-3">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" 
                                       id="is_active" {{ old('is_active', $user->is_active) ? 'checked' : '' }}
                                       {{ $user->id === auth()->id() ? 'disabled' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Active User
                                </label>
                            </div>
                            <div class="form-text">
                                @if($user->id === auth()->id())
                                    <span class="text-warning">You cannot deactivate your own account.</span>
                                @else
                                    Inactive users cannot log in to the system.
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <div class="separator my-10"></div>
                    
                    <!-- Account Information -->
                    <div class="row g-5 mb-10">
                        <div class="col-12">
                            <h5 class="fw-bold text-gray-800 mb-5">Account Information</h5>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Created</label>
                            <div class="fw-bold text-gray-800">{{ $user->created_at->format('M d, Y H:i') }}</div>
                            <div class="text-muted fs-7">{{ $user->created_at->diffForHumans() }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Last Updated</label>
                            <div class="fw-bold text-gray-800">{{ $user->updated_at->format('M d, Y H:i') }}</div>
                            <div class="text-muted fs-7">{{ $user->updated_at->diffForHumans() }}</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Workshops Organized</label>
                            <div class="fw-bold text-gray-800">{{ $user->organizedWorkshops->count() }}</div>
                            <div class="text-muted fs-7">workshops</div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-3">
                        <a href="{{ route('users.show', $user) }}" class="btn btn-light">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="ki-duotone ki-check fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            Update User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-xl-4">
        <!-- Quick Actions -->
        <div class="card card-flush mb-5">
            <div class="card-header">
                <div class="card-title">
                    <h3 class="fw-bold">Quick Actions</h3>
                </div>
            </div>
            <div class="card-body">
                <div class="d-flex flex-column gap-3">
                    @if($user->id !== auth()->id())
                        <!-- Toggle Status -->
                        <form method="POST" action="{{ route('users.toggle-status', $user) }}">
                            @csrf
                            <button type="submit" class="btn btn-light-{{ $user->is_active ? 'warning' : 'success' }} w-100">
                                @if($user->is_active)
                                    <i class="ki-duotone ki-cross fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    Deactivate User
                                @else
                                    <i class="ki-duotone ki-check fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    Activate User
                                @endif
                            </button>
                        </form>
                        
                        <!-- Delete User -->
                        @if($user->organizedWorkshops->count() === 0)
                            <form method="POST" action="{{ route('users.destroy', $user) }}" 
                                  onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-light-danger w-100">
                                    <i class="ki-duotone ki-trash fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                        <span class="path4"></span>
                                        <span class="path5"></span>
                                    </i>
                                    Delete User
                                </button>
                            </form>
                        @else
                            <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-4">
                                <i class="ki-duotone ki-information fs-2tx text-warning me-4">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                    <span class="path3"></span>
                                </i>
                                <div class="d-flex flex-stack flex-grow-1">
                                    <div class="fw-semibold">
                                        <div class="fs-7 text-gray-700">
                                            Cannot delete user with {{ $user->organizedWorkshops->count() }} organized workshop(s).
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Role Change -->
        <div class="card card-flush">
            <div class="card-header">
                <div class="card-title">
                    <h3 class="fw-bold">Quick Role Change</h3>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('users.change-role', $user) }}" id="role-change-form">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Change Role</label>
                        <select name="role_id" class="form-select form-select-solid" data-control="select2">
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" {{ $user->role_id == $role->id ? 'selected' : '' }}>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-light-primary w-100">
                        <i class="ki-duotone ki-security-user fs-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        Change Role
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function togglePassword(inputId, button) {
    const input = document.getElementById(inputId);
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'ki-duotone ki-eye-slash fs-2';
        icon.innerHTML = '<span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span>';
    } else {
        input.type = 'password';
        icon.className = 'ki-duotone ki-eye fs-2';
        icon.innerHTML = '<span class="path1"></span><span class="path2"></span><span class="path3"></span>';
    }
}

$(document).ready(function() {
    // Form validation
    $('#user-form').on('submit', function(e) {
        const password = $('#password-input').val();
        const confirmPassword = $('#password-confirm-input').val();
        
        if (password && password !== confirmPassword) {
            e.preventDefault();
            alert('Passwords do not match. Please check and try again.');
            return false;
        }
        
        // Show loading state
        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Updating...');
    });
    
    // Real-time password validation
    $('#password-input, #password-confirm-input').on('input', function() {
        const password = $('#password-input').val();
        const confirmPassword = $('#password-confirm-input').val();
        const confirmInput = $('#password-confirm-input');
        
        if (password && confirmPassword && password !== confirmPassword) {
            confirmInput.addClass('is-invalid');
            if (!confirmInput.next('.invalid-feedback').length) {
                confirmInput.after('<div class="invalid-feedback">Passwords do not match.</div>');
            }
        } else {
            confirmInput.removeClass('is-invalid');
            confirmInput.next('.invalid-feedback').remove();
        }
    });
    
    // Role change form
    $('#role-change-form').on('submit', function(e) {
        const currentRole = '{{ $user->role ? $user->role->name : "No Role" }}';
        const newRoleText = $(this).find('select option:selected').text();
        
        if (currentRole === newRoleText) {
            e.preventDefault();
            alert('User already has this role.');
            return false;
        }
        
        if (!confirm(`Are you sure you want to change the user's role to "${newRoleText}"?`)) {
            e.preventDefault();
            return false;
        }
        
        // Show loading state
        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Changing...');
    });
});
</script>
@endpush

@push('styles')
<style>
.form-label.required::after {
    content: "*";
    color: #f1416c;
    margin-left: 4px;
}

.position-relative .btn-icon {
    border: none;
    background: transparent;
}

.position-relative .btn-icon:hover {
    background: rgba(0, 0, 0, 0.05);
}

@media (max-width: 768px) {
    .col-xl-4 {
        order: -1;
    }
    
    .card-body {
        padding: 1rem;
    }
}
</style>
@endpush