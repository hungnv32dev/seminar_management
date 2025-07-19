@extends('layouts.app')

@section('title', 'User Details')

@section('toolbar')
    <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
        <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
            User Details
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
            <li class="breadcrumb-item text-muted">{{ $user->name }}</li>
        </ul>
    </div>
    
    <div class="d-flex align-items-center gap-2 gap-lg-3">
        <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-primary">
            <i class="ki-duotone ki-pencil fs-2">
                <span class="path1"></span>
                <span class="path2"></span>
            </i>
            Edit User
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
    <!-- User Information -->
    <div class="col-xl-4">
        <div class="card card-flush mb-5">
            <div class="card-header">
                <div class="card-title">
                    <h3 class="fw-bold">User Information</h3>
                </div>
            </div>
            <div class="card-body text-center">
                <!-- Avatar -->
                <div class="symbol symbol-100px symbol-circle mb-7">
                    <div class="symbol-label bg-light-primary text-primary fs-1 fw-bolder">
                        {{ substr($user->name, 0, 1) }}
                    </div>
                </div>
                
                <!-- Name and Status -->
                <div class="mb-5">
                    <h4 class="fw-bold text-gray-800 mb-2">
                        {{ $user->name }}
                        @if($user->id === auth()->id())
                            <span class="badge badge-light-info ms-2">You</span>
                        @endif
                    </h4>
                    <div class="text-muted fs-6 mb-3">{{ $user->email }}</div>
                    
                    @if($user->is_active)
                        <span class="badge badge-light-success fs-7">
                            <i class="ki-duotone ki-check fs-7">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            Active
                        </span>
                    @else
                        <span class="badge badge-light-danger fs-7">
                            <i class="ki-duotone ki-cross fs-7">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            Inactive
                        </span>
                    @endif
                </div>
                
                <!-- Role -->
                <div class="mb-7">
                    <div class="text-muted fs-7 mb-1">Role</div>
                    @if($user->role)
                        <span class="badge badge-light-primary fs-6">{{ $user->role->name }}</span>
                    @else
                        <span class="badge badge-light-secondary fs-6">No Role Assigned</span>
                    @endif
                </div>
                
                <!-- Quick Actions -->
                @if($user->id !== auth()->id())
                    <div class="d-flex flex-column gap-2">
                        <form method="POST" action="{{ route('users.toggle-status', $user) }}">
                            @csrf
                            <button type="submit" class="btn btn-light-{{ $user->is_active ? 'warning' : 'success' }} btn-sm w-100">
                                @if($user->is_active)
                                    <i class="ki-duotone ki-cross fs-4">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    Deactivate
                                @else
                                    <i class="ki-duotone ki-check fs-4">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    Activate
                                @endif
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
        
        <!-- Account Details -->
        <div class="card card-flush">
            <div class="card-header">
                <div class="card-title">
                    <h3 class="fw-bold">Account Details</h3>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-5">
                    <div class="col-12">
                        <div class="d-flex align-items-center mb-3">
                            <i class="ki-duotone ki-calendar fs-2 text-gray-500 me-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <div class="flex-grow-1">
                                <div class="text-gray-800 fw-bold">Created</div>
                                <div class="text-muted fs-7">{{ $user->created_at->format('M d, Y H:i') }}</div>
                                <div class="text-muted fs-8">{{ $user->created_at->diffForHumans() }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <div class="d-flex align-items-center mb-3">
                            <i class="ki-duotone ki-time fs-2 text-gray-500 me-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <div class="flex-grow-1">
                                <div class="text-gray-800 fw-bold">Last Updated</div>
                                <div class="text-muted fs-7">{{ $user->updated_at->format('M d, Y H:i') }}</div>
                                <div class="text-muted fs-8">{{ $user->updated_at->diffForHumans() }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12">
                        <div class="d-flex align-items-center">
                            <i class="ki-duotone ki-profile-user fs-2 text-gray-500 me-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                                <span class="path4"></span>
                            </i>
                            <div class="flex-grow-1">
                                <div class="text-gray-800 fw-bold">User ID</div>
                                <div class="text-muted fs-7">#{{ $user->id }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Statistics and Activity -->
    <div class="col-xl-8">
        <!-- Statistics Cards -->
        <div class="row g-5 mb-5">
            <div class="col-md-3">
                <div class="card card-flush bg-light-primary">
                    <div class="card-body text-center py-5">
                        <div class="fs-2x fw-bold text-primary">{{ $statistics['workshops_organized'] }}</div>
                        <div class="fw-semibold text-gray-600">Total Workshops</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-flush bg-light-success">
                    <div class="card-body text-center py-5">
                        <div class="fs-2x fw-bold text-success">{{ $statistics['active_workshops'] }}</div>
                        <div class="fw-semibold text-gray-600">Active Workshops</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-flush bg-light-info">
                    <div class="card-body text-center py-5">
                        <div class="fs-2x fw-bold text-info">{{ $statistics['completed_workshops'] }}</div>
                        <div class="fw-semibold text-gray-600">Completed</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-flush bg-light-warning">
                    <div class="card-body text-center py-5">
                        <div class="fs-2x fw-bold text-warning">{{ $statistics['total_participants'] }}</div>
                        <div class="fw-semibold text-gray-600">Total Participants</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Organized Workshops -->
        <div class="card card-flush">
            <div class="card-header align-items-center py-5 gap-2 gap-md-5">
                <div class="card-title">
                    <h3 class="fw-bold">Organized Workshops</h3>
                </div>
                <div class="card-toolbar">
                    <span class="badge badge-light-primary">{{ $user->organizedWorkshops->count() }} workshops</span>
                </div>
            </div>
            <div class="card-body pt-0">
                @if($user->organizedWorkshops->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
                            <thead>
                                <tr class="fw-bold text-muted">
                                    <th class="min-w-200px">Workshop</th>
                                    <th class="min-w-100px">Date</th>
                                    <th class="min-w-80px">Status</th>
                                    <th class="min-w-80px">Participants</th>
                                    <th class="min-w-100px text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($user->organizedWorkshops->take(10) as $workshop)
                                <tr>
                                    <td>
                                        <div class="d-flex justify-content-start flex-column">
                                            <span class="text-dark fw-bold text-hover-primary fs-6">{{ $workshop->name }}</span>
                                            <span class="text-muted fw-semibold d-block fs-7">{{ $workshop->location }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-dark fw-bold d-block fs-6">{{ $workshop->date_time->format('M d, Y') }}</span>
                                        <span class="text-muted fw-semibold d-block fs-7">{{ $workshop->date_time->format('H:i') }}</span>
                                    </td>
                                    <td>
                                        @php
                                            $statusColors = [
                                                'draft' => 'secondary',
                                                'published' => 'primary',
                                                'ongoing' => 'success',
                                                'completed' => 'info',
                                                'cancelled' => 'danger'
                                            ];
                                        @endphp
                                        <span class="badge badge-light-{{ $statusColors[$workshop->status] ?? 'secondary' }}">
                                            {{ ucfirst($workshop->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-dark fw-bold fs-6">{{ $workshop->participants->count() }}</span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('workshops.show', $workshop) }}" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm" title="View Workshop">
                                            <i class="ki-duotone ki-eye fs-3">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                                <span class="path3"></span>
                                            </i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    @if($user->organizedWorkshops->count() > 10)
                        <div class="text-center mt-5">
                            <a href="{{ route('workshops.index', ['organizer' => $user->id]) }}" class="btn btn-light-primary">
                                View All {{ $user->organizedWorkshops->count() }} Workshops
                            </a>
                        </div>
                    @endif
                @else
                    <div class="text-center py-10">
                        <i class="ki-duotone ki-calendar fs-3x text-gray-400 mb-5">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        <h4 class="text-gray-600 fw-bold mb-3">No Workshops Organized</h4>
                        <p class="text-gray-500 mb-0">
                            This user hasn't organized any workshops yet.
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
.symbol-100px {
    width: 100px;
    height: 100px;
}

.symbol-100px .symbol-label {
    font-size: 2.5rem;
}

@media (max-width: 768px) {
    .col-xl-4 {
        order: -1;
    }
    
    .row.g-5 .col-md-3 {
        margin-bottom: 1rem;
    }
    
    .fs-2x {
        font-size: 1.5rem !important;
    }
}
</style>
@endpush