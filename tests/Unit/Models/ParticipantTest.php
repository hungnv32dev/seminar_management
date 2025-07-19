<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Participant;
use App\Models\Workshop;
use App\Models\TicketType;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ParticipantTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_fillable_attributes()
    {
        $fillable = [
            'workshop_id',
            'ticket_type_id',
            'name',
            'email',
            'phone',
            'occupation',
            'address',
            'company',
            'position',
            'ticket_code',
            'is_paid',
            'is_checked_in',
        ];

        $participant = new Participant();
        
        $this->assertEquals($fillable, $participant->getFillable());
    }

    /** @test */
    public function it_casts_boolean_attributes()
    {
        $participant = Participant::factory()->create([
            'is_paid' => true,
            'is_checked_in' => false,
        ]);

        $this->assertIsBool($participant->is_paid);
        $this->assertIsBool($participant->is_checked_in);
        $this->assertTrue($participant->is_paid);
        $this->assertFalse($participant->is_checked_in);
    }

    /** @test */
    public function it_belongs_to_workshop()
    {
        $workshop = Workshop::factory()->create();
        $ticketType = TicketType::factory()->create(['workshop_id' => $workshop->id]);
        $participant = Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id
        ]);

        $this->assertInstanceOf(Workshop::class, $participant->workshop);
        $this->assertEquals($workshop->id, $participant->workshop->id);
    }

    /** @test */
    public function it_belongs_to_ticket_type()
    {
        $workshop = Workshop::factory()->create();
        $ticketType = TicketType::factory()->create(['workshop_id' => $workshop->id]);
        $participant = Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id
        ]);

        $this->assertInstanceOf(TicketType::class, $participant->ticketType);
        $this->assertEquals($ticketType->id, $participant->ticketType->id);
    }

    /** @test */
    public function it_can_scope_paid_participants()
    {
        $workshop = Workshop::factory()->create();
        $ticketType = TicketType::factory()->create(['workshop_id' => $workshop->id]);
        
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

        $paidParticipants = Participant::paid()->get();

        $this->assertCount(1, $paidParticipants);
        $this->assertTrue($paidParticipants->first()->is_paid);
    }

    /** @test */
    public function it_can_scope_checked_in_participants()
    {
        $workshop = Workshop::factory()->create();
        $ticketType = TicketType::factory()->create(['workshop_id' => $workshop->id]);
        
        Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id,
            'is_checked_in' => true
        ]);
        Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id,
            'is_checked_in' => false
        ]);

        $checkedInParticipants = Participant::checkedIn()->get();

        $this->assertCount(1, $checkedInParticipants);
        $this->assertTrue($checkedInParticipants->first()->is_checked_in);
    }

    /** @test */
    public function it_generates_ticket_code_on_creation()
    {
        $workshop = Workshop::factory()->create();
        $ticketType = TicketType::factory()->create(['workshop_id' => $workshop->id]);
        
        $participant = Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id,
            'ticket_code' => null // Let it generate automatically
        ]);

        $this->assertNotNull($participant->ticket_code);
        $this->assertEquals(8, strlen($participant->ticket_code));
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{8}$/', $participant->ticket_code);
    }

    /** @test */
    public function it_generates_unique_ticket_codes()
    {
        $workshop = Workshop::factory()->create();
        $ticketType = TicketType::factory()->create(['workshop_id' => $workshop->id]);
        
        $participant1 = Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id,
        ]);
        $participant2 = Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id,
        ]);

        $this->assertNotEquals($participant1->ticket_code, $participant2->ticket_code);
    }

    /** @test */
    public function it_can_manually_set_ticket_code()
    {
        $workshop = Workshop::factory()->create();
        $ticketType = TicketType::factory()->create(['workshop_id' => $workshop->id]);
        
        $participant = Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id,
            'ticket_code' => 'CUSTOM01'
        ]);

        $this->assertEquals('CUSTOM01', $participant->ticket_code);
    }

    /** @test */
    public function it_can_be_created_with_all_attributes()
    {
        $workshop = Workshop::factory()->create();
        $ticketType = TicketType::factory()->create(['workshop_id' => $workshop->id]);
        
        $participantData = [
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'occupation' => 'Developer',
            'address' => '123 Main St',
            'company' => 'Tech Corp',
            'position' => 'Senior Developer',
            'ticket_code' => 'TICKET01',
            'is_paid' => true,
            'is_checked_in' => false,
        ];

        $participant = Participant::create($participantData);

        $this->assertEquals('John Doe', $participant->name);
        $this->assertEquals('john@example.com', $participant->email);
        $this->assertEquals('+1234567890', $participant->phone);
        $this->assertEquals('Developer', $participant->occupation);
        $this->assertEquals('123 Main St', $participant->address);
        $this->assertEquals('Tech Corp', $participant->company);
        $this->assertEquals('Senior Developer', $participant->position);
        $this->assertEquals('TICKET01', $participant->ticket_code);
        $this->assertTrue($participant->is_paid);
        $this->assertFalse($participant->is_checked_in);
    }

    /** @test */
    public function it_can_be_updated()
    {
        $workshop = Workshop::factory()->create();
        $ticketType = TicketType::factory()->create(['workshop_id' => $workshop->id]);
        $participant = Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id,
            'name' => 'Original Name'
        ]);
        
        $participant->update(['name' => 'Updated Name']);

        $this->assertEquals('Updated Name', $participant->fresh()->name);
    }

    /** @test */
    public function it_can_update_payment_status()
    {
        $workshop = Workshop::factory()->create();
        $ticketType = TicketType::factory()->create(['workshop_id' => $workshop->id]);
        $participant = Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id,
            'is_paid' => false
        ]);
        
        $participant->update(['is_paid' => true]);

        $this->assertTrue($participant->fresh()->is_paid);
    }

    /** @test */
    public function it_can_update_check_in_status()
    {
        $workshop = Workshop::factory()->create();
        $ticketType = TicketType::factory()->create(['workshop_id' => $workshop->id]);
        $participant = Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id,
            'is_checked_in' => false
        ]);
        
        $participant->update(['is_checked_in' => true]);

        $this->assertTrue($participant->fresh()->is_checked_in);
    }

    /** @test */
    public function it_can_be_deleted()
    {
        $workshop = Workshop::factory()->create();
        $ticketType = TicketType::factory()->create(['workshop_id' => $workshop->id]);
        $participant = Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id
        ]);
        $participantId = $participant->id;

        $participant->delete();

        $this->assertDatabaseMissing('participants', ['id' => $participantId]);
    }

    /** @test */
    public function it_maintains_relationships_after_updates()
    {
        $workshop = Workshop::factory()->create();
        $ticketType = TicketType::factory()->create(['workshop_id' => $workshop->id]);
        $participant = Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id
        ]);
        
        $participant->update(['name' => 'Updated Name']);

        $this->assertEquals($workshop->id, $participant->fresh()->workshop->id);
        $this->assertEquals($ticketType->id, $participant->fresh()->ticketType->id);
    }

    /** @test */
    public function it_can_find_participant_by_ticket_code()
    {
        $workshop = Workshop::factory()->create();
        $ticketType = TicketType::factory()->create(['workshop_id' => $workshop->id]);
        $participant = Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id,
            'ticket_code' => 'FINDME01'
        ]);

        $foundParticipant = Participant::where('ticket_code', 'FINDME01')->first();

        $this->assertNotNull($foundParticipant);
        $this->assertEquals($participant->id, $foundParticipant->id);
    }

    /** @test */
    public function it_can_filter_participants_by_workshop()
    {
        $workshop1 = Workshop::factory()->create();
        $workshop2 = Workshop::factory()->create();
        $ticketType1 = TicketType::factory()->create(['workshop_id' => $workshop1->id]);
        $ticketType2 = TicketType::factory()->create(['workshop_id' => $workshop2->id]);
        
        Participant::factory()->count(3)->create([
            'workshop_id' => $workshop1->id,
            'ticket_type_id' => $ticketType1->id
        ]);
        Participant::factory()->count(2)->create([
            'workshop_id' => $workshop2->id,
            'ticket_type_id' => $ticketType2->id
        ]);

        $workshop1Participants = Participant::where('workshop_id', $workshop1->id)->get();
        $workshop2Participants = Participant::where('workshop_id', $workshop2->id)->get();

        $this->assertCount(3, $workshop1Participants);
        $this->assertCount(2, $workshop2Participants);
    }

    /** @test */
    public function it_can_filter_participants_by_ticket_type()
    {
        $workshop = Workshop::factory()->create();
        $ticketType1 = TicketType::factory()->create(['workshop_id' => $workshop->id]);
        $ticketType2 = TicketType::factory()->create(['workshop_id' => $workshop->id]);
        
        Participant::factory()->count(3)->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType1->id
        ]);
        Participant::factory()->count(2)->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType2->id
        ]);

        $type1Participants = Participant::where('ticket_type_id', $ticketType1->id)->get();
        $type2Participants = Participant::where('ticket_type_id', $ticketType2->id)->get();

        $this->assertCount(3, $type1Participants);
        $this->assertCount(2, $type2Participants);
    }

    /** @test */
    public function generate_ticket_code_creates_unique_codes()
    {
        $participant = new Participant();
        
        $code1 = $participant->generateTicketCode();
        $code2 = $participant->generateTicketCode();

        $this->assertNotEquals($code1, $code2);
        $this->assertEquals(8, strlen($code1));
        $this->assertEquals(8, strlen($code2));
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{8}$/', $code1);
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{8}$/', $code2);
    }
}