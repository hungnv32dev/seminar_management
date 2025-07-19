@extends('layouts.app')

@section('title', 'Check-in System')

@section('toolbar')
    <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
        <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
            Check-in System
        </h1>
        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
            <li class="breadcrumb-item text-muted">
                <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">Home</a>
            </li>
            <li class="breadcrumb-item">
                <span class="bullet bg-gray-400 w-5px h-2px"></span>
            </li>
            <li class="breadcrumb-item text-muted">Check-in</li>
        </ul>
    </div>
@endsection

@section('content')
<div class="row g-5 g-xl-10 mb-5 mb-xl-10">
    
    <!-- Workshop Selection -->
    <div class="col-12">
        <div class="card card-flush">
            <div class="card-header align-items-center py-5 gap-2 gap-md-5">
                <div class="card-title">
                    <h3 class="fw-bold">Workshop Selection</h3>
                </div>
            </div>
            <div class="card-body pt-0">
                <form method="GET" action="{{ route('checkin.index') }}" class="d-flex flex-column flex-md-row gap-3">
                    <div class="flex-grow-1">
                        <select name="workshop_id" class="form-select form-select-solid" data-control="select2" data-placeholder="Select a workshop" onchange="this.form.submit()">
                            <option value="">All Workshops</option>
                            @foreach($workshops as $workshop)
                                <option value="{{ $workshop->id }}" {{ request('workshop_id') == $workshop->id ? 'selected' : '' }}>
                                    {{ $workshop->name }} - {{ $workshop->date_time->format('M d, Y H:i') }}
                                    ({{ $workshop->participants->count() }} participants)
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('checkin.mobile') }}{{ request('workshop_id') ? '?workshop_id=' . request('workshop_id') : '' }}" class="btn btn-light-success">
                            <i class="ki-duotone ki-phone fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            Mobile View
                        </a>
                        <button type="button" class="btn btn-light-primary" onclick="location.reload()">
                            <i class="ki-duotone ki-arrows-circle fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            Refresh
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- QR Code Scanner -->
    <div class="col-xl-6">
        <div class="card card-flush h-xl-100">
            <div class="card-header align-items-center py-5 gap-2 gap-md-5">
                <div class="card-title">
                    <h3 class="fw-bold">QR Code Scanner</h3>
                </div>
                <div class="card-toolbar">
                    <span class="badge badge-light-success" id="scanner-status">Ready</span>
                </div>
            </div>
            <div class="card-body pt-0">
                
                <!-- Manual Input -->
                <div class="mb-8">
                    <label class="form-label fw-semibold">Manual Ticket Code Entry</label>
                    <div class="input-group">
                        <input type="text" class="form-control form-control-solid" id="manual-ticket-code" placeholder="Enter ticket code or scan QR code" autofocus>
                        <button class="btn btn-primary" type="button" id="manual-checkin-btn">
                            <i class="ki-duotone ki-check fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            Check In
                        </button>
                    </div>
                    <div class="form-text">You can manually type the ticket code or use a QR code scanner</div>
                </div>
                
                <!-- Camera Scanner (Future Enhancement) -->
                <div class="text-center p-10 bg-light-primary rounded">
                    <i class="ki-duotone ki-scan-barcode fs-3x text-primary mb-5">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                        <span class="path4"></span>
                        <span class="path5"></span>
                        <span class="path6"></span>
                    </i>
                    <h4 class="text-gray-800 fw-bold mb-3">QR Code Scanner</h4>
                    <p class="text-gray-600 mb-0">
                        Use the manual input above or connect a QR code scanner device.<br>
                        Camera scanning can be added in future updates.
                    </p>
                </div>
                
            </div>
        </div>
    </div>
    
    <!-- Participant Information -->
    <div class="col-xl-6">
        <div class="card card-flush h-xl-100">
            <div class="card-header align-items-center py-5 gap-2 gap-md-5">
                <div class="card-title">
                    <h3 class="fw-bold">Participant Information</h3>
                </div>
            </div>
            <div class="card-body pt-0">
                
                <!-- Default State -->
                <div id="participant-default" class="text-center p-10">
                    <i class="ki-duotone ki-user fs-3x text-gray-400 mb-5">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <h4 class="text-gray-600 fw-bold mb-3">No Participant Selected</h4>
                    <p class="text-gray-500 mb-0">Scan a QR code or enter a ticket code to view participant information</p>
                </div>
                
                <!-- Participant Details -->
                <div id="participant-details" class="d-none">
                    <div class="d-flex flex-column">
                        
                        <!-- Status Badge -->
                        <div class="mb-5">
                            <span id="checkin-status-badge" class="badge badge-light-warning fs-7">Not Checked In</span>
                            <span id="payment-status-badge" class="badge badge-light-info fs-7 ms-2">Payment Status</span>
                        </div>
                        
                        <!-- Participant Info -->
                        <div class="mb-5">
                            <h4 id="participant-name" class="fw-bold text-gray-800 mb-2">Participant Name</h4>
                            <div class="text-gray-600 mb-1">
                                <i class="ki-duotone ki-sms fs-6 me-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <span id="participant-email">email@example.com</span>
                            </div>
                            <div class="text-gray-600 mb-1">
                                <i class="ki-duotone ki-phone fs-6 me-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <span id="participant-phone">+1234567890</span>
                            </div>
                            <div class="text-gray-600">
                                <i class="ki-duotone ki-office-bag fs-6 me-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <span id="participant-company">Company Name</span>
                                <span id="participant-position" class="text-muted"> - Position</span>
                            </div>
                        </div>
                        
                        <!-- Workshop Info -->
                        <div class="mb-5">
                            <h6 class="fw-semibold text-gray-600 mb-2">Workshop Details</h6>
                            <div class="text-gray-800 mb-1">
                                <i class="ki-duotone ki-calendar fs-6 me-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <span id="participant-workshop">Workshop Name</span>
                            </div>
                            <div class="text-gray-600">
                                <i class="ki-duotone ki-ticket fs-6 me-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <span id="participant-ticket-type">Ticket Type</span>
                            </div>
                        </div>
                        
                        <!-- Ticket Code -->
                        <div class="mb-5">
                            <h6 class="fw-semibold text-gray-600 mb-2">Ticket Code</h6>
                            <div class="bg-light-primary p-3 rounded">
                                <code id="participant-ticket-code" class="fs-4 fw-bold text-primary">TICKET123</code>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="d-flex gap-3">
                            <button type="button" class="btn btn-success flex-grow-1" id="confirm-checkin-btn">
                                <i class="ki-duotone ki-check fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                Confirm Check-in
                            </button>
                            <button type="button" class="btn btn-light-danger" id="undo-checkin-btn" style="display: none;">
                                <i class="ki-duotone ki-cross fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                Undo
                            </button>
                        </div>
                        
                    </div>
                </div>
                
                <!-- Loading State -->
                <div id="participant-loading" class="text-center p-10 d-none">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h4 class="text-gray-600 fw-bold mt-5 mb-3">Processing...</h4>
                    <p class="text-gray-500 mb-0">Please wait while we process the check-in</p>
                </div>
                
            </div>
        </div>
    </div>
    
