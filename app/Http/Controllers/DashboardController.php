<?php

namespace App\Http\Controllers;

use App\Services\StatisticsService;
use App\Models\Workshop;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class DashboardController extends Controller
{
    protected StatisticsService $statisticsService;

    public function __construct(StatisticsService $statisticsService)
    {
        $this->statisticsService = $statisticsService;
    }

    /**
     * Display the main dashboard.
     */
    public function index(Request $request): View
    {
        // Get dashboard statistics
        $statistics = $this->statisticsService->getDashboardStatistics();
        
        // Get workshops for filtering dropdown
        $workshops = Workshop::orderBy('date_time', 'desc')->get();
        
        // Get selected filters
        $selectedWorkshop = $request->filled('workshop_id') ? 
            Workshop::find($request->workshop_id) : null;
        
        $dateRange = $this->getDateRangeFromRequest($request);
        
        return view('dashboard.index', compact(
            'statistics', 
            'workshops', 
            'selectedWorkshop', 
            'dateRange'
        ));
    }

    /**
     * Get overview statistics for dashboard cards (API endpoint).
     */
    public function getOverviewStats(Request $request): JsonResponse
    {
        $cacheKey = 'dashboard_overview_' . md5($request->getQueryString());
        
        $stats = Cache::remember($cacheKey, 5, function () use ($request) {
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $startDate = Carbon::parse($request->start_date);
                $endDate = Carbon::parse($request->end_date);
                return $this->statisticsService->getFilteredStatistics($startDate, $endDate);
            }
            
            return $this->statisticsService->getOverviewStatistics();
        });

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get workshop statistics (API endpoint).
     */
    public function getWorkshopStats(Request $request): JsonResponse
    {
        $request->validate([
            'workshop_id' => 'nullable|exists:workshops,id',
            'status' => 'nullable|in:draft,published,ongoing,completed,cancelled',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $cacheKey = 'workshop_stats_' . md5($request->getQueryString());
        
        $stats = Cache::remember($cacheKey, 10, function () use ($request) {
            if ($request->filled('workshop_id')) {
                $workshop = Workshop::findOrFail($request->workshop_id);
                return $this->statisticsService->getWorkshopDetailedStatistics($workshop);
            }
            
            return $this->statisticsService->getWorkshopStatistics();
        });

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get participant statistics (API endpoint).
     */
    public function getParticipantStats(Request $request): JsonResponse
    {
        $request->validate([
            'workshop_id' => 'nullable|exists:workshops,id',
            'company' => 'nullable|string',
            'position' => 'nullable|string',
        ]);

        $cacheKey = 'participant_stats_' . md5($request->getQueryString());
        
        $stats = Cache::remember($cacheKey, 10, function () {
            return $this->statisticsService->getParticipantStatistics();
        });

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get revenue statistics (API endpoint).
     */
    public function getRevenueStats(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'workshop_id' => 'nullable|exists:workshops,id',
        ]);

        $cacheKey = 'revenue_stats_' . md5($request->getQueryString());
        
        $stats = Cache::remember($cacheKey, 10, function () use ($request) {
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $startDate = Carbon::parse($request->start_date);
                $endDate = Carbon::parse($request->end_date);
                $filtered = $this->statisticsService->getFilteredStatistics($startDate, $endDate);
                return $filtered['revenue'] ?? [];
            }
            
            return $this->statisticsService->getRevenueStatistics();
        });

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get trend data for charts (API endpoint).
     */
    public function getTrendData(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'nullable|in:7d,30d,90d,12m',
            'metric' => 'nullable|in:workshops,participants,revenue,checkins',
        ]);

        $period = $request->get('period', '12m');
        $metric = $request->get('metric', 'all');
        
        $cacheKey = "trend_data_{$period}_{$metric}";
        
        $trends = Cache::remember($cacheKey, 30, function () use ($period, $metric) {
            $data = $this->statisticsService->getTrendStatistics();
            
            if ($metric !== 'all') {
                return [
                    'months' => $data['months'],
                    $metric => $data[$metric],
                    'growth_rate' => $data['growth_rates'][$metric] ?? 0,
                ];
            }
            
            return $data;
        });

        return response()->json([
            'success' => true,
            'data' => $trends
        ]);
    }

    /**
     * Get recent activity feed (API endpoint).
     */
    public function getRecentActivity(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:100',
            'type' => 'nullable|in:check_in,registration,workshop_created',
        ]);

        $limit = $request->get('limit', 20);
        
        $cacheKey = "recent_activity_{$limit}";
        
        $activities = Cache::remember($cacheKey, 5, function () use ($limit) {
            return $this->statisticsService->getRecentActivity($limit);
        });

        // Filter by type if specified
        if ($request->filled('type')) {
            $activities = array_filter($activities, function ($activity) use ($request) {
                return $activity['type'] === $request->type;
            });
            $activities = array_values($activities); // Re-index array
        }

        return response()->json([
            'success' => true,
            'data' => $activities
        ]);
    }

    /**
     * Get workshop comparison data (API endpoint).
     */
    public function getWorkshopComparison(Request $request): JsonResponse
    {
        $request->validate([
            'workshop_ids' => 'required|array|min:2|max:5',
            'workshop_ids.*' => 'exists:workshops,id',
        ]);

        $workshopIds = $request->workshop_ids;
        $cacheKey = 'workshop_comparison_' . md5(implode(',', $workshopIds));
        
        $comparison = Cache::remember($cacheKey, 15, function () use ($workshopIds) {
            $workshops = Workshop::whereIn('id', $workshopIds)
                ->with(['participants.ticketType'])
                ->get();
            
            $comparisonData = [];
            foreach ($workshops as $workshop) {
                $stats = $this->statisticsService->getWorkshopDetailedStatistics($workshop);
                $comparisonData[] = [
                    'workshop_id' => $workshop->id,
                    'workshop_name' => $workshop->name,
                    'date_time' => $workshop->date_time->format('Y-m-d H:i'),
                    'status' => $workshop->status,
                    'total_participants' => $stats['participant_stats']['total_participants'],
                    'checked_in' => $stats['participant_stats']['checked_in'],
                    'checkin_percentage' => $stats['participant_stats']['checkin_percentage'],
                    'paid_participants' => $stats['participant_stats']['paid_participants'],
                    'payment_percentage' => $stats['participant_stats']['payment_percentage'],
                    'total_revenue' => $stats['revenue_stats']['total_revenue'],
                    'potential_revenue' => $stats['revenue_stats']['potential_revenue'],
                    'revenue_realization_rate' => $stats['revenue_stats']['revenue_realization_rate'],
                ];
            }
            
            return $comparisonData;
        });

        return response()->json([
            'success' => true,
            'data' => $comparison
        ]);
    }

    /**
     * Get real-time dashboard updates (API endpoint).
     */
    public function getRealTimeUpdates(Request $request): JsonResponse
    {
        $request->validate([
            'last_update' => 'nullable|date',
        ]);

        $lastUpdate = $request->filled('last_update') ? 
            Carbon::parse($request->last_update) : 
            Carbon::now()->subMinutes(5);

        // Get recent activities since last update
        $recentActivities = $this->statisticsService->getRecentActivity(50);
        $newActivities = array_filter($recentActivities, function ($activity) use ($lastUpdate) {
            return Carbon::parse($activity['timestamp'])->gt($lastUpdate);
        });

        // Get current overview stats
        $currentStats = $this->statisticsService->getOverviewStatistics();

        return response()->json([
            'success' => true,
            'data' => [
                'timestamp' => now()->toISOString(),
                'new_activities' => array_values($newActivities),
                'current_stats' => $currentStats,
                'has_updates' => count($newActivities) > 0,
            ]
        ]);
    }

    /**
     * Export dashboard data (API endpoint).
     */
    public function exportData(Request $request): JsonResponse
    {
        $request->validate([
            'format' => 'required|in:json,csv',
            'type' => 'required|in:overview,workshops,participants,revenue,trends',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $type = $request->type;
        $format = $request->format;
        
        // Get data based on type
        $data = match ($type) {
            'overview' => $this->statisticsService->getOverviewStatistics(),
            'workshops' => $this->statisticsService->getWorkshopStatistics(),
            'participants' => $this->statisticsService->getParticipantStatistics(),
            'revenue' => $this->statisticsService->getRevenueStatistics(),
            'trends' => $this->statisticsService->getTrendStatistics(),
            default => []
        };

        // Apply date filtering if specified
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            $data = $this->statisticsService->getFilteredStatistics($startDate, $endDate);
        }

        if ($format === 'csv') {
            return $this->exportToCsv($data, $type);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
            'export_info' => [
                'type' => $type,
                'format' => $format,
                'generated_at' => now()->toISOString(),
                'record_count' => $this->getRecordCount($data),
            ]
        ]);
    }

    /**
     * Clear dashboard cache (API endpoint).
     */
    public function clearCache(): JsonResponse
    {
        $this->statisticsService->clearCache();
        
        // Clear dashboard-specific cache keys
        $cacheKeys = [
            'dashboard_overview_*',
            'workshop_stats_*',
            'participant_stats_*',
            'revenue_stats_*',
            'trend_data_*',
            'recent_activity_*',
            'workshop_comparison_*',
        ];

        foreach ($cacheKeys as $pattern) {
            Cache::forget($pattern);
        }

        return response()->json([
            'success' => true,
            'message' => 'Dashboard cache cleared successfully'
        ]);
    }

    /**
     * Get dashboard configuration (API endpoint).
     */
    public function getConfig(): JsonResponse
    {
        $config = [
            'refresh_intervals' => [
                'overview' => 30000, // 30 seconds
                'activities' => 15000, // 15 seconds
                'charts' => 60000, // 1 minute
                'statistics' => 120000, // 2 minutes
            ],
            'chart_colors' => [
                'primary' => '#009ef7',
                'success' => '#50cd89',
                'warning' => '#ffc700',
                'danger' => '#f1416c',
                'info' => '#7239ea',
            ],
            'date_ranges' => [
                'last_7_days' => [
                    'start' => Carbon::now()->subDays(7)->format('Y-m-d'),
                    'end' => Carbon::now()->format('Y-m-d'),
                ],
                'last_30_days' => [
                    'start' => Carbon::now()->subDays(30)->format('Y-m-d'),
                    'end' => Carbon::now()->format('Y-m-d'),
                ],
                'last_90_days' => [
                    'start' => Carbon::now()->subDays(90)->format('Y-m-d'),
                    'end' => Carbon::now()->format('Y-m-d'),
                ],
                'this_year' => [
                    'start' => Carbon::now()->startOfYear()->format('Y-m-d'),
                    'end' => Carbon::now()->format('Y-m-d'),
                ],
            ],
            'features' => [
                'real_time_updates' => true,
                'export_data' => true,
                'workshop_comparison' => true,
                'advanced_filtering' => true,
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $config
        ]);
    }

    /**
     * Get date range from request parameters.
     */
    private function getDateRangeFromRequest(Request $request): array
    {
        $defaultStart = Carbon::now()->subDays(30);
        $defaultEnd = Carbon::now();

        return [
            'start' => $request->filled('start_date') ? 
                Carbon::parse($request->start_date) : $defaultStart,
            'end' => $request->filled('end_date') ? 
                Carbon::parse($request->end_date) : $defaultEnd,
        ];
    }

    /**
     * Export data to CSV format.
     */
    private function exportToCsv(array $data, string $type): JsonResponse
    {
        $csvData = $this->convertToCsvFormat($data, $type);
        $filename = "dashboard_{$type}_" . now()->format('Y-m-d_H-i-s') . '.csv';

        return response()->json([
            'success' => true,
            'data' => $csvData,
            'filename' => $filename,
            'content_type' => 'text/csv',
        ]);
    }

    /**
     * Convert data to CSV format.
     */
    private function convertToCsvFormat(array $data, string $type): string
    {
        $csv = '';
        
        switch ($type) {
            case 'overview':
                $csv = "Metric,Value\n";
                foreach ($data as $key => $value) {
                    $csv .= ucfirst(str_replace('_', ' ', $key)) . "," . $value . "\n";
                }
                break;
                
            case 'workshops':
                if (isset($data['workshops'])) {
                    $csv = "Workshop,Date,Status,Participants,Checked In,Revenue\n";
                    foreach ($data['workshops'] as $workshop) {
                        $csv .= sprintf(
                            "%s,%s,%s,%d,%d,%.2f\n",
                            $workshop['name'],
                            $workshop['date_time'],
                            $workshop['status'],
                            $workshop['total_participants'],
                            $workshop['checked_in'],
                            $workshop['revenue']
                        );
                    }
                }
                break;
                
            default:
                $csv = "Data\n" . json_encode($data);
        }

        return $csv;
    }

    /**
     * Get record count from data array.
     */
    private function getRecordCount(array $data): int
    {
        if (isset($data['workshops'])) {
            return count($data['workshops']);
        }
        
        if (isset($data['participants'])) {
            return $data['total_participants'] ?? 0;
        }
        
        return 1; // For single record data like overview
    }
}