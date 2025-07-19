<?php

namespace App\Services;

use App\Models\Workshop;
use App\Models\Participant;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class StatisticsService
{
    /**
     * Cache duration in minutes
     */
    const CACHE_DURATION = 15;

    /**
     * Get comprehensive dashboard statistics
     */
    public function getDashboardStatistics(): array
    {
        return Cache::remember('dashboard_statistics', self::CACHE_DURATION, function () {
            $stats = [
                'overview' => $this->getOverviewStatistics(),
                'workshops' => $this->getWorkshopStatistics(),
                'participants' => $this->getParticipantStatistics(),
                'revenue' => $this->getRevenueStatistics(),
                'recent_activity' => $this->getRecentActivity(),
                'trends' => $this->getTrendStatistics(),
            ];

            return $stats;
        });
    }

    /**
     * Get overview statistics for dashboard cards
     */
    public function getOverviewStatistics(): array
    {
        $totalWorkshops = Workshop::count();
        $activeWorkshops = Workshop::whereIn('status', ['published', 'ongoing'])->count();
        $totalParticipants = Participant::count();
        $checkedInParticipants = Participant::where('is_checked_in', true)->count();
        $totalRevenue = $this->calculateTotalRevenue();

        return [
            'total_workshops' => $totalWorkshops,
            'active_workshops' => $activeWorkshops,
            'completed_workshops' => Workshop::where('status', 'completed')->count(),
            'draft_workshops' => Workshop::where('status', 'draft')->count(),
            'total_participants' => $totalParticipants,
            'checked_in_participants' => $checkedInParticipants,
            'pending_checkin' => $totalParticipants - $checkedInParticipants,
            'checkin_rate' => $totalParticipants > 0 ? round(($checkedInParticipants / $totalParticipants) * 100, 1) : 0,
            'total_revenue' => $totalRevenue,
            'paid_participants' => Participant::where('is_paid', true)->count(),
            'unpaid_participants' => Participant::where('is_paid', false)->count(),
            'payment_rate' => $totalParticipants > 0 ? round((Participant::where('is_paid', true)->count() / $totalParticipants) * 100, 1) : 0,
        ];
    }

    /**
     * Get detailed workshop statistics
     */
    public function getWorkshopStatistics(): array
    {
        $workshops = Workshop::with(['participants.ticketType'])
            ->orderBy('date_time', 'desc')
            ->get();

        $workshopStats = [];
        foreach ($workshops as $workshop) {
            $participants = $workshop->participants;
            $revenue = $this->calculateWorkshopRevenue($workshop);

            $workshopStats[] = [
                'id' => $workshop->id,
                'name' => $workshop->name,
                'date_time' => $workshop->date_time,
                'status' => $workshop->status,
                'location' => $workshop->location,
                'total_participants' => $participants->count(),
                'checked_in' => $participants->where('is_checked_in', true)->count(),
                'not_checked_in' => $participants->where('is_checked_in', false)->count(),
                'paid_participants' => $participants->where('is_paid', true)->count(),
                'unpaid_participants' => $participants->where('is_paid', false)->count(),
                'checkin_percentage' => $participants->count() > 0 ? 
                    round(($participants->where('is_checked_in', true)->count() / $participants->count()) * 100, 1) : 0,
                'payment_percentage' => $participants->count() > 0 ? 
                    round(($participants->where('is_paid', true)->count() / $participants->count()) * 100, 1) : 0,
                'revenue' => $revenue,
                'potential_revenue' => $this->calculateWorkshopPotentialRevenue($workshop),
                'ticket_types' => $this->getWorkshopTicketTypeStats($workshop),
            ];
        }

        return [
            'workshops' => $workshopStats,
            'summary' => [
                'total_workshops' => count($workshopStats),
                'avg_participants' => count($workshopStats) > 0 ? 
                    round(collect($workshopStats)->avg('total_participants'), 1) : 0,
                'avg_checkin_rate' => count($workshopStats) > 0 ? 
                    round(collect($workshopStats)->avg('checkin_percentage'), 1) : 0,
                'avg_payment_rate' => count($workshopStats) > 0 ? 
                    round(collect($workshopStats)->avg('payment_percentage'), 1) : 0,
                'total_revenue' => collect($workshopStats)->sum('revenue'),
            ]
        ];
    }

    /**
     * Get participant statistics and demographics
     */
    public function getParticipantStatistics(): array
    {
        $participants = Participant::with(['workshop', 'ticketType'])->get();

        // Company distribution
        $companyStats = $participants->groupBy('company')
            ->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'checked_in' => $group->where('is_checked_in', true)->count(),
                    'paid' => $group->where('is_paid', true)->count(),
                ];
            })
            ->sortByDesc('count')
            ->take(10);

        // Position distribution
        $positionStats = $participants->groupBy('position')
            ->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'checked_in' => $group->where('is_checked_in', true)->count(),
                ];
            })
            ->sortByDesc('count')
            ->take(10);

        // Registration trends (by month)
        $registrationTrends = $participants->groupBy(function ($participant) {
            return $participant->created_at->format('Y-m');
        })->map(function ($group) {
            return $group->count();
        })->sortKeys();

        return [
            'total_participants' => $participants->count(),
            'unique_companies' => $participants->pluck('company')->filter()->unique()->count(),
            'unique_positions' => $participants->pluck('position')->filter()->unique()->count(),
            'company_distribution' => $companyStats,
            'position_distribution' => $positionStats,
            'registration_trends' => $registrationTrends,
            'status_breakdown' => [
                'checked_in' => $participants->where('is_checked_in', true)->count(),
                'not_checked_in' => $participants->where('is_checked_in', false)->count(),
                'paid' => $participants->where('is_paid', true)->count(),
                'unpaid' => $participants->where('is_paid', false)->count(),
            ]
        ];
    }

    /**
     * Get comprehensive revenue statistics
     */
    public function getRevenueStatistics(): array
    {
        $participants = Participant::with(['ticketType'])->get();
        $paidParticipants = $participants->where('is_paid', true);

        // Revenue by workshop
        $revenueByWorkshop = Workshop::with(['participants.ticketType'])
            ->get()
            ->map(function ($workshop) {
                return [
                    'workshop_id' => $workshop->id,
                    'workshop_name' => $workshop->name,
                    'revenue' => $this->calculateWorkshopRevenue($workshop),
                    'potential_revenue' => $this->calculateWorkshopPotentialRevenue($workshop),
                    'participants' => $workshop->participants->count(),
                    'paid_participants' => $workshop->participants->where('is_paid', true)->count(),
                ];
            })
            ->sortByDesc('revenue');

        // Revenue by ticket type
        $revenueByTicketType = TicketType::with(['participants'])
            ->get()
            ->map(function ($ticketType) {
                $paidParticipants = $ticketType->participants->where('is_paid', true);
                return [
                    'ticket_type' => $ticketType->name,
                    'fee' => $ticketType->fee,
                    'total_participants' => $ticketType->participants->count(),
                    'paid_participants' => $paidParticipants->count(),
                    'revenue' => $paidParticipants->count() * $ticketType->fee,
                    'potential_revenue' => $ticketType->participants->count() * $ticketType->fee,
                ];
            })
            ->sortByDesc('revenue');

        // Monthly revenue trends
        $monthlyRevenue = $paidParticipants->groupBy(function ($participant) {
            return $participant->created_at->format('Y-m');
        })->map(function ($group) {
            return $group->sum(function ($participant) {
                return $participant->ticketType->fee;
            });
        })->sortKeys();

        $totalRevenue = $this->calculateTotalRevenue();
        $potentialRevenue = $this->calculatePotentialRevenue();

        return [
            'total_revenue' => $totalRevenue,
            'potential_revenue' => $potentialRevenue,
            'revenue_realization_rate' => $potentialRevenue > 0 ? 
                round(($totalRevenue / $potentialRevenue) * 100, 1) : 0,
            'average_ticket_price' => $participants->count() > 0 ? 
                round($participants->avg(function ($p) { return $p->ticketType->fee; }), 2) : 0,
            'revenue_by_workshop' => $revenueByWorkshop,
            'revenue_by_ticket_type' => $revenueByTicketType,
            'monthly_trends' => $monthlyRevenue,
            'payment_summary' => [
                'total_participants' => $participants->count(),
                'paid_participants' => $paidParticipants->count(),
                'unpaid_participants' => $participants->where('is_paid', false)->count(),
                'payment_rate' => $participants->count() > 0 ? 
                    round(($paidParticipants->count() / $participants->count()) * 100, 1) : 0,
            ]
        ];
    }

    /**
     * Get recent activity for dashboard
     */
    public function getRecentActivity(int $limit = 20): array
    {
        $recentCheckIns = Participant::where('is_checked_in', true)
            ->with(['workshop', 'ticketType'])
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($participant) {
                return [
                    'type' => 'check_in',
                    'participant_name' => $participant->name,
                    'workshop_name' => $participant->workshop->name,
                    'timestamp' => $participant->updated_at,
                    'description' => "{$participant->name} checked in to {$participant->workshop->name}",
                ];
            });

        $recentRegistrations = Participant::with(['workshop', 'ticketType'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($participant) {
                return [
                    'type' => 'registration',
                    'participant_name' => $participant->name,
                    'workshop_name' => $participant->workshop->name,
                    'timestamp' => $participant->created_at,
                    'description' => "{$participant->name} registered for {$participant->workshop->name}",
                ];
            });

        $recentWorkshops = Workshop::orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($workshop) {
                return [
                    'type' => 'workshop_created',
                    'workshop_name' => $workshop->name,
                    'timestamp' => $workshop->created_at,
                    'description' => "Workshop '{$workshop->name}' was created",
                ];
            });

        // Combine and sort all activities
        $allActivities = collect()
            ->merge($recentCheckIns)
            ->merge($recentRegistrations)
            ->merge($recentWorkshops)
            ->sortByDesc('timestamp')
            ->take($limit)
            ->values();

        return $allActivities->toArray();
    }

    /**
     * Get trend statistics for charts and analytics
     */
    public function getTrendStatistics(): array
    {
        $last12Months = collect();
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $last12Months->push($month->format('Y-m'));
        }

        // Workshop creation trends
        $workshopTrends = Workshop::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
            ->whereRaw('created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');

        // Participant registration trends
        $participantTrends = Participant::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
            ->whereRaw('created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');

        // Check-in trends
        $checkinTrends = Participant::selectRaw('DATE_FORMAT(updated_at, "%Y-%m") as month, COUNT(*) as count')
            ->where('is_checked_in', true)
            ->whereRaw('updated_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');

        // Revenue trends
        $revenueTrends = Participant::join('ticket_types', 'participants.ticket_type_id', '=', 'ticket_types.id')
            ->selectRaw('DATE_FORMAT(participants.created_at, "%Y-%m") as month, SUM(ticket_types.fee) as revenue')
            ->where('participants.is_paid', true)
            ->whereRaw('participants.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('revenue', 'month');

        // Fill missing months with zeros
        $workshopData = $last12Months->mapWithKeys(function ($month) use ($workshopTrends) {
            return [$month => $workshopTrends->get($month, 0)];
        });

        $participantData = $last12Months->mapWithKeys(function ($month) use ($participantTrends) {
            return [$month => $participantTrends->get($month, 0)];
        });

        $checkinData = $last12Months->mapWithKeys(function ($month) use ($checkinTrends) {
            return [$month => $checkinTrends->get($month, 0)];
        });

        $revenueData = $last12Months->mapWithKeys(function ($month) use ($revenueTrends) {
            return [$month => (float) $revenueTrends->get($month, 0)];
        });

        return [
            'months' => $last12Months->toArray(),
            'workshops' => $workshopData->values()->toArray(),
            'participants' => $participantData->values()->toArray(),
            'checkins' => $checkinData->values()->toArray(),
            'revenue' => $revenueData->values()->toArray(),
            'growth_rates' => [
                'workshops' => $this->calculateGrowthRate($workshopData->toArray()),
                'participants' => $this->calculateGrowthRate($participantData->toArray()),
                'checkins' => $this->calculateGrowthRate($checkinData->toArray()),
                'revenue' => $this->calculateGrowthRate($revenueData->toArray()),
            ]
        ];
    }

    /**
     * Get statistics for a specific workshop
     */
    public function getWorkshopDetailedStatistics(Workshop $workshop): array
    {
        $participants = $workshop->participants()->with('ticketType')->get();
        
        return [
            'workshop_info' => [
                'id' => $workshop->id,
                'name' => $workshop->name,
                'description' => $workshop->description,
                'date_time' => $workshop->date_time,
                'location' => $workshop->location,
                'status' => $workshop->status,
            ],
            'participant_stats' => [
                'total_participants' => $participants->count(),
                'checked_in' => $participants->where('is_checked_in', true)->count(),
                'not_checked_in' => $participants->where('is_checked_in', false)->count(),
                'paid_participants' => $participants->where('is_paid', true)->count(),
                'unpaid_participants' => $participants->where('is_paid', false)->count(),
                'checkin_percentage' => $participants->count() > 0 ? 
                    round(($participants->where('is_checked_in', true)->count() / $participants->count()) * 100, 1) : 0,
                'payment_percentage' => $participants->count() > 0 ? 
                    round(($participants->where('is_paid', true)->count() / $participants->count()) * 100, 1) : 0,
            ],
            'revenue_stats' => [
                'total_revenue' => $this->calculateWorkshopRevenue($workshop),
                'potential_revenue' => $this->calculateWorkshopPotentialRevenue($workshop),
                'revenue_realization_rate' => $this->calculateWorkshopRevenueRealizationRate($workshop),
            ],
            'ticket_type_breakdown' => $this->getWorkshopTicketTypeStats($workshop),
            'participant_demographics' => $this->getWorkshopParticipantDemographics($workshop),
            'timeline' => $this->getWorkshopTimeline($workshop),
        ];
    }

    /**
     * Calculate total revenue across all workshops
     */
    public function calculateTotalRevenue(): float
    {
        return Participant::join('ticket_types', 'participants.ticket_type_id', '=', 'ticket_types.id')
            ->where('participants.is_paid', true)
            ->sum('ticket_types.fee');
    }

    /**
     * Calculate potential revenue (if all participants paid)
     */
    public function calculatePotentialRevenue(): float
    {
        return Participant::join('ticket_types', 'participants.ticket_type_id', '=', 'ticket_types.id')
            ->sum('ticket_types.fee');
    }

    /**
     * Calculate revenue for a specific workshop
     */
    public function calculateWorkshopRevenue(Workshop $workshop): float
    {
        return $workshop->participants()
            ->join('ticket_types', 'participants.ticket_type_id', '=', 'ticket_types.id')
            ->where('participants.is_paid', true)
            ->sum('ticket_types.fee');
    }

    /**
     * Calculate potential revenue for a specific workshop
     */
    public function calculateWorkshopPotentialRevenue(Workshop $workshop): float
    {
        return $workshop->participants()
            ->join('ticket_types', 'participants.ticket_type_id', '=', 'ticket_types.id')
            ->sum('ticket_types.fee');
    }

    /**
     * Calculate revenue realization rate for a workshop
     */
    public function calculateWorkshopRevenueRealizationRate(Workshop $workshop): float
    {
        $potential = $this->calculateWorkshopPotentialRevenue($workshop);
        if ($potential == 0) return 0;
        
        $actual = $this->calculateWorkshopRevenue($workshop);
        return round(($actual / $potential) * 100, 1);
    }

    /**
     * Get ticket type statistics for a workshop
     */
    private function getWorkshopTicketTypeStats(Workshop $workshop): array
    {
        return $workshop->ticketTypes()->with('participants')->get()->map(function ($ticketType) {
            $participants = $ticketType->participants;
            return [
                'ticket_type' => $ticketType->name,
                'fee' => $ticketType->fee,
                'total_participants' => $participants->count(),
                'checked_in' => $participants->where('is_checked_in', true)->count(),
                'paid_participants' => $participants->where('is_paid', true)->count(),
                'revenue' => $participants->where('is_paid', true)->count() * $ticketType->fee,
                'potential_revenue' => $participants->count() * $ticketType->fee,
            ];
        })->toArray();
    }

    /**
     * Get participant demographics for a workshop
     */
    private function getWorkshopParticipantDemographics(Workshop $workshop): array
    {
        $participants = $workshop->participants;

        return [
            'companies' => $participants->groupBy('company')->map->count()->sortDesc()->take(10),
            'positions' => $participants->groupBy('position')->map->count()->sortDesc()->take(10),
            'registration_timeline' => $participants->groupBy(function ($p) {
                return $p->created_at->format('Y-m-d');
            })->map->count()->sortKeys(),
        ];
    }

    /**
     * Get timeline of events for a workshop
     */
    private function getWorkshopTimeline(Workshop $workshop): array
    {
        $events = collect();

        // Workshop creation
        $events->push([
            'type' => 'workshop_created',
            'timestamp' => $workshop->created_at,
            'description' => 'Workshop created',
        ]);

        // Participant registrations
        $workshop->participants->each(function ($participant) use ($events) {
            $events->push([
                'type' => 'participant_registered',
                'timestamp' => $participant->created_at,
                'description' => "{$participant->name} registered",
                'participant' => $participant->name,
            ]);
        });

        // Check-ins
        $workshop->participants->where('is_checked_in', true)->each(function ($participant) use ($events) {
            $events->push([
                'type' => 'participant_checked_in',
                'timestamp' => $participant->updated_at,
                'description' => "{$participant->name} checked in",
                'participant' => $participant->name,
            ]);
        });

        return $events->sortBy('timestamp')->values()->toArray();
    }

    /**
     * Calculate growth rate between periods
     */
    private function calculateGrowthRate(array $data): float
    {
        if (count($data) < 2) return 0;

        $current = end($data);
        $previous = prev($data);

        if ($previous == 0) return $current > 0 ? 100 : 0;

        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * Clear statistics cache
     */
    public function clearCache(): void
    {
        Cache::forget('dashboard_statistics');
        Cache::tags(['workshop_stats'])->flush();
    }

    /**
     * Get filtered statistics based on date range
     */
    public function getFilteredStatistics(Carbon $startDate, Carbon $endDate): array
    {
        $workshops = Workshop::whereBetween('date_time', [$startDate, $endDate])
            ->with(['participants.ticketType'])
            ->get();

        $participants = Participant::whereBetween('created_at', [$startDate, $endDate])
            ->with(['workshop', 'ticketType'])
            ->get();

        return [
            'date_range' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'workshops' => [
                'total' => $workshops->count(),
                'by_status' => $workshops->groupBy('status')->map->count(),
            ],
            'participants' => [
                'total' => $participants->count(),
                'checked_in' => $participants->where('is_checked_in', true)->count(),
                'paid' => $participants->where('is_paid', true)->count(),
            ],
            'revenue' => [
                'total' => $participants->where('is_paid', true)->sum(function ($p) {
                    return $p->ticketType->fee;
                }),
                'potential' => $participants->sum(function ($p) {
                    return $p->ticketType->fee;
                }),
            ],
        ];
    }
}