</div>

@if($selectedWorkshop)
<!-- Workshop Statistics -->
<div class="row g-5 g-xl-10 mb-5 mb-xl-10">
    <div class="col-12">
        <div class="card card-flush">
            <div class="card-header align-items-center py-5 gap-2 gap-md-5">
                <div class="card-title">
                    <h3 class="fw-bold">{{ $selectedWorkshop->name }} - Statistics</h3>
                </div>
                <div class="card-toolbar">
                    <button type="button" class="btn btn-light-primary btn-sm" onclick="refreshStatistics()">
                        <i class="ki-duotone ki-arrows-circle fs-4">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        Refresh
                    </button>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="row g-5" id="workshop-statistics">
                    <!-- Statistics will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Participants List -->
<div class="row g-5 g-xl-10">
    <div class="col-12">
        <div class="card card-flush">
            <div class="card-header align-items-center py-5 gap-2 gap-md-5">
                <div class="card-title">
                    <h3 class="fw-bold">Participants</h3>
                </div>
                <div class="card-toolbar">
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-light-success btn-sm" id="bulk-checkin-btn" disabled>
                            <i class="ki-duotone ki-check-square fs-4">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                            </i>
                            Bulk Check-in
                        </button>
                        <a href="{{ route('checkin.export', $selectedWorkshop->id) }}" class="btn btn-light-primary btn-sm">
                            <i class="ki-duotone ki-exit-down fs-4">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            Export Report
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4" id="participants-table">
                        <thead>
                            <tr class="fw-bold text-muted">
                                <th class="w-25px">
                                    <div class="form-check form-check-sm form-check-custom form-check-solid">
                                        <input class="form-check-input" type="checkbox" id="select-all">
                                    </div>
                                </th>
                                <th class="min-w-150px">Name</th>
                                <th class="min-w-140px">Email</th>
                                <th class="min-w-120px">Phone</th>
                                <th class="min-w-120px">Company</th>
                                <th class="min-w-100px">Ticket Type</th>
                                <th class="min-w-100px">Payment</th>
                                <th class="min-w-100px">Status</th>
                                <th class="min-w-100px text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($selectedWorkshop->participants as $participant)
                            <tr data-participant-id="{{ $participant->id }}">
                                <td>
                                    <div class="form-check form-check-sm form-check-custom form-check-solid">
                                        <input class="form-check-input participant-checkbox" type="checkbox" value="{{ $participant->id }}">
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="symbol symbol-45px me-5">
                                            <div class="symbol-label bg-light-primary text-primary fs-6 fw-bolder">
                                                {{ substr($participant->name, 0, 1) }}
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-start flex-column">
                                            <span class="text-dark fw-bold text-hover-primary fs-6">{{ $participant->name }}</span>
                                            <span class="text-muted fw-semibold text-muted d-block fs-7">{{ $participant->position }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-dark fw-bold d-block fs-6">{{ $participant->email }}</span>
                                </td>
                                <td>
                                    <span class="text-dark fw-bold d-block fs-6">{{ $participant->phone }}</span>
                                </td>
                                <td>
                                    <span class="text-dark fw-bold d-block fs-6">{{ $participant->company }}</span>
                                </td>
                                <td>
                                    <span class="badge badge-light-info">{{ $participant->ticketType->name }}</span>
                                </td>
                                <td>
                                    @if($participant->is_paid)
                                        <span class="badge badge-light-success">Paid</span>
                                    @else
                                        <span class="badge badge-light-warning">Unpaid</span>
                                    @endif
                                </td>
                                <td>
                                    @if($participant->is_checked_in)
                                        <span class="badge badge-light-success">
                                            <i class="ki-duotone ki-check fs-7">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                            Checked In
                                        </span>
                                    @else
                                        <span class="badge badge-light-danger">
                                            <i class="ki-duotone ki-cross fs-7">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                            Not Checked In
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end flex-shrink-0">
                                        @if($participant->is_checked_in)
                                            <form method="POST" action="{{ route('checkin.undo', $participant->id) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-icon btn-bg-light btn-active-color-danger btn-sm me-1" title="Undo Check-in">
                                                    <i class="ki-duotone ki-cross fs-3">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>
                                                </button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('checkin.manual', $participant->id) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-icon btn-bg-light btn-active-color-success btn-sm me-1" title="Manual Check-in">
                                                    <i class="ki-duotone ki-check fs-3">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>
                                                </button>
                                            </form>
                                        @endif
                                        <button type="button" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm" 
                                                onclick="showParticipantDetails('{{ $participant->ticket_code }}')" title="View Details">
                                            <i class="ki-duotone ki-eye fs-3">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                                <span class="path3"></span>
                                            </i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
let currentParticipantId = null;
let selectedWorkshopId = {{ request('workshop_id') ?? 'null' }};

$(document).ready(function() {
    // Initialize DataTable if participants exist
    @if($selectedWorkshop && $selectedWorkshop->participants->count() > 0)
    $('#participants-table').DataTable({
        responsive: true,
        pageLength: 25,
        order: [[7, 'asc'], [1, 'asc']], // Sort by status (not checked in first), then by name
        columnDefs: [
            { orderable: false, targets: [0, 8] } // Disable sorting for checkbox and actions columns
        ]
    });
    @endif
    
    // Load workshop statistics if workshop is selected
    if (selectedWorkshopId) {
        loadWorkshopStatistics();
    }
    
    // Manual check-in button
    $('#manual-checkin-btn').click(function() {
        const ticketCode = $('#manual-ticket-code').val().trim();
        if (ticketCode) {
            processCheckIn(ticketCode);
        } else {
            showToast('Please enter a ticket code', 'warning');
        }
    });
    
    // Enter key on manual input
    $('#manual-ticket-code').keypress(function(e) {
        if (e.which === 13) { // Enter key
            $('#manual-checkin-btn').click();
        }
    });
    
    // Confirm check-in button
    $('#confirm-checkin-btn').click(function() {
        const ticketCode = $('#participant-ticket-code').text();
        if (ticketCode && currentParticipantId) {
            confirmCheckIn(ticketCode);
        }
    });
    
    // Undo check-in button
    $('#undo-checkin-btn').click(function() {
        if (currentParticipantId) {
            undoCheckIn(currentParticipantId);
        }
    });
    
    // Select all checkbox
    $('#select-all').change(function() {
        $('.participant-checkbox').prop('checked', this.checked);
        updateBulkCheckInButton();
    });
    
    // Individual checkboxes
    $(document).on('change', '.participant-checkbox', function() {
        updateBulkCheckInButton();
        
        // Update select all checkbox
        const totalCheckboxes = $('.participant-checkbox').length;
        const checkedCheckboxes = $('.participant-checkbox:checked').length;
        $('#select-all').prop('checked', totalCheckboxes === checkedCheckboxes);
    });
    
    // Bulk check-in button
    $('#bulk-checkin-btn').click(function() {
        const selectedIds = $('.participant-checkbox:checked').map(function() {
            return this.value;
        }).get();
        
        if (selectedIds.length > 0) {
            bulkCheckIn(selectedIds);
        }
    });
    
    // Auto-focus on manual input
    $('#manual-ticket-code').focus();
});

function processCheckIn(ticketCode) {
    showLoading();
    
    $.ajax({
        url: '{{ route("checkin.participant") }}',
        method: 'GET',
        data: { 
            ticket_code: ticketCode 
        },
        success: function(response) {
            if (response.success) {
                showParticipantInfo(response.participant);
                $('#manual-ticket-code').val('').focus();
            } else {
                hideLoading();
                showToast(response.error, 'error');
            }
        },
        error: function(xhr) {
            hideLoading();
            const response = xhr.responseJSON;
            showToast(response?.error || 'Failed to process check-in', 'error');
        }
    });
}

function confirmCheckIn(ticketCode) {
    showLoading();
    
    $.ajax({
        url: '{{ route("checkin.scan") }}',
        method: 'POST',
        data: {
            ticket_code: ticketCode,
            workshop_id: selectedWorkshopId,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            hideLoading();
            if (response.success) {
                showToast('Check-in successful!', 'success');
                showParticipantInfo(response.participant);
                updateParticipantRow(response.participant);
                loadWorkshopStatistics();
            } else {
                showToast(response.error, response.error_type === 'already_checked_in' ? 'warning' : 'error');
                if (response.participant) {
                    showParticipantInfo(response.participant);
                }
            }
        },
        error: function(xhr) {
            hideLoading();
            const response = xhr.responseJSON;
            showToast(response?.error || 'Check-in failed', 'error');
        }
    });
}

function undoCheckIn(participantId) {
    if (!confirm('Are you sure you want to undo this check-in?')) {
        return;
    }
    
    showLoading();
    
    $.ajax({
        url: `/checkin/undo/${participantId}`,
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            hideLoading();
            if (response.success) {
                showToast('Check-in undone successfully', 'success');
                location.reload(); // Refresh to update the table
            } else {
                showToast(response.error, 'error');
            }
        },
        error: function(xhr) {
            hideLoading();
            showToast('Failed to undo check-in', 'error');
        }
    });
}

