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
    
    <div class="d-flex align-items-center gap-2 gap-lg-3">
        <!-- Date Range Filter -->
        <div class="d-flex align-items-center">
            <select class="form-select form-select-sm" id="date-range-filter" data-control="select2" data-placeholder="Select date range">
                <option value="last_7_days">Last 7 Days</option>
                <option value="last_30_days" selected>Last 30 Days</option>
                <option value="last_90_days">Last 90 Days</option>
                <option value="this_year">This Year</option>
                <option value="custom">Custom Range</option>
            </select>
        </div>
        
        <!-- Workshop Filter -->
        <div class="d-flex align-items-center">
            <select class="form-select form-select-sm" id="workshop-filter" data-control="select2" data-placeholder="All Workshops">
                <option value="">All Workshops</option>
                @foreach($workshops as $workshop)
                    <option value="{{ $workshop->id }}" {{ $selectedWorkshop && $selectedWorkshop->id == $workshop->id ? 'selected' : '' }}>
                        {{ $workshop->name }}
                    </option>
                @endforeach
            </select>
        </div>
        
        <!-- Refresh Button -->
        <button type="button" class="btn btn-sm btn-light-primary" id="refresh-dashboard">
            <i class="ki-duotone ki-arrows-circle fs-4">
                <span class="path1"></span>
                <span class="path2"></span>
            </i>
            Refresh
        </button>
        
        <!-- Auto-refresh Toggle -->
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="auto-refresh-toggle" checked>
            <label class="form-check-label" for="auto-refresh-toggle">
                Auto-refresh
            </label>
        </div>
    </div>
@endsection

@section('content')

