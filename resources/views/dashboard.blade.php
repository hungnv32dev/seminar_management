@extends('layouts.app')

@section('title', 'Dashboard')

@section('toolbar')
    <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
        <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
            Dashboard
        </h1>
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
            <li class="breadcrumb-item text-muted">
                <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">Home</a>
            </li>
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-400 w-5px h-2px"></span>
            </li>
            <li class="breadcrumb-item text-muted">Dashboard</li>
        </ul>
    </div>
@endsection

@section('content')
<div class="row g-5 g-xl-10 mb-5 mb-xl-10">
    
    <!-- Quick Actions -->
    <div class="col-12">
        <div class="card card-flush">
            <div class="card-header align-items-center py-5 gap-2 gap-md-5">
                <div class="card-title">
                    <h3 class="fw-bold">Quick Actions</h3>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="row g-3">
                    <div class="col-md-3">
                        <a href="{{ route('checkin.index') }}" class="btn btn-light-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center py-5">
                            <i class="ki-duotone ki-scan-barcode fs-2x mb-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                                <span class="path4"></span>
                                <span class="path5"></span>
                                <span class="path6"></span>
                            </i>
                            <span class="fw-bold fs-4">Check-in System</span>
                            <span class="text-muted fs-7">Scan QR codes and manage attendance</span>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="{{ route('workshops.index') }}" class="btn btn-light-success w-100 h-100 d-flex flex-column align-items-center justify-content-center py-5">
                            <i class="ki-duotone ki-calendar fs-2x mb-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <span class="fw-bold fs-4">Workshops</span>
                            <span class="text-muted fs-7">Manage workshop events</span>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="{{ route('participants.index') }}" class="btn btn-light-warning w-100 h-100 d-flex flex-column align-items-center justify-content-center py-5">
                            <i class="ki-duotone ki-people fs-2x mb-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                                <span class="path4"></span>
                                <span class="path5"></span>
                            </i>
                            <span class="fw-bold fs-4">Participants</span>
                            <span class="text-muted fs-7">Manage participant registrations</span>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="{{ route('email-templates.index') }}" class="btn btn-light-info w-100 h-100 d-flex flex-column align-items-center justify-content-center py-5">
                            <i class="ki-duotone ki-sms fs-2x mb-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <span class="fw-bold fs-4">Email Templates</span>
                            <span class="text-muted fs-7">Customize email communications</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Welcome Message -->
    <div class="col-12">
        <div class="card card-flush bg-light-primary">
            <div class="card-body text-center py-10">
                <h2 class="fw-bold text-primary mb-5">Welcome to Workshop Management System</h2>
                <p class="text-gray-700 fs-5 mb-8">
                    Manage your workshops, participants, and check-in processes efficiently with our comprehensive system.
                </p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="{{ route('checkin.index') }}" class="btn btn-primary">
                        <i class="ki-duotone ki-scan-barcode fs-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                            <span class="path4"></span>
                            <span class="path5"></span>
                            <span class="path6"></span>
                        </i>
                        Start Check-in
                    </a>
                    <a href="{{ route('workshops.index') }}" class="btn btn-light-primary">
                        <i class="ki-duotone ki-calendar fs-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        View Workshops
                    </a>
                </div>
            </div>
        </div>
    </div>
    
</div>
@endsection