function showParticipantDetails(ticketCode) {
    processCheckIn(ticketCode);
}

function showParticipantInfo(participant) {
    currentParticipantId = participant.id;
    
    // Update participant details
    $('#participant-name').text(participant.name);
    $('#participant-email').text(participant.email);
    $('#participant-phone').text(participant.phone);
    $('#participant-company').text(participant.company || 'N/A');
    $('#participant-position').text(participant.position ? ' - ' + participant.position : '');
    $('#participant-workshop').text(participant.workshop);
    $('#participant-ticket-type').text(participant.ticket_type);
    $('#participant-ticket-code').text(participant.ticket_code);
    
    // Update status badges
    if (participant.is_checked_in) {
        $('#checkin-status-badge').removeClass('badge-light-warning badge-light-danger')
                                  .addClass('badge-light-success')
                                  .text('Checked In');
        $('#confirm-checkin-btn').hide();
        $('#undo-checkin-btn').show();
    } else {
        $('#checkin-status-badge').removeClass('badge-light-success badge-light-danger')
                                  .addClass('badge-light-warning')
                                  .text('Not Checked In');
        $('#confirm-checkin-btn').show();
        $('#undo-checkin-btn').hide();
    }
    
    if (participant.is_paid) {
        $('#payment-status-badge').removeClass('badge-light-warning')
                                  .addClass('badge-light-success')
                                  .text('Paid');
    } else {
        $('#payment-status-badge').removeClass('badge-light-success')
                                  .addClass('badge-light-warning')
                                  .text('Unpaid');
    }
    
    // Show participant details
    $('#participant-default').addClass('d-none');
    $('#participant-loading').addClass('d-none');
    $('#participant-details').removeClass('d-none');
}

