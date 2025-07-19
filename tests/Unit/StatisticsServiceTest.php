<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\StatisticsService;
use App\Models\Workshop;
use App\Models\Participant;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class StatisticsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected StatisticsService $statisticsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->statisticsService = new StatisticsService();
        
        // Clear cache before each test
        Cache::flush();
    }

    /** @test */
    public function it_can_get_overview_statistics()
    {
        // Create test data
        $workshop = Workshop::factory()->create(['status' => 'published']);
        $ticketType = TicketType::factory()->create(['workshop_id' => $workshop->id, 'fee' => 100]);
        
        Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id,
            'is_paid' => true,
            'is_checked_in' => true
        ]);
        
        Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id,
            'is_paid' => false,
            'is_checked_in' => false
        ]);

        $stats = $this->statisticsService->getOverviewStatistics();

        $this->assertArrayHasKey('total_workshops', $stats);
        $this->assertArrayHasKey('total_participants', $stats);
        $this->assertArrayHasKey('checked_in_participants', $stats);
        $this->assertArrayHasKey('total_revenue', $stats);
        $this->assertArrayHasKey('checkin_rate', $stats);
        $this->assertArrayHasKey('payment_rate', $stats);

        $this->assertEquals(1, $stats['total_workshops']);
        $this->assertEquals(2, $stats['total_participants']);
        $this->assertEquals(1, $stats['checked_in_participants']);
        $this->assertEquals(100, $stats['total_revenue']);
        $this->assertEquals(50.0, $stats['checkin_rate']);
        $this->assertEquals(50.0, $stats['payment_rate']);
    }

    /** @test */
    public function it_can_calculate_total_revenue()
    {
        $workshop = Workshop::factory()->create();
        $ticketType1 = TicketType::factory()->create(['workshop_id' => $workshop->id, 'fee' => 100]);
        $ticketType2 = TicketType::factory()->create(['workshop_id' => $workshop->id, 'fee' => 150]);

        // Paid participants
        Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType1->id,
            'is_paid' => true
        ]);
        
        Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType2->id,
            'is_paid' => true
        ]);

        // Unpaid participant
        Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType1->id,
            'is_paid' => false
        ]);

        $totalRevenue = $this->statisticsService->calculateTotalRevenue();
        
        $this->assertEquals(250, $totalRevenue); // 100 + 150
    }

    /** @test */
    public function it_can_calculate_potential_revenue()
    {
        $workshop = Workshop::factory()->create();
        $ticketType = TicketType::factory()->create(['workshop_id' => $workshop->id, 'fee' => 100]);

        // Create 3 participants (2 paid, 1 unpaid)
        Participant::factory()->count(2)->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id,
            'is_paid' => true
        ]);
        
        Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id,
            'is_paid' => false
        ]);

        $potentialRevenue = $this->statisticsService->calculatePotentialRevenue();
        
        $this->assertEquals(300, $potentialRevenue); // 3 * 100
    }

    /** @test */
    public function it_can_calculate_workshop_revenue()
    {
        $workshop1 = Workshop::factory()->create();
        $workshop2 = Workshop::factory()->create();
        
        $ticketType1 = TicketType::factory()->create(['workshop_id' => $workshop1->id, 'fee' => 100]);
        $ticketType2 = TicketType::factory()->create(['workshop_id' => $workshop2->id, 'fee' => 200]);

        // Workshop 1 participants
        Participant::factory()->create([
            'workshop_id' => $workshop1->id,
            'ticket_type_id' => $ticketType1->id,
            'is_paid' => true
        ]);
        
        Participant::factory()->create([
            'workshop_id' => $workshop1->id,
            'ticket_type_id' => $ticketType1->id,
            'is_paid' => false
        ]);

        // Workshop 2 participants
        Participant::factory()->create([
            'workshop_id' => $workshop2->id,
            'ticket_type_id' => $ticketType2->id,
            'is_paid' => true
        ]);

        $workshop1Revenue = $this->statisticsService->calculateWorkshopRevenue($workshop1);
        $workshop2Revenue = $this->statisticsService->calculateWorkshopRevenue($workshop2);

        $this->assertEquals(100, $workshop1Revenue);
        $this->assertEquals(200, $workshop2Revenue);
    }

    /** @test */
    public function it_can_get_workshop_statistics()
    {
        $workshop = Workshop::factory()->create(['status' => 'published']);
        $ticketType = TicketType::factory()->create(['workshop_id' => $workshop->id, 'fee' => 100]);
        
        Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id,
            'is_paid' => true,
            'is_checked_in' => true
        ]);

        $stats = $this->statisticsService->getWorkshopStatistics();

        $this->assertArrayHasKey('workshops', $stats);
        $this->assertArrayHasKey('summary', $stats);
        
        $workshopStats = $stats['workshops'][0];
        $this->assertEquals($workshop->id, $workshopStats['id']);
        $this->assertEquals($workshop->name, $workshopStats['name']);
        $this->assertEquals(1, $workshopStats['total_participants']);
        $this->assertEquals(1, $workshopStats['checked_in']);
        $this->assertEquals(1, $workshopStats['paid_participants']);
        $this->assertEquals(100, $workshopStats['revenue']);
    }

    /** @test */
    public function it_can_get_participant_statistics()
    {
        $workshop = Workshop::factory()->create();
        $ticketType = TicketType::factory()->create(['workshop_id' => $workshop->id]);
        
        Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id,
            'company' => 'Company A',
            'position' => 'Developer',
            'is_checked_in' => true
        ]);
        
        Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id,
            'company' => 'Company A',
            'position' => 'Manager',
            'is_checked_in' => false
        ]);

        $stats = $this->statisticsService->getParticipantStatistics();

        $this->assertArrayHasKey('total_participants', $stats);
        $this->assertArrayHasKey('unique_companies', $stats);
        $this->assertArrayHasKey('unique_positions', $stats);
        $this->assertArrayHasKey('company_distribution', $stats);
        $this->assertArrayHasKey('position_distribution', $stats);
        $this->assertArrayHasKey('status_breakdown', $stats);

        $this->assertEquals(2, $stats['total_participants']);
        $this->assertEquals(1, $stats['unique_companies']);
        $this->assertEquals(2, $stats['unique_positions']);
        $this->assertEquals(1, $stats['status_breakdown']['checked_in']);
        $this->assertEquals(1, $stats['status_breakdown']['not_checked_in']);
    }

    /** @test */
    public function it_can_get_revenue_statistics()
    {
        $workshop = Workshop::factory()->create();
        $ticketType = TicketType::factory()->create(['workshop_id' => $workshop->id, 'fee' => 100]);
        
        Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id,
            'is_paid' => true
        ]);
        
        Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id,
            'is_paid' => false
        ]);

        $stats = $this->statisticsService->getRevenueStatistics();

        $this->assertArrayHasKey('total_revenue', $stats);
        $this->assertArrayHasKey('potential_revenue', $stats);
        $this->assertArrayHasKey('revenue_realization_rate', $stats);
        $this->assertArrayHasKey('revenue_by_workshop', $stats);
        $this->assertArrayHasKey('revenue_by_ticket_type', $stats);
        $this->assertArrayHasKey('payment_summary', $stats);

        $this->assertEquals(100, $stats['total_revenue']);
        $this->assertEquals(200, $stats['potential_revenue']);
        $this->assertEquals(50.0, $stats['revenue_realization_rate']);
    }

    /** @test */
    public function it_can_get_recent_activity()
    {
        $workshop = Workshop::factory()->create();
        $ticketType = TicketType::factory()->create(['workshop_id' => $workshop->id]);
        
        $participant = Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id,
            'is_checked_in' => true
        ]);

        $activities = $this->statisticsService->getRecentActivity(10);

        $this->assertIsArray($activities);
        $this->assertNotEmpty($activities);
        
        // Should contain check-in, registration, and workshop creation activities
        $activityTypes = collect($activities)->pluck('type')->unique();
        $this->assertContains('check_in', $activityTypes);
        $this->assertContains('registration', $activityTypes);
        $this->assertContains('workshop_created', $activityTypes);
    }

    /** @test */
    public function it_can_get_trend_statistics()
    {
        // Create data from different months
        $workshop = Workshop::factory()->create([
            'created_at' => Carbon::now()->subMonth()
        ]);
        
        $ticketType = TicketType::factory()->create(['workshop_id' => $workshop->id, 'fee' => 100]);
        
        Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id,
            'is_paid' => true,
            'is_checked_in' => true,
            'created_at' => Carbon::now()->subMonth()
        ]);

        $trends = $this->statisticsService->getTrendStatistics();

        $this->assertArrayHasKey('months', $trends);
        $this->assertArrayHasKey('workshops', $trends);
        $this->assertArrayHasKey('participants', $trends);
        $this->assertArrayHasKey('checkins', $trends);
        $this->assertArrayHasKey('revenue', $trends);
        $this->assertArrayHasKey('growth_rates', $trends);

        $this->assertCount(12, $trends['months']);
        $this->assertCount(12, $trends['workshops']);
        $this->assertCount(12, $trends['participants']);
    }

    /** @test */
    public function it_can_get_workshop_detailed_statistics()
    {
        $workshop = Workshop::factory()->create();
        $ticketType = TicketType::factory()->create(['workshop_id' => $workshop->id, 'fee' => 100]);
        
        Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id,
            'is_paid' => true,
            'is_checked_in' => true,
            'company' => 'Test Company'
        ]);

        $stats = $this->statisticsService->getWorkshopDetailedStatistics($workshop);

        $this->assertArrayHasKey('workshop_info', $stats);
        $this->assertArrayHasKey('participant_stats', $stats);
        $this->assertArrayHasKey('revenue_stats', $stats);
        $this->assertArrayHasKey('ticket_type_breakdown', $stats);
        $this->assertArrayHasKey('participant_demographics', $stats);
        $this->assertArrayHasKey('timeline', $stats);

        $this->assertEquals($workshop->id, $stats['workshop_info']['id']);
        $this->assertEquals(1, $stats['participant_stats']['total_participants']);
        $this->assertEquals(100, $stats['revenue_stats']['total_revenue']);
    }

    /** @test */
    public function it_can_calculate_revenue_realization_rate()
    {
        $workshop = Workshop::factory()->create();
        $ticketType = TicketType::factory()->create(['workshop_id' => $workshop->id, 'fee' => 100]);
        
        // 2 paid out of 4 participants = 50% realization rate
        Participant::factory()->count(2)->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id,
            'is_paid' => true
        ]);
        
        Participant::factory()->count(2)->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id,
            'is_paid' => false
        ]);

        $rate = $this->statisticsService->calculateWorkshopRevenueRealizationRate($workshop);
        
        $this->assertEquals(50.0, $rate);
    }

    /** @test */
    public function it_handles_zero_potential_revenue_gracefully()
    {
        $workshop = Workshop::factory()->create();
        
        // Workshop with no participants
        $rate = $this->statisticsService->calculateWorkshopRevenueRealizationRate($workshop);
        
        $this->assertEquals(0, $rate);
    }

    /** @test */
    public function it_can_get_filtered_statistics()
    {
        $startDate = Carbon::now()->subDays(30);
        $endDate = Carbon::now();
        
        $workshop = Workshop::factory()->create([
            'date_time' => Carbon::now()->subDays(15)
        ]);
        
        $ticketType = TicketType::factory()->create(['workshop_id' => $workshop->id, 'fee' => 100]);
        
        Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id,
            'is_paid' => true,
            'created_at' => Carbon::now()->subDays(10)
        ]);

        $stats = $this->statisticsService->getFilteredStatistics($startDate, $endDate);

        $this->assertArrayHasKey('date_range', $stats);
        $this->assertArrayHasKey('workshops', $stats);
        $this->assertArrayHasKey('participants', $stats);
        $this->assertArrayHasKey('revenue', $stats);

        $this->assertEquals(1, $stats['workshops']['total']);
        $this->assertEquals(1, $stats['participants']['total']);
        $this->assertEquals(100, $stats['revenue']['total']);
    }

    /** @test */
    public function it_can_clear_cache()
    {
        // Put something in cache
        Cache::put('dashboard_statistics', ['test' => 'data'], 60);
        Cache::tags(['workshop_stats'])->put('test_key', 'test_value', 60);
        
        $this->assertTrue(Cache::has('dashboard_statistics'));
        
        $this->statisticsService->clearCache();
        
        $this->assertFalse(Cache::has('dashboard_statistics'));
    }

    /** @test */
    public function it_caches_dashboard_statistics()
    {
        $workshop = Workshop::factory()->create();
        $ticketType = TicketType::factory()->create(['workshop_id' => $workshop->id]);
        Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id
        ]);

        // First call should cache the result
        $stats1 = $this->statisticsService->getDashboardStatistics();
        
        // Second call should return cached result
        $stats2 = $this->statisticsService->getDashboardStatistics();
        
        $this->assertEquals($stats1, $stats2);
        $this->assertTrue(Cache::has('dashboard_statistics'));
    }
}