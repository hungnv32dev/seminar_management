<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>Check-in - {{ config('app.name', 'Workshop Management') }}</title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ asset('demo1/assets/media/logos/favicon.ico') }}" />
    
    <!-- Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
    
    <!-- Bootstrap CSS -->
    <link href="{{ asset('demo1/assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('demo1/assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
    
    <style>
        body {
            background: #f5f8fa;
            font-size: 16px;
        }
        
        .mobile-header {
            background: #009ef7;
            color: white;
            padding: 1rem;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .mobile-container {
            padding: 1rem;
            max-width: 100%;
        }
        
        .scanner-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .scanner-input {
            font-size: 18px;
            padding: 1rem;
            border-radius: 10px;
            border: 2px solid #e4e6ef;
            width: 100%;
            margin-bottom: 1rem;
        }
        
        .scanner-input:focus {
            border-color: #009ef7;
            box-shadow: 0 0 0 0.2rem rgba(0, 158, 247, 0.25);
        }
        
        .btn-scan {
            background: #009ef7;
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            width: 100%;
            margin-bottom: 1rem;
        }
        
        .btn-scan:hover {
            background: #0084d4;
            color: white;
        }
        
        .participant-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            border-left: 5px solid #009ef7;
        }
        
        .participant-name {
            font-size: 20px;
            font-weight: 700;
            color: #181c32;
            margin-bottom: 0.5rem;
        }
        
        .participant-info {
            color: #7e8299;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
        }
        
        .participant-info i {
            margin-right: 0.5rem;
            width: 20px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin: 0.25rem 0.5rem 0.25rem 0;
        }
        
        .status-success {
            background: #e8fff3;
            color: #0bb783;
        }
        
        .status-warning {
            background: #fff8dd;
            color: #f1bc00;
        }
        
        .status-danger {
            background: #ffe2e5;
            color: #f1416c;
        }
        
        .btn-action {
            padding: 1rem 2rem;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            border: none;
            margin: 0.5rem 0;
            width: 100%;
        }
        
        .btn-success {
            background: #50cd89;
            color: white;
        }
        
        .btn-danger {
            background: #f1416c;
            color: white;
        }
        
        .workshop-selector {
            background: white;
            border-radius: 15px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .workshop-selector select {
            font-size: 16px;
            padding: 0.75rem;
            border-radius: 8px;
            border: 2px solid #e4e6ef;
            width: 100%;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            font-size: 12px;
            color: #7e8299;
            font-weight: 600;
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        
        .loading-content {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
        }
        
        .toast {
            position: fixed;
            top: 80px;
            left: 1rem;
            right: 1rem;
            z-index: 9999;
            padding: 1rem;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            transform: translateY(-100px);
            transition: transform 0.3s ease;
        }
        
        .toast.show {
            transform: translateY(0);
        }
        
        .toast-success {
            background: #50cd89;
        }
        
        .toast-error {
            background: #f1416c;
        }
        
        .toast-warning {
            background: #f1bc00;
        }
        
        .hidden {
            display: none !important;
        }
        
        @media (max-width: 480px) {
            .mobile-container {
                padding: 0.5rem;
            }
            
            .scanner-card, .participant-card, .workshop-selector {
                padding: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    
    <!-- Mobile Header -->
    <div class="mobile-header">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h4 class="mb-0">Check-in System</h4>
                <small class="opacity-75">Workshop Management</small>
            </div>
            <div>
                <button type="button" class="btn btn-sm btn-light-primary" onclick="location.reload()">
                    <i class="ki-duotone ki-arrows-circle fs-4">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Mobile Container -->
    <div class="mobile-container">
        
        <!-- Workshop Selection -->
        <div class="workshop-selector">
            <label class="form-label fw-bold mb-2">Select Workshop</label>
            <select id="workshop-selector" class="form-select" onchange="selectWorkshop(this.value)">
                <option value="">All Workshops</option>
                @foreach($workshops as $workshop)
                    <option value="{{ $workshop->id }}" {{ request('workshop_id') == $workshop->id ? 'selected' : '' }}>
                        {{ $workshop->name }} ({{ $workshop->participants->count() }})
                    </option>
                @endforeach
            </select>
        </div>
        
        @if($selectedWorkshop)
        <!-- Statistics -->
        <div class="stats-grid" id="stats-container">
            <!-- Stats will be loaded here -->
        </div>
        @endif
        
        <!-- QR Scanner -->
        <div class="scanner-card">
            <h5 class="fw-bold mb-3">
                <i class="ki-duotone ki-scan-barcode fs-2 me-2">
                    <span class="path1"></span>
                    <span class="path2"></span>
                    <span class="path3"></span>
                    <span class="path4"></span>
                    <span class="path5"></span>
                    <span class="path6"></span>
                </i>
                Scan QR Code
            </h5>
            <input type="text" 
                   id="ticket-input" 
                   class="scanner-input" 
                   placeholder="Scan QR code or enter ticket code"
                   autocomplete="off"
                   autofocus>
            <button type="button" class="btn-scan" onclick="processTicket()">
                <i class="ki-duotone ki-check fs-2 me-2">
                    <span class="path1"></span>
                    <span class="path2"></span>
                </i>
                Check In
            </button>
            <div class="text-center">
                <small class="text-muted">Point camera at QR code or type ticket code manually</small>
            </div>
        </div>
        
        <!-- Participant Information -->
        <div id="participant-info" class="hidden">
            <div class="participant-card">
                <div class="participant-name" id="participant-name">Participant Name</div>
                
                <div class="mb-3">
                    <span id="checkin-badge" class="status-badge status-warning">Not Checked In</span>
                    <span id="payment-badge" class="status-badge status-warning">Unpaid</span>
                </div>
                
                <div class="participant-info">
                    <i class="ki-duotone ki-sms fs-5">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <span id="participant-email">email@example.com</span>
                </div>
                
                <div class="participant-info">
                    <i class="ki-duotone ki-phone fs-5">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <span id="participant-phone">+1234567890</span>
                </div>
                
                <div class="participant-info">
                    <i class="ki-duotone ki-office-bag fs-5">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <span id="participant-company">Company Name</span>
                </div>
                
                <div class="participant-info">
                    <i class="ki-duotone ki-calendar fs-5">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <span id="participant-workshop">Workshop Name</span>
                </div>
                
                <div class="participant-info mb-3">
                    <i class="ki-duotone ki-ticket fs-5">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    <span id="participant-ticket-type">Ticket Type</span>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="button" id="confirm-btn" class="btn-action btn-success" onclick="confirmCheckIn()">
                        <i class="ki-duotone ki-check fs-2 me-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        Confirm Check-in
                    </button>
                    <button type="button" id="undo-btn" class="btn-action btn-danger hidden" onclick="undoCheckIn()">
                        <i class="ki-duotone ki-cross fs-2 me-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        Undo Check-in
                    </button>
                </div>
            </div>
        </div>
        
    </div>
    
    <!-- Loading Overlay -->
    <div id="loading-overlay" class="loading-overlay hidden">
        <div class="loading-content">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <div class="fw-bold">Processing...</div>
            <div class="text-muted">Please wait</div>
        </div>
    </div>
    
    <!-- Toast Container -->
    <div id="toast-container"></div>
    
    <!-- Scripts -->
    <script src="{{ asset('demo1/assets/plugins/global/plugins.bundle.js') }}"></script>
    
    <script>
        let currentParticipant = null;
        let selectedWorkshopId = {{ request('workshop_id') ?? 'null' }};
        
        document.addEventListener('DOMContentLoaded', function() {
            // Load statistics if workshop is selected
            if (selectedWorkshopId) {
                loadStatistics();
            }
            
            // Auto-focus on input
            document.getElementById('ticket-input').focus();
            
            // Handle enter key
            document.getElementById('ticket-input').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    processTicket();
                }
            });
            
            // Auto-clear input after 3 seconds of inactivity
            let inputTimer;
            document.getElementById('ticket-input').addEventListener('input', function() {
                clearTimeout(inputTimer);
                inputTimer = setTimeout(() => {
                    if (this.value && !currentParticipant) {
                        this.value = '';
                        this.focus();
                    }
                }, 3000);
            });
        });
        
        function selectWorkshop(workshopId) {
            const url = new URL(window.location);
            if (workshopId) {
                url.searchParams.set('workshop_id', workshopId);
            } else {
                url.searchParams.delete('workshop_id');
            }
            window.location.href = url.toString();
        }
        
        function processTicket() {
            const ticketCode = document.getElementById('ticket-input').value.trim();
            if (!ticketCode) {
                showToast('Please enter a ticket code', 'warning');
                return;
            }
            
            showLoading();
            
            fetch('{{ route("checkin.participant") }}', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    ticket_code: ticketCode
                })
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    showParticipantInfo(data.participant);
                    document.getElementById('ticket-input').value = '';
                } else {
                    showToast(data.error, 'error');
                    document.getElementById('ticket-input').focus();
                }
            })
            .catch(error => {
                hideLoading();
                showToast('Failed to process ticket', 'error');
                document.getElementById('ticket-input').focus();
            });
        }
        
        function showParticipantInfo(participant) {
            currentParticipant = participant;
            
            // Update participant details
            document.getElementById('participant-name').textContent = participant.name;
            document.getElementById('participant-email').textContent = participant.email;
            document.getElementById('participant-phone').textContent = participant.phone;
            document.getElementById('participant-company').textContent = participant.company || 'N/A';
            document.getElementById('participant-workshop').textContent = participant.workshop;
            document.getElementById('participant-ticket-type').textContent = participant.ticket_type;
            
            // Update status badges
            const checkinBadge = document.getElementById('checkin-badge');
            const paymentBadge = document.getElementById('payment-badge');
            const confirmBtn = document.getElementById('confirm-btn');
            const undoBtn = document.getElementById('undo-btn');
            
            if (participant.is_checked_in) {
                checkinBadge.textContent = 'Checked In';
                checkinBadge.className = 'status-badge status-success';
                confirmBtn.classList.add('hidden');
                undoBtn.classList.remove('hidden');
            } else {
                checkinBadge.textContent = 'Not Checked In';
                checkinBadge.className = 'status-badge status-danger';
                confirmBtn.classList.remove('hidden');
                undoBtn.classList.add('hidden');
            }
            
            if (participant.is_paid) {
                paymentBadge.textContent = 'Paid';
                paymentBadge.className = 'status-badge status-success';
            } else {
                paymentBadge.textContent = 'Unpaid';
                paymentBadge.className = 'status-badge status-warning';
            }
            
            // Show participant info
            document.getElementById('participant-info').classList.remove('hidden');
            
            // Auto-hide after 10 seconds if not checked in
            if (!participant.is_checked_in) {
                setTimeout(() => {
                    if (currentParticipant && currentParticipant.id === participant.id && !currentParticipant.is_checked_in) {
                        hideParticipantInfo();
                    }
                }, 10000);
            }
        }
        
        function hideParticipantInfo() {
            document.getElementById('participant-info').classList.add('hidden');
            currentParticipant = null;
            document.getElementById('ticket-input').focus();
        }
        
        function confirmCheckIn() {
            if (!currentParticipant) return;
            
            showLoading();
            
            fetch('{{ route("checkin.scan") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    ticket_code: currentParticipant.ticket_code,
                    workshop_id: selectedWorkshopId
                })
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    showToast('Check-in successful!', 'success');
                    showParticipantInfo(data.participant);
                    loadStatistics();
                    
                    // Auto-hide after 3 seconds
                    setTimeout(() => {
                        hideParticipantInfo();
                    }, 3000);
                } else {
                    showToast(data.error, data.error_type === 'already_checked_in' ? 'warning' : 'error');
                    if (data.participant) {
                        showParticipantInfo(data.participant);
                    }
                }
            })
            .catch(error => {
                hideLoading();
                showToast('Check-in failed', 'error');
            });
        }
        
        function undoCheckIn() {
            if (!currentParticipant || !confirm('Undo check-in for this participant?')) return;
            
            showLoading();
            
            fetch(`/checkin/undo/${currentParticipant.id}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    showToast('Check-in undone', 'success');
                    hideParticipantInfo();
                    loadStatistics();
                } else {
                    showToast(data.error, 'error');
                }
            })
            .catch(error => {
                hideLoading();
                showToast('Failed to undo check-in', 'error');
            });
        }
        
        function loadStatistics() {
            if (!selectedWorkshopId) return;
            
            fetch(`/checkin/statistics/${selectedWorkshopId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayStatistics(data.statistics);
                }
            })
            .catch(error => {
                console.error('Failed to load statistics');
            });
        }
        
        function displayStatistics(stats) {
            const container = document.getElementById('stats-container');
            if (!container) return;
            
            container.innerHTML = `
                <div class="stat-card">
                    <div class="stat-number text-primary">${stats.total_participants}</div>
                    <div class="stat-label">Total</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number text-success">${stats.checked_in}</div>
                    <div class="stat-label">Checked In</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number text-warning">${stats.not_checked_in}</div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number text-info">${stats.checkin_percentage}%</div>
                    <div class="stat-label">Completion</div>
                </div>
            `;
        }
        
        function showLoading() {
            document.getElementById('loading-overlay').classList.remove('hidden');
        }
        
        function hideLoading() {
            document.getElementById('loading-overlay').classList.add('hidden');
        }
        
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `toast toast-${type} show`;
            toast.textContent = message;
            
            document.getElementById('toast-container').appendChild(toast);
            
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }, 3000);
        }
    </script>
    
</body>
</html>