function showLoading() {
    $('#participant-default').addClass('d-none');
    $('#participant-details').addClass('d-none');
    $('#participant-loading').removeClass('d-none');
}

function hideLoading() {
    $('#participant-loading').addClass('d-none');
    if (currentParticipantId) {
        $('#participant-details').removeClass('d-none');
    } else {
        $('#participant-default').removeClass('d-none');
    }
}

function updateParticipantRow(participant) {
    const row = $(`tr[data-participant-id="${participant.id}"]`);
    if (row.length) {
        // Update status badge
        const statusCell = row.find('td:nth-child(8)');
        if (participant.is_checked_in) {
            statusCell.html(`
                <span class="badge badge-light-success">
                    <i class="ki-duotone ki-check fs-7">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    Checked In
                </span>
            `);
            
            // Update action buttons
            const actionCell = row.find('td:nth-child(9)');
            actionCell.html(`
                <div class="d-flex justify-content-end flex-shrink-0">
                    <form method="POST" action="/checkin/undo/${participant.id}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-icon btn-bg-light btn-active-color-danger btn-sm me-1" title="Undo Check-in">
                            <i class="ki-duotone ki-cross fs-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                        </button>
                    </form>
                    <button type="button" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm" 
                            onclick="showParticipantDetails('${participant.ticket_code}')" title="View Details">
                        <i class="ki-duotone ki-eye fs-3">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                        </i>
                    </button>
                </div>
            `);
        }
    }
}