<!-- Overview Statistics Cards -->
<div class="row g-5 g-xl-10 mb-5 mb-xl-10">
    <div class="col-xl-3">
        <div class="card card-flush bgi-no-repeat bgi-size-contain bgi-position-x-end h-xl-100" style="background-color: #F1416C;background-image:url('{{ asset('demo1/assets/media/patterns/vector-1.png') }}')">
            <div class="card-header pt-5">
                <div class="card-title d-flex flex-column">
                    <span class="fs-2hx fw-bold text-white me-2 lh-1 ls-n2" id="total-workshops">{{ $statistics['overview']['total_workshops'] ?? 0 }}</span>
                    <span class="text-white opacity-75 pt-1 fw-semibold fs-6">Total Workshops</span>
                </div>
            </div>
            <div class="card-body d-flex align-items-end pt-0">
                <div class="d-flex align-items-center flex-column mt-3 w-100">
                    <div class="d-flex justify-content-between w-100 mt-auto mb-2">
                        <span class="fw-bolder fs-6 text-white opacity-75">Active</span>
                        <span class="fw-bold fs-6 text-white" id="active-workshops">{{ $statistics['overview']['active_workshops'] ?? 0 }}</span>
                    </div>
                    <div class="h-8px mx-3 w-100 bg-white bg-opacity-50 rounded">
                        <div class="bg-white rounded h-8px" role="progressbar" style="width: {{ $statistics['overview']['total_workshops'] > 0 ? round(($statistics['overview']['active_workshops'] / $statistics['overview']['total_workshops']) * 100) : 0 }}%" id="workshops-progress"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3">
        <div class="card card-flush bgi-no-repeat bgi-size-contain bgi-position-x-end h-xl-100" style="background-color: #7239EA;background-image:url('{{ asset('demo1/assets/media/patterns/vector-2.png') }}')">
            <div class="card-header pt-5">
                <div class="card-title d-flex flex-column">
                    <span class="fs-2hx fw-bold text-white me-2 lh-1 ls-n2" id="total-participants">{{ $statistics['overview']['total_participants'] ?? 0 }}</span>
                    <span class="text-white opacity-75 pt-1 fw-semibold fs-6">Total Participants</span>
                </div>
            </div>
            <div class="card-body d-flex align-items-end pt-0">
                <div class="d-flex align-items-center flex-column mt-3 w-100">
                    <div class="d-flex justify-content-between w-100 mt-auto mb-2">
                        <span class="fw-bolder fs-6 text-white opacity-75">Checked In</span>
                        <span class="fw-bold fs-6 text-white" id="checked-in-participants">{{ $statistics['overview']['checked_in_participants'] ?? 0 }}</span>
                    </div>
                    <div class="h-8px mx-3 w-100 bg-white bg-opacity-50 rounded">
                        <div class="bg-white rounded h-8px" role="progressbar" style="width: {{ $statistics['overview']['checkin_rate'] ?? 0 }}%" id="checkin-progress"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3">
        <div class="card card-flush bgi-no-repeat bgi-size-contain bgi-position-x-end h-xl-100" style="background-color: #17C653;background-image:url('{{ asset('demo1/assets/media/patterns/vector-3.png') }}')">
            <div class="card-header pt-5">
                <div class="card-title d-flex flex-column">
                    <span class="fs-2hx fw-bold text-white me-2 lh-1 ls-n2" id="total-revenue">${{ number_format($statistics['overview']['total_revenue'] ?? 0, 2) }}</span>
                    <span class="text-white opacity-75 pt-1 fw-semibold fs-6">Total Revenue</span>
                </div>
            </div>
            <div class="card-body d-flex align-items-end pt-0">
                <div class="d-flex align-items-center flex-column mt-3 w-100">
                    <div class="d-flex justify-content-between w-100 mt-auto mb-2">
                        <span class="fw-bolder fs-6 text-white opacity-75">Payment Rate</span>
                        <span class="fw-bold fs-6 text-white" id="payment-rate">{{ $statistics['overview']['payment_rate'] ?? 0 }}%</span>
                    </div>
                    <div class="h-8px mx-3 w-100 bg-white bg-opacity-50 rounded">
                        <div class="bg-white rounded h-8px" role="progressbar" style="width: {{ $statistics['overview']['payment_rate'] ?? 0 }}%" id="payment-progress"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3">
        <div class="card card-flush bgi-no-repeat bgi-size-contain bgi-position-x-end h-xl-100" style="background-color: #FFC700;background-image:url('{{ asset('demo1/assets/media/patterns/vector-4.png') }}')">
            <div class="card-header pt-5">
                <div class="card-title d-flex flex-column">
                    <span class="fs-2hx fw-bold text-white me-2 lh-1 ls-n2" id="checkin-rate">{{ $statistics['overview']['checkin_rate'] ?? 0 }}%</span>
                    <span class="text-white opacity-75 pt-1 fw-semibold fs-6">Check-in Rate</span>
                </div>
            </div>
            <div class="card-body d-flex align-items-end pt-0">
                <div class="d-flex align-items-center flex-column mt-3 w-100">
                    <div class="d-flex justify-content-between w-100 mt-auto mb-2">
                        <span class="fw-bolder fs-6 text-white opacity-75">Pending</span>
                        <span class="fw-bold fs-6 text-white" id="pending-checkin">{{ $statistics['overview']['pending_checkin'] ?? 0 }}</span>
                    </div>
                    <div class="h-8px mx-3 w-100 bg-white bg-opacity-50 rounded">
                        <div class="bg-white rounded h-8px" role="progressbar" style="width: {{ 100 - ($statistics['overview']['checkin_rate'] ?? 0) }}%" id="pending-progress"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts and Analytics -->
<div class="row g-5 g-xl-10 mb-5 mb-xl-10">
    
    <!-- Trends Chart -->
    <div class="col-xl-8">
        <div class="card card-flush h-xl-100">
            <div class="card-header align-items-center py-5 gap-2 gap-md-5">
                <div class="card-title">
                    <h3 class="fw-bold">Trends Overview</h3>
                </div>
                <div class="card-toolbar">
                    <div class="d-flex gap-2">
                        <select class="form-select form-select-sm" id="trend-metric" data-control="select2">
                            <option value="all">All Metrics</option>
                            <option value="workshops">Workshops</option>
                            <option value="participants">Participants</option>
                            <option value="revenue">Revenue</option>
                            <option value="checkins">Check-ins</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-body pt-0">
                <canvas id="trends-chart" height="300"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Workshop Status Distribution -->
    <div class="col-xl-4">
        <div class="card card-flush h-xl-100">
            <div class="card-header align-items-center py-5">
                <div class="card-title">
                    <h3 class="fw-bold">Workshop Status</h3>
                </div>
            </div>
            <div class="card-body pt-0">
                <canvas id="workshop-status-chart" height="300"></canvas>
            </div>
        </div>
    </div>
    
</div>

<!-- Recent Activity and Quick Actions -->
<div class="row g-5 g-xl-10 mb-5 mb-xl-10">
    
    <!-- Recent Activity -->
    <div class="col-xl-6">
        <div class="card card-flush h-xl-100">
            <div class="card-header align-items-center py-5 gap-2 gap-md-5">
                <div class="card-title">
                    <h3 class="fw-bold">Recent Activity</h3>
                </div>
                <div class="card-toolbar">
                    <button type="button" class="btn btn-sm btn-light-primary" id="refresh-activity">
                        <i class="ki-duotone ki-arrows-circle fs-4">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                    </button>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="timeline-label" id="activity-timeline">
                    <!-- Activity items will be loaded here -->
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="col-xl-6">
        <div class="card card-flush h-xl-100">
            <div class="card-header align-items-center py-5">
                <div class="card-title">
                    <h3 class="fw-bold">Quick Actions</h3>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="row g-3">
                    <div class="col-6">
                        <a href="{{ route('checkin.index') }}" class="btn btn-light-primary w-100 h-100 d-flex flex-column align-items-center justify-content-center py-5">
                            <i class="ki-duotone ki-scan-barcode fs-2x mb-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                                <span class="path4"></span>
                                <span class="path5"></span>
                                <span class="path6"></span>
                            </i>
                            <span class="fw-bold fs-6">Check-in System</span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="{{ route('workshops.index') }}" class="btn btn-light-success w-100 h-100 d-flex flex-column align-items-center justify-content-center py-5">
                            <i class="ki-duotone ki-calendar fs-2x mb-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <span class="fw-bold fs-6">Workshops</span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="{{ route('participants.index') }}" class="btn btn-light-warning w-100 h-100 d-flex flex-column align-items-center justify-content-center py-5">
                            <i class="ki-duotone ki-people fs-2x mb-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                                <span class="path4"></span>
                                <span class="path5"></span>
                            </i>
                            <span class="fw-bold fs-6">Participants</span>
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="{{ route('email-templates.index') }}" class="btn btn-light-info w-100 h-100 d-flex flex-column align-items-center justify-content-center py-5">
                            <i class="ki-duotone ki-sms fs-2x mb-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <span class="fw-bold fs-6">Email Templates</span>
                        </a>
                    </div>
                </div>
                
                <!-- Export and Cache Actions -->
                <div class="separator my-5"></div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light-primary btn-sm flex-grow-1" id="export-data-btn">
                        <i class="ki-duotone ki-exit-down fs-4">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        Export Data
                    </button>
                    <button type="button" class="btn btn-light-danger btn-sm" id="clear-cache-btn">
                        <i class="ki-duotone ki-trash fs-4">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                            <span class="path4"></span>
                            <span class="path5"></span>
                        </i>
                        Clear Cache
                    </button>
                </div>
            </div>
        </div>
    </div>
    
</div>

<!-- Workshop Performance Table -->
<div class="row g-5 g-xl-10">
    <div class="col-12">
        <div class="card card-flush">
            <div class="card-header align-items-center py-5 gap-2 gap-md-5">
                <div class="card-title">
                    <h3 class="fw-bold">Workshop Performance</h3>
                </div>
                <div class="card-toolbar">
                    <button type="button" class="btn btn-sm btn-light-primary" id="refresh-workshops">
                        <i class="ki-duotone ki-arrows-circle fs-4">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        Refresh
                    </button>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4" id="workshops-table">
                        <thead>
                            <tr class="fw-bold text-muted">
                                <th class="min-w-150px">Workshop</th>
                                <th class="min-w-100px">Date</th>
                                <th class="min-w-80px">Status</th>
                                <th class="min-w-80px">Participants</th>
                                <th class="min-w-80px">Checked In</th>
                                <th class="min-w-80px">Check-in Rate</th>
                                <th class="min-w-80px">Revenue</th>
                                <th class="min-w-80px">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="workshops-table-body">
                            <!-- Workshop data will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let dashboardConfig = {};
let autoRefreshInterval = null;
let trendsChart = null;
let statusChart = null;

$(document).ready(function() {
    // Load dashboard configuration
    loadDashboardConfig();
    
    // Initialize dashboard
    initializeDashboard();
    
    // Set up event listeners
    setupEventListeners();
    
    // Start auto-refresh if enabled
    if ($('#auto-refresh-toggle').is(':checked')) {
        startAutoRefresh();
    }
});

function loadDashboardConfig() {
    $.get('{{ route("api.dashboard.config") }}')
        .done(function(response) {
            if (response.success) {
                dashboardConfig = response.data;
            }
        });
}

function initializeDashboard() {
    loadOverviewStats();
    loadTrendsChart();
    loadWorkshopStatusChart();
    loadRecentActivity();
    loadWorkshopsTable();
}

function setupEventListeners() {
    // Date range filter
    $('#date-range-filter').change(function() {
        refreshDashboard();
    });
    
    // Workshop filter
    $('#workshop-filter').change(function() {
        refreshDashboard();
    });
    
    // Refresh button
    $('#refresh-dashboard').click(function() {
        refreshDashboard();
    });
    
    // Auto-refresh toggle
    $('#auto-refresh-toggle').change(function() {
        if (this.checked) {
            startAutoRefresh();
        } else {
            stopAutoRefresh();
        }
    });
    
    // Trend metric selector
    $('#trend-metric').change(function() {
        loadTrendsChart();
    });
    
    // Activity refresh
    $('#refresh-activity').click(function() {
        loadRecentActivity();
    });
    
    // Workshops refresh
    $('#refresh-workshops').click(function() {
        loadWorkshopsTable();
    });
    
    // Export data
    $('#export-data-btn').click(function() {
        exportDashboardData();
    });
    
    // Clear cache
    $('#clear-cache-btn').click(function() {
        clearDashboardCache();
    });
}

function loadOverviewStats() {
    const params = getFilterParams();
    
    $.get('{{ route("api.dashboard.overview") }}', params)
        .done(function(response) {
            if (response.success) {
                updateOverviewCards(response.data);
            }
        })
        .fail(function() {
            showToast('Failed to load overview statistics', 'error');
        });
}

function updateOverviewCards(data) {
    $('#total-workshops').text(data.total_workshops || 0);
    $('#active-workshops').text(data.active_workshops || 0);
    $('#total-participants').text(data.total_participants || 0);
    $('#checked-in-participants').text(data.checked_in_participants || 0);
    $('#total-revenue').text('$' + (data.total_revenue || 0).toLocaleString());
    $('#payment-rate').text((data.payment_rate || 0) + '%');
    $('#checkin-rate').text((data.checkin_rate || 0) + '%');
    $('#pending-checkin').text(data.pending_checkin || 0);
    
    // Update progress bars
    const workshopProgress = data.total_workshops > 0 ? (data.active_workshops / data.total_workshops) * 100 : 0;
    $('#workshops-progress').css('width', workshopProgress + '%');
    $('#checkin-progress').css('width', (data.checkin_rate || 0) + '%');
    $('#payment-progress').css('width', (data.payment_rate || 0) + '%');
    $('#pending-progress').css('width', (100 - (data.checkin_rate || 0)) + '%');
}

function loadTrendsChart() {
    const metric = $('#trend-metric').val();
    const params = { metric: metric, period: '12m' };
    
    $.get('{{ route("api.dashboard.trends") }}', params)
        .done(function(response) {
            if (response.success) {
                renderTrendsChart(response.data);
            }
        })
        .fail(function() {
            showToast('Failed to load trends data', 'error');
        });
}

function renderTrendsChart(data) {
    const ctx = document.getElementById('trends-chart').getContext('2d');
    
    if (trendsChart) {
        trendsChart.destroy();
    }
    
    const datasets = [];
    const colors = dashboardConfig.chart_colors || {
        primary: '#009ef7',
        success: '#50cd89',
        warning: '#ffc700',
        danger: '#f1416c'
    };
    
    if (data.workshops) {
        datasets.push({
            label: 'Workshops',
            data: data.workshops,
            borderColor: colors.primary,
            backgroundColor: colors.primary + '20',
            tension: 0.4
        });
    }
    
    if (data.participants) {
        datasets.push({
            label: 'Participants',
            data: data.participants,
            borderColor: colors.success,
            backgroundColor: colors.success + '20',
            tension: 0.4
        });
    }
    
    if (data.revenue) {
        datasets.push({
            label: 'Revenue ($)',
            data: data.revenue,
            borderColor: colors.warning,
            backgroundColor: colors.warning + '20',
            tension: 0.4,
            yAxisID: 'y1'
        });
    }
    
    if (data.checkins) {
        datasets.push({
            label: 'Check-ins',
            data: data.checkins,
            borderColor: colors.danger,
            backgroundColor: colors.danger + '20',
            tension: 0.4
        });
    }
    
    trendsChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.months || [],
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    position: 'left'
                },
                y1: {
                    type: 'linear',
                    display: data.revenue ? true : false,
                    position: 'right',
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            }
        }
    });
}

