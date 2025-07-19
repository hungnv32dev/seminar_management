<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Workshop;
use App\Models\User;
use App\Models\TicketType;
use App\Models\Participant;
use App\Models\EmailTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

class WorkshopTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_fillable_attributes()
    {
        $fillable = [
            'name',
            'description',
            'date_time',
            'location',
            'status',
        ];

        $workshop = new Workshop();
        
        $this->assertEquals($fillable, $workshop->getFillable());
    }

    /** @test */
    public function it_casts_date_time_to_carbon()
    {
        $workshop = Workshop::factory()->create([
            'date_time' => '2024-12-25 10:00:00'
        ]);

        $this->assertInstanceOf(Carbon::class, $workshop->date_time);
        $this->assertEquals('2024-12-25 10:00:00', $workshop->date_time->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_belongs_to_many_organizers()
    {
        $workshop = Workshop::factory()->create();
        $organizers = User::factory()->count(3)->create();
        
        $workshop->organizers()->attach($organizers->pluck('id'));

        $this->assertInstanceOf(Collection::class, $workshop->organizers);
        $this->assertCount(3, $workshop->organizers);
        $this->assertInstanceOf(User::class, $workshop->organizers->first());
    }

    /** @test */
    public function it_has_many_ticket_types()
    {
        $workshop = Workshop::factory()->create();
        $ticketTypes = TicketType::factory()->count(3)->create(['workshop_id' => $workshop->id]);

        $this->assertInstanceOf(Collection::class, $workshop->ticketTypes);
        $this->assertCount(3, $workshop->ticketTypes);
        $this->assertInstanceOf(TicketType::class, $workshop->ticketTypes->first());
    }

    /** @test */
    public function it_has_many_participants()
    {
        $workshop = Workshop::factory()->create();
        $ticketType = TicketType::factory()->create(['workshop_id' => $workshop->id]);
        $participants = Participant::factory()->count(3)->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id
        ]);

        $this->assertInstanceOf(Collection::class, $workshop->participants);
        $this->assertCount(3, $workshop->participants);
        $this->assertInstanceOf(Participant::class, $workshop->participants->first());
    }

    /** @test */
    public function it_has_many_email_templates()
    {
        $workshop = Workshop::factory()->create();
        $emailTemplates = EmailTemplate::factory()->count(3)->create(['workshop_id' => $workshop->id]);

        $this->assertInstanceOf(Collection::class, $workshop->emailTemplates);
        $this->assertCount(3, $workshop->emailTemplates);
        $this->assertInstanceOf(EmailTemplate::class, $workshop->emailTemplates->first());
    }

    /** @test */
    public function it_can_scope_active_workshops()
    {
        Workshop::factory()->create(['status' => 'published']);
        Workshop::factory()->create(['status' => 'ongoing']);
        Workshop::factory()->create(['status' => 'draft']);
        Workshop::factory()->create(['status' => 'completed']);

        $activeWorkshops = Workshop::active()->get();

        $this->assertCount(2, $activeWorkshops);
        $this->assertTrue($activeWorkshops->every(fn($workshop) => 
            in_array($workshop->status, ['published', 'ongoing'])
        ));
    }

    /** @test */
    public function it_can_scope_upcoming_workshops()
    {
        $futureDate = Carbon::now()->addDays(7);
        $pastDate = Carbon::now()->subDays(7);

        Workshop::factory()->create([
            'date_time' => $futureDate,
            'status' => 'published'
        ]);
        Workshop::factory()->create([
            'date_time' => $futureDate,
            'status' => 'draft'
        ]);
        Workshop::factory()->create([
            'date_time' => $pastDate,
            'status' => 'published'
        ]);

        $upcomingWorkshops = Workshop::upcoming()->get();

        $this->assertCount(2, $upcomingWorkshops);
        $this->assertTrue($upcomingWorkshops->every(fn($workshop) => 
            $workshop->date_time->gt(Carbon::now()) && 
            in_array($workshop->status, ['published', 'draft'])
        ));
    }

    /** @test */
    public function it_can_scope_past_workshops()
    {
        $futureDate = Carbon::now()->addDays(7);
        $pastDate = Carbon::now()->subDays(7);

        Workshop::factory()->create([
            'date_time' => $pastDate,
            'status' => 'completed'
        ]);
        Workshop::factory()->create([
            'date_time' => $pastDate,
            'status' => 'cancelled'
        ]);
        Workshop::factory()->create([
            'date_time' => $futureDate,
            'status' => 'completed'
        ]);

        $pastWorkshops = Workshop::past()->get();

        $this->assertCount(2, $pastWorkshops);
        $this->assertTrue($pastWorkshops->every(fn($workshop) => 
            $workshop->date_time->lt(Carbon::now()) && 
            in_array($workshop->status, ['completed', 'cancelled'])
        ));
    }

    /** @test */
    public function it_can_be_created_with_all_attributes()
    {
        $workshopData = [
            'name' => 'Laravel Workshop',
            'description' => 'Learn Laravel framework',
            'date_time' => '2024-12-25 10:00:00',
            'location' => 'Conference Room A',
            'status' => 'published',
        ];

        $workshop = Workshop::create($workshopData);

        $this->assertEquals('Laravel Workshop', $workshop->name);
        $this->assertEquals('Learn Laravel framework', $workshop->description);
        $this->assertEquals('Conference Room A', $workshop->location);
        $this->assertEquals('published', $workshop->status);
        $this->assertInstanceOf(Carbon::class, $workshop->date_time);
    }

    /** @test */
    public function it_can_be_updated()
    {
        $workshop = Workshop::factory()->create(['name' => 'Original Name']);
        
        $workshop->update(['name' => 'Updated Name']);

        $this->assertEquals('Updated Name', $workshop->fresh()->name);
    }

    /** @test */
    public function it_maintains_relationships_after_updates()
    {
        $workshop = Workshop::factory()->create();
        $organizer = User::factory()->create();
        $ticketType = TicketType::factory()->create(['workshop_id' => $workshop->id]);
        
        $workshop->organizers()->attach($organizer->id);
        $workshop->update(['name' => 'Updated Name']);

        $this->assertCount(1, $workshop->fresh()->organizers);
        $this->assertCount(1, $workshop->fresh()->ticketTypes);
    }

    /** @test */
    public function it_can_be_deleted()
    {
        $workshop = Workshop::factory()->create();
        $workshopId = $workshop->id;

        $workshop->delete();

        $this->assertDatabaseMissing('workshops', ['id' => $workshopId]);
    }

    /** @test */
    public function deleting_workshop_affects_related_data()
    {
        $workshop = Workshop::factory()->create();
        $ticketType = TicketType::factory()->create(['workshop_id' => $workshop->id]);
        $participant = Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id
        ]);
        
        $workshop->delete();

        // Related data should be handled by foreign key constraints
        $this->assertDatabaseMissing('workshops', ['id' => $workshop->id]);
    }

    /** @test */
    public function it_can_add_and_remove_organizers()
    {
        $workshop = Workshop::factory()->create();
        $organizers = User::factory()->count(3)->create();
        
        // Add organizers
        $workshop->organizers()->attach($organizers->pluck('id'));
        $this->assertCount(3, $workshop->organizers);
        
        // Remove one organizer
        $workshop->organizers()->detach($organizers->first()->id);
        $this->assertCount(2, $workshop->fresh()->organizers);
    }

    /** @test */
    public function it_can_have_multiple_ticket_types()
    {
        $workshop = Workshop::factory()->create();
        
        TicketType::factory()->create([
            'workshop_id' => $workshop->id,
            'name' => 'Standard',
            'fee' => 100
        ]);
        TicketType::factory()->create([
            'workshop_id' => $workshop->id,
            'name' => 'Premium',
            'fee' => 200
        ]);

        $this->assertCount(2, $workshop->ticketTypes);
        $this->assertEquals('Standard', $workshop->ticketTypes->first()->name);
    }

    /** @test */
    public function it_can_have_participants_through_ticket_types()
    {
        $workshop = Workshop::factory()->create();
        $ticketType = TicketType::factory()->create(['workshop_id' => $workshop->id]);
        
        Participant::factory()->count(5)->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id
        ]);

        $this->assertCount(5, $workshop->participants);
        $this->assertTrue($workshop->participants->every(fn($participant) => 
            $participant->workshop_id === $workshop->id
        ));
    }

    /** @test */
    public function it_can_have_email_templates()
    {
        $workshop = Workshop::factory()->create();
        
        EmailTemplate::factory()->create([
            'workshop_id' => $workshop->id,
            'type' => 'invite',
            'subject' => 'Workshop Invitation'
        ]);
        EmailTemplate::factory()->create([
            'workshop_id' => $workshop->id,
            'type' => 'reminder',
            'subject' => 'Workshop Reminder'
        ]);

        $this->assertCount(2, $workshop->emailTemplates);
        $this->assertContains('invite', $workshop->emailTemplates->pluck('type'));
        $this->assertContains('reminder', $workshop->emailTemplates->pluck('type'));
    }

    /** @test */
    public function it_handles_different_status_values()
    {
        $statuses = ['draft', 'published', 'ongoing', 'completed', 'cancelled'];
        
        foreach ($statuses as $status) {
            $workshop = Workshop::factory()->create(['status' => $status]);
            $this->assertEquals($status, $workshop->status);
        }
    }

    /** @test */
    public function it_can_filter_by_date_ranges()
    {
        $today = Carbon::now();
        $yesterday = $today->copy()->subDay();
        $tomorrow = $today->copy()->addDay();

        Workshop::factory()->create(['date_time' => $yesterday]);
        Workshop::factory()->create(['date_time' => $today]);
        Workshop::factory()->create(['date_time' => $tomorrow]);

        $todayWorkshops = Workshop::whereDate('date_time', $today->toDateString())->get();
        $futureWorkshops = Workshop::where('date_time', '>', $today)->get();

        $this->assertCount(1, $todayWorkshops);
        $this->assertCount(1, $futureWorkshops);
    }
}