function loadWorkshopStatistics() {
    if (!selectedWorkshopId) return;
    
    $.ajax({
        url: `/checkin/statistics/${selectedWorkshopId}`,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                displayStatistics(response.statistics);
            }
        },
        error: function() {
            console.error('Failed to load statistics');
        }
    });
}

function displayStatistics(stats) {
    const statisticsHtml = `
        <div class="col-md-3">
            <div class="card card-flush bg-light-primary">
                <div class="card-body text-center py-5">
                    <div class="fs-2x fw-bold text-primary">${stats.total_participants}</div>
                    <div class="fw-semibold text-gray-600">Total Participants</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush bg-light-success">
                <div class="card-body text-center py-5">
                    <div class="fs-2x fw-bold text-success">${stats.checked_in}</div>
                    <div class="fw-semibold text-gray-600">Checked In</div>
                    <div class="text-muted fs-7">${stats.checkin_percentage}%</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush bg-light-warning">
                <div class="card-body text-center py-5">
                    <div class="fs-2x fw-bold text-warning">${stats.not_checked_in}</div>
                    <div class="fw-semibold text-gray-600">Not Checked In</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-flush bg-light-info">
                <div class="card-body text-center py-5">
                    <div class="fs-2x fw-bold text-info">${stats.paid_participants}</div>
                    <div class="fw-semibold text-gray-600">Paid</div>
                    <div class="text-muted fs-7">${stats.payment_percentage}%</div>
                </div>
            </div>
        </div>
    `;
    
    $('#workshop-statistics').html(statisticsHtml);
}

function refreshStatistics() {
    loadWorkshopStatistics();
    showToast('Statistics refreshed', 'info');
}

function updateBulkCheckInButton() {
    const checkedCount = $('.participant-checkbox:checked').length;
    const button = $('#bulk-checkin-btn');
    
    if (checkedCount > 0) {
        button.prop('disabled', false).text(`Bulk Check-in (${checkedCount})`);
    } else {
        button.prop('disabled', true).text('Bulk Check-in');
    }
}

function bulkCheckIn(participantIds) {
    if (!confirm(`Are you sure you want to check in ${participantIds.length} participant(s)?`)) {
        return;
    }
    
    $.ajax({
        url: '{{ route("checkin.bulk") }}',
        method: 'POST',
        data: {
            participant_ids: participantIds,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                showToast('Bulk check-in completed', 'success');
                location.reload(); // Refresh to update the table
            } else {
                showToast(response.error, 'error');
            }
        },
        error: function() {
            showToast('Bulk check-in failed', 'error');
        }
    });
}

function showToast(message, type = 'info') {
    // Using Metronic's toast notification
    const toastClass = {
        'success': 'alert-success',
        'error': 'alert-danger',
        'warning': 'alert-warning',
        'info': 'alert-info'
    };
    
    const toast = $(`
        <div class="alert ${toastClass[type]} alert-dismissible fade show position-fixed" 
             style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `);
    
    $('body').append(toast);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        toast.alert('close');
    }, 5000);
}
</script>
@endpush

@push('styles')
<style>
.scanner-input {
    font-size: 1.2rem;
    padding: 1rem;
}

.participant-info-card {
    border-left: 4px solid #009ef7;
}

.status-badge {
    font-size: 0.875rem;
    padding: 0.5rem 1rem;
}

@media (max-width: 768px) {
    .card-body {
        padding: 1rem;
    }
    
    .btn {
        font-size: 0.875rem;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
}
</style>
@endpush