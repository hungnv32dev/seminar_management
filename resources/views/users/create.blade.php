@extends('layouts.app')

@section('title', 'Create User')

@section('toolbar')
    <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
        <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
            Create User
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
            <li class="breadcrumb-item text-muted">Create User</li>
        </ul>
    </div>
    
    <div class="d-flex align-items-center gap-2 gap-lg-3">
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
                    <h3 class="fw-bold">User Information</h3>
                </div>
            </div>
            <div class="card-body pt-0">
                <form method="POST" action="{{ route('users.store') }}" id="user-form">
                    @csrf
                    
                    <div class="row g-5">
                        <!-- Name -->
                        <div class="col-md-6">
                            <label class="form-label required">Full Name</label>
                            <input type="text" name="name" class="form-control form-control-solid @error('name') is-invalid @enderror" 
                                   placeholder="Enter full name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Email -->
                        <div class="col-md-6">
                            <label class="form-label required">Email Address</label>
                            <input type="email" name="email" class="form-control form-control-solid @error('email') is-invalid @enderror" 
                                   placeholder="Enter email address" value="{{ old('email') }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Password -->
                        <div class="col-md-6">
                            <label class="form-label required">Password</label>
                            <div class="position-relative">
                                <input type="password" name="password" class="form-control form-control-solid @error('password') is-invalid @enderror" 
                                       placeholder="Enter password" required id="password-input">
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
                                Password must be at least 8 characters with uppercase, lowercase, numbers, and symbols.
                            </div>
                        </div>
                        
                        <!-- Confirm Password -->
                        <div class="col-md-6">
                            <label class="form-label required">Confirm Password</label>
                            <div class="position-relative">
                                <input type="password" name="password_confirmation" class="form-control form-control-solid @error('password_confirmation') is-invalid @enderror" 
                                       placeholder="Confirm password" required id="password-confirm-input">
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
                                    <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('role_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Status -->
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <div class="form-check form-switch form-check-custom form-check-solid mt-3">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" 
                                       id="is_active" {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Active User
                                </label>
                            </div>
                            <div class="form-text">
                                Inactive users cannot log in to the system.
                            </div>
                        </div>
                    </div>
                    
                    <div class="separator my-10"></div>
                    
                    <div class="d-flex justify-content-end gap-3">
                        <a href="{{ route('users.index') }}" class="btn btn-light">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="ki-duotone ki-check fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            Create User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-xl-4">
        <!-- Help Card -->
        <div class="card card-flush">
            <div class="card-header">
                <div class="card-title">
                    <h3 class="fw-bold">User Creation Guidelines</h3>
                </div>
            </div>
            <div class="card-body">
                <div class="mb-5">
                    <h6 class="fw-semibold text-gray-600 mb-2">Password Requirements</h6>
                    <ul class="list-unstyled text-gray-700 fs-7">
                        <li class="d-flex align-items-center mb-2">
                            <i class="ki-duotone ki-check fs-6 text-success me-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            At least 8 characters long
                        </li>
                        <li class="d-flex align-items-center mb-2">
                            <i class="ki-duotone ki-check fs-6 text-success me-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            Contains uppercase letters
                        </li>
                        <li class="d-flex align-items-center mb-2">
                            <i class="ki-duotone ki-check fs-6 text-success me-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            Contains lowercase letters
                        </li>
                        <li class="d-flex align-items-center mb-2">
                            <i class="ki-duotone ki-check fs-6 text-success me-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            Contains numbers
                        </li>
                        <li class="d-flex align-items-center">
                            <i class="ki-duotone ki-check fs-6 text-success me-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            Contains special characters
                        </li>
                    </ul>
                </div>
                
                <div class="mb-5">
                    <h6 class="fw-semibold text-gray-600 mb-2">Role Information</h6>
                    <p class="text-gray-700 fs-7 mb-3">
                        Each user must be assigned a role that determines their permissions and access levels within the system.
                    </p>
                    @if($roles->count() > 0)
                        <div class="text-gray-700 fs-7">
                            <strong>Available Roles:</strong>
                            <ul class="mt-2">
                                @foreach($roles as $role)
                                    <li>{{ $role->name }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
                
                <div class="notice d-flex bg-light-warning rounded border-warning border border-dashed p-6">
                    <i class="ki-duotone ki-information fs-2tx text-warning me-4">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                    </i>
                    <div class="d-flex flex-stack flex-grow-1">
                        <div class="fw-semibold">
                            <div class="fs-6 text-gray-700">
                                <strong>Note:</strong> New users will receive login credentials via email. 
                                Make sure the email address is correct and accessible.
                            </div>
                        </div>
                    </div>
                </div>
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
        
        if (password !== confirmPassword) {
            e.preventDefault();
            alert('Passwords do not match. Please check and try again.');
            return false;
        }
        
        // Show loading state
        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Creating...');
    });
    
    // Real-time password validation
    $('#password-input, #password-confirm-input').on('input', function() {
        const password = $('#password-input').val();
        const confirmPassword = $('#password-confirm-input').val();
        const confirmInput = $('#password-confirm-input');
        
        if (confirmPassword && password !== confirmPassword) {
            confirmInput.addClass('is-invalid');
            if (!confirmInput.next('.invalid-feedback').length) {
                confirmInput.after('<div class="invalid-feedback">Passwords do not match.</div>');
            }
        } else {
            confirmInput.removeClass('is-invalid');
            confirmInput.next('.invalid-feedback').remove();
        }
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