function loadWorkshopStatusChart() {
    $.get('{{ route("api.dashboard.workshops") }}')
        .done(function(response) {
            if (response.success && response.data.workshops) {
                renderWorkshopStatusChart(response.data.workshops);
            }
        })
        .fail(function() {
            showToast('Failed to load workshop status data', 'error');
        });
}

function renderWorkshopStatusChart(workshops) {
    const statusCounts = {};
    workshops.forEach(workshop => {
        statusCounts[workshop.status] = (statusCounts[workshop.status] || 0) + 1;
    });
    
    const ctx = document.getElementById('workshop-status-chart').getContext('2d');
    
    if (statusChart) {
        statusChart.destroy();
    }
    
    const colors = ['#009ef7', '#50cd89', '#ffc700', '#f1416c', '#7239ea'];
    
    statusChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(statusCounts),
            datasets: [{
                data: Object.values(statusCounts),
                backgroundColor: colors,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom'
                }
            }
        }
    });
}

function loadRecentActivity() {
    $.get('{{ route("api.dashboard.activity") }}', { limit: 10 })
        .done(function(response) {
            if (response.success) {
                renderRecentActivity(response.data);
            }
        })
        .fail(function() {
            showToast('Failed to load recent activity', 'error');
        });
}

function renderRecentActivity(activities) {
    const timeline = $('#activity-timeline');
    timeline.empty();
    
    if (activities.length === 0) {
        timeline.html('<div class="text-center text-muted py-5">No recent activity</div>');
        return;
    }
    
    activities.forEach(activity => {
        const timeAgo = moment(activity.timestamp).fromNow();
        const iconClass = getActivityIcon(activity.type);
        const colorClass = getActivityColor(activity.type);
        
        const item = `
            <div class="timeline-item">
                <div class="timeline-line w-40px"></div>
                <div class="timeline-icon symbol symbol-circle symbol-40px me-4">
                    <div class="symbol-label ${colorClass}">
                        <i class="${iconClass} fs-2 text-white"></i>
                    </div>
                </div>
                <div class="timeline-content mb-10 mt-n1">
                    <div class="pe-3 mb-5">
                        <div class="fs-5 fw-semibold mb-2">${activity.description}</div>
                        <div class="d-flex align-items-center mt-1 fs-6">
                            <div class="text-muted me-2 fs-7">${timeAgo}</div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        timeline.append(item);
    });
}

function loadWorkshopsTable() {
    $.get('{{ route("api.dashboard.workshops") }}')
        .done(function(response) {
            if (response.success && response.data.workshops) {
                renderWorkshopsTable(response.data.workshops);
            }
        })
        .fail(function() {
            showToast('Failed to load workshops data', 'error');
        });
}

function renderWorkshopsTable(workshops) {
    const tbody = $('#workshops-table-body');
    tbody.empty();
    
    workshops.forEach(workshop => {
        const statusBadge = getStatusBadge(workshop.status);
        const checkinRate = workshop.checkin_percentage || 0;
        const revenue = workshop.revenue || 0;
        
        const row = `
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="d-flex justify-content-start flex-column">
                            <span class="text-dark fw-bold text-hover-primary fs-6">${workshop.name}</span>
                            <span class="text-muted fw-semibold text-muted d-block fs-7">${workshop.location || 'N/A'}</span>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="text-dark fw-bold d-block fs-6">${moment(workshop.date_time).format('MMM DD, YYYY')}</span>
                    <span class="text-muted fw-semibold d-block fs-7">${moment(workshop.date_time).format('HH:mm')}</span>
                </td>
                <td>
                    ${statusBadge}
                </td>
                <td>
                    <span class="text-dark fw-bold fs-6">${workshop.total_participants}</span>
                </td>
                <td>
                    <span class="text-dark fw-bold fs-6">${workshop.checked_in}</span>
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <span class="me-2 fw-bold fs-6">${checkinRate}%</span>
                        <div class="progress h-6px w-60px">
                            <div class="progress-bar bg-success" role="progressbar" style="width: ${checkinRate}%"></div>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="text-dark fw-bold fs-6">$${revenue.toLocaleString()}</span>
                </td>
                <td>
                    <a href="/workshops/${workshop.id}" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm">
                        <i class="ki-duotone ki-eye fs-3">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                        </i>
                    </a>
                </td>
            </tr>
        `;
        
        tbody.append(row);
    });
}

function getFilterParams() {
    const params = {};
    
    const workshopId = $('#workshop-filter').val();
    if (workshopId) {
        params.workshop_id = workshopId;
    }
    
    const dateRange = $('#date-range-filter').val();
    if (dateRange && dashboardConfig.date_ranges && dashboardConfig.date_ranges[dateRange]) {
        params.start_date = dashboardConfig.date_ranges[dateRange].start;
        params.end_date = dashboardConfig.date_ranges[dateRange].end;
    }
    
    return params;
}

function refreshDashboard() {
    loadOverviewStats();
    loadTrendsChart();
    loadWorkshopStatusChart();
    loadRecentActivity();
    loadWorkshopsTable();
    showToast('Dashboard refreshed', 'success');
}

function startAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
    }
    
    autoRefreshInterval = setInterval(() => {
        loadOverviewStats();
        loadRecentActivity();
    }, dashboardConfig.refresh_intervals?.overview || 30000);
}

function stopAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
        autoRefreshInterval = null;
    }
}

function exportDashboardData() {
    const params = {
        format: 'json',
        type: 'overview',
        ...getFilterParams()
    };
    
    $.get('{{ route("api.dashboard.export") }}', params)
        .done(function(response) {
            if (response.success) {
                const dataStr = JSON.stringify(response.data, null, 2);
                const dataBlob = new Blob([dataStr], {type: 'application/json'});
                const url = URL.createObjectURL(dataBlob);
                const link = document.createElement('a');
                link.href = url;
                link.download = `dashboard_export_${new Date().toISOString().split('T')[0]}.json`;
                link.click();
                showToast('Data exported successfully', 'success');
            }
        })
        .fail(function() {
            showToast('Failed to export data', 'error');
        });
}

function clearDashboardCache() {
    if (!confirm('Are you sure you want to clear the dashboard cache?')) {
        return;
    }
    
    $.post('{{ route("api.dashboard.clear-cache") }}', {
        _token: '{{ csrf_token() }}'
    })
    .done(function(response) {
        if (response.success) {
            showToast('Cache cleared successfully', 'success');
            refreshDashboard();
        }
    })
    .fail(function() {
        showToast('Failed to clear cache', 'error');
    });
}

// Helper functions
function getActivityIcon(type) {
    const icons = {
        'check_in': 'ki-duotone ki-check',
        'registration': 'ki-duotone ki-user-tick',
        'workshop_created': 'ki-duotone ki-calendar-add'
    };
    return icons[type] || 'ki-duotone ki-information';
}

function getActivityColor(type) {
    const colors = {
        'check_in': 'bg-success',
        'registration': 'bg-primary',
        'workshop_created': 'bg-warning'
    };
    return colors[type] || 'bg-info';
}

function getStatusBadge(status) {
    const badges = {
        'draft': '<span class="badge badge-light-secondary">Draft</span>',
        'published': '<span class="badge badge-light-primary">Published</span>',
        'ongoing': '<span class="badge badge-light-success">Ongoing</span>',
        'completed': '<span class="badge badge-light-info">Completed</span>',
        'cancelled': '<span class="badge badge-light-danger">Cancelled</span>'
    };
    return badges[status] || '<span class="badge badge-light-secondary">Unknown</span>';
}

function showToast(message, type = 'info') {
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
    
    setTimeout(() => {
        toast.alert('close');
    }, 5000);
}

// Load moment.js for date formatting
if (typeof moment === 'undefined') {
    $('head').append('<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>');
}
</script>
@endpush

@push('styles')
<style>
.timeline-item {
    display: flex;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.timeline-line {
    width: 2px;
    background-color: #e4e6ef;
    margin-right: 1rem;
    flex-shrink: 0;
}

.timeline-icon {
    flex-shrink: 0;
}

.timeline-content {
    flex-grow: 1;
}

.progress {
    background-color: #f1f3f6;
}

.card-flush {
    box-shadow: 0 0 50px 0 rgba(82, 63, 105, 0.15);
}

@media (max-width: 768px) {
    .fs-2hx {
        font-size: 2rem !important;
    }
    
    .card-body {
        padding: 1rem;
    }
}
</style>
@endpush