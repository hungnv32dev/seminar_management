<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CheckInService;
use App\Services\QRCodeService;
use App\Models\Workshop;
use App\Models\TicketType;
use App\Models\Participant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CheckInServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CheckInService $checkInService;
    protected QRCodeService $qrCodeService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->qrCodeService = $this->createMock(QRCodeService::class);
        $this->checkInService = new CheckInService($this->qrCodeService);
    }

    /** @test */
    public function it_can_check_in_participant_by_ticket_code()
    {
        $workshop = Workshop::factory()->create();
        $ticketType = TicketType::factory()->create(['workshop_id' => $workshop->id]);
        $participant = Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id,
            'is_checked_in' => false,
            'ticket_code' => 'TEST1234'
        ]);

        $result = $this->checkInService->checkInByTicketCode('TEST1234');

        $this->assertTrue($result['success']);
        $this->assertEquals('Check-in successful!', $result['message']);
        $this->assertEquals($participant->id, $result['participant']['id']);
        $this->assertTrue($participant->fresh()->is_checked_in);
    }

    /** @test */
    public function it_returns_error_for_invalid_ticket_code()
    {
        $result = $this->checkInService->checkInByTicketCode('INVALID1');

        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid ticket code. Participant not found.', $result['error']);
        $this->assertEquals('not_found', $result['error_type']);
    }

    /** @test */
    public function it_returns_error_for_already_checked_in_participant()
    {
        $workshop = Workshop::factory()->create();
        $ticketType = TicketType::factory()->create(['workshop_id' => $workshop->id]);
        $participant = Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id,
            'is_checked_in' => true,
            'ticket_code' => 'CHECKED1'
        ]);

        $result = $this->checkInService->checkInByTicketCode('CHECKED1');

        $this->assertFalse($result['success']);
        $this->assertEquals('Participant is already checked in.', $result['error']);
        $this->assertEquals('already_checked_in', $result['error_type']);
        $this->assertArrayHasKey('participant', $result);
    }

    /** @test */
    public function it_validates_workshop_id_when_provided()
    {
        $workshop1 = Workshop::factory()->create();
        $workshop2 = Workshop::factory()->create();
        $ticketType = TicketType::factory()->create(['workshop_id' => $workshop1->id]);
        $participant = Participant::factory()->create([
            'workshop_id' => $workshop1->id,
            'ticket_type_id' => $ticketType->id,
            'is_checked_in' => false,
            'ticket_code' => 'WRONG123'
        ]);

        $result = $this->checkInService->checkInByTicketCode('WRONG123', $workshop2->id);

        $this->assertFalse($result['success']);
        $this->assertEquals('This ticket is not valid for the selected workshop.', $result['error']);
        $this->assertEquals('wrong_workshop', $result['error_type']);
    }

    /** @test */
    public function it_can_check_in_participant_by_id()
    {
        $workshop = Workshop::factory()->create();
        $ticketType = TicketType::factory()->create(['workshop_id' => $workshop->id]);
        $participant = Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id,
            'is_checked_in' => false
        ]);

        $result = $this->checkInService->checkInById($participant->id);

        $this->assertTrue($result['success']);
        $this->assertEquals('Check-in successful!', $result['message']);
        $this->assertTrue($participant->fresh()->is_checked_in);
    }

    /** @test */
    public function it_returns_error_when_checking_in_already_checked_in_participant_by_id()
    {
        $workshop = Workshop::factory()->create();
        $ticketType = TicketType::factory()->create(['workshop_id' => $workshop->id]);
        $participant = Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id,
            'is_checked_in' => true
        ]);

        $result = $this->checkInService->checkInById($participant->id);

        $this->assertFalse($result['success']);
        $this->assertEquals('Participant is already checked in.', $result['error']);
        $this->assertEquals('already_checked_in', $result['error_type']);
    }

    /** @test */
    public function it_can_undo_check_in()
    {
        $workshop = Workshop::factory()->create();
        $ticketType = TicketType::factory()->create(['workshop_id' => $workshop->id]);
        $participant = Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id,
            'is_checked_in' => true
        ]);

        $result = $this->checkInService->undoCheckIn($participant);

        $this->assertTrue($result['success']);
        $this->assertEquals('Check-in undone successfully.', $result['message']);
        $this->assertFalse($participant->fresh()->is_checked_in);
    }

    /** @test */
    public function it_returns_error_when_undoing_check_in_for_not_checked_in_participant()
    {
        $workshop = Workshop::factory()->create();
        $ticketType = TicketType::factory()->create(['workshop_id' => $workshop->id]);
        $participant = Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id,
            'is_checked_in' => false
        ]);

        $result = $this->checkInService->undoCheckIn($participant);

        $this->assertFalse($result['success']);
        $this->assertEquals('Participant is not checked in.', $result['error']);
        $this->assertEquals('not_checked_in', $result['error_type']);
    }

    /** @test */
    public function it_can_bulk_check_in_participants()
    {
        $workshop = Workshop::factory()->create();
        $ticketType = TicketType::factory()->create(['workshop_id' => $workshop->id]);
        $participants = Participant::factory()->count(3)->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id,
            'is_checked_in' => false
        ]);

        $participantIds = $participants->pluck('id')->toArray();
        $result = $this->checkInService->bulkCheckIn($participantIds);

        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['checked_in']);
        $this->assertEquals(0, $result['already_checked_in']);
        $this->assertEquals(0, $result['failed']);

        foreach ($participants as $participant) {
            $this->assertTrue($participant->fresh()->is_checked_in);
        }
    }

    /** @test */
    public function it_handles_mixed_status_in_bulk_check_in()
    {
        $workshop = Workshop::factory()->create();
        $ticketType = TicketType::factory()->create(['workshop_id' => $workshop->id]);
        
        $notCheckedIn = Participant::factory()->count(2)->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id,
            'is_checked_in' => false
        ]);
        
        $alreadyCheckedIn = Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id,
            'is_checked_in' => true
        ]);

        $participantIds = $notCheckedIn->pluck('id')->merge([$alreadyCheckedIn->id])->toArray();
        $result = $this->checkInService->bulkCheckIn($participantIds);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['checked_in']);
        $this->assertEquals(1, $result['already_checked_in']);
        $this->assertEquals(0, $result['failed']);
    }

    /** @test */
    public function it_can_get_workshop_statistics()
    {
        $workshop = Workshop::factory()->create();
        $ticketType = TicketType::factory()->create(['workshop_id' => $workshop->id]);
        
        // Create participants with different statuses
        Participant::factory()->count(2)->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id,
            'is_checked_in' => true,
            'is_paid' => true
        ]);
        
        Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id,
            'is_checked_in' => false,
            'is_paid' => true
        ]);
        
        Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id,
            'is_checked_in' => false,
            'is_paid' => false
        ]);

        $stats = $this->checkInService->getWorkshopStatistics($workshop);

        $this->assertEquals(4, $stats['total_participants']);
        $this->assertEquals(2, $stats['checked_in']);
        $this->assertEquals(2, $stats['not_checked_in']);
        $this->assertEquals(3, $stats['paid_participants']);
        $this->assertEquals(1, $stats['unpaid_participants']);
        $this->assertEquals(50.0, $stats['checkin_percentage']);
        $this->assertEquals(75.0, $stats['payment_percentage']);
    }

    /** @test */
    public function it_handles_empty_workshop_statistics()
    {
        $workshop = Workshop::factory()->create();

        $stats = $this->checkInService->getWorkshopStatistics($workshop);

        $this->assertEquals(0, $stats['total_participants']);
        $this->assertEquals(0, $stats['checked_in']);
        $this->assertEquals(0, $stats['not_checked_in']);
        $this->assertEquals(0, $stats['checkin_percentage']);
        $this->assertEquals(0, $stats['payment_percentage']);
    }

    /** @test */
    public function it_can_search_participants()
    {
        $workshop = Workshop::factory()->create();
        $ticketType = TicketType::factory()->create(['workshop_id' => $workshop->id]);
        
        Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id,
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);
        
        Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id,
            'name' => 'Jane Smith',
            'email' => 'jane@example.com'
        ]);

        $result = $this->checkInService->searchParticipants('John');

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['participants']);
        $this->assertEquals('John Doe', $result['participants'][0]['name']);
    }

    /** @test */
    public function it_can_search_participants_by_email()
    {
        $workshop = Workshop::factory()->create();
        $ticketType = TicketType::factory()->create(['workshop_id' => $workshop->id]);
        
        Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id,
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);

        $result = $this->checkInService->searchParticipants('john@example.com');

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['participants']);
        $this->assertEquals('john@example.com', $result['participants'][0]['email']);
    }

    /** @test */
    public function it_can_filter_search_by_workshop()
    {
        $workshop1 = Workshop::factory()->create();
        $workshop2 = Workshop::factory()->create();
        $ticketType1 = TicketType::factory()->create(['workshop_id' => $workshop1->id]);
        $ticketType2 = TicketType::factory()->create(['workshop_id' => $workshop2->id]);
        
        Participant::factory()->create([
            'workshop_id' => $workshop1->id,
            'ticket_type_id' => $ticketType1->id,
            'name' => 'John Doe'
        ]);
        
        Participant::factory()->create([
            'workshop_id' => $workshop2->id,
            'ticket_type_id' => $ticketType2->id,
            'name' => 'John Smith'
        ]);

        $result = $this->checkInService->searchParticipants('John', $workshop1->id);

        $this->assertTrue($result['success']);
        $this->assertCount(1, $result['participants']);
        $this->assertEquals('John Doe', $result['participants'][0]['name']);
    }

    /** @test */
    public function it_can_get_recent_check_ins()
    {
        $workshop = Workshop::factory()->create();
        $ticketType = TicketType::factory()->create(['workshop_id' => $workshop->id]);
        
        // Create participants with different check-in times
        $participant1 = Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id,
            'is_checked_in' => true,
            'updated_at' => Carbon::now()->subMinutes(5)
        ]);
        
        $participant2 = Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id,
            'is_checked_in' => true,
            'updated_at' => Carbon::now()->subMinutes(2)
        ]);
        
        // Not checked in participant
        Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id,
            'is_checked_in' => false
        ]);

        $result = $this->checkInService->getRecentCheckIns(10);

        $this->assertTrue($result['success']);
        $this->assertCount(2, $result['recent_checkins']);
        
        // Should be ordered by most recent first
        $this->assertEquals($participant2->name, $result['recent_checkins'][0]['name']);
        $this->assertEquals($participant1->name, $result['recent_checkins'][1]['name']);
    }

    /** @test */
    public function it_can_validate_simple_qr_code()
    {
        $ticketCode = 'TEST1234';
        $workshop = Workshop::factory()->create();
        $ticketType = TicketType::factory()->create(['workshop_id' => $workshop->id]);
        $participant = Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id,
            'ticket_code' => $ticketCode
        ]);

        $this->qrCodeService->method('validateTicketCode')
            ->with($ticketCode)
            ->willReturn($participant);

        $result = $this->checkInService->validateQRCode($ticketCode);

        $this->assertTrue($result['valid']);
        $this->assertEquals('simple', $result['type']);
        $this->assertEquals($ticketCode, $result['ticket_code']);
    }

    /** @test */
    public function it_can_validate_advanced_qr_code()
    {
        $qrData = json_encode([
            'ticket_code' => 'TEST1234',
            'workshop_id' => 1,
            'participant_id' => 1
        ]);

        $workshop = Workshop::factory()->create();
        $ticketType = TicketType::factory()->create(['workshop_id' => $workshop->id]);
        $participant = Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id,
            'ticket_code' => 'TEST1234'
        ]);

        $this->qrCodeService->method('validateTicketCode')
            ->with('TEST1234')
            ->willReturn($participant);

        $result = $this->checkInService->validateQRCode($qrData);

        $this->assertTrue($result['valid']);
        $this->assertEquals('advanced', $result['type']);
    }

    /** @test */
    public function it_returns_error_for_invalid_qr_code_format()
    {
        $result = $this->checkInService->validateQRCode('invalid-json-{');

        $this->assertFalse($result['valid']);
        $this->assertEquals('Invalid QR code format.', $result['error']);
        $this->assertEquals('invalid_format', $result['error_type']);
    }

    /** @test */
    public function it_logs_successful_check_ins()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Participant checked in successfully', \Mockery::type('array'));

        $workshop = Workshop::factory()->create();
        $ticketType = TicketType::factory()->create(['workshop_id' => $workshop->id]);
        $participant = Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id,
            'is_checked_in' => false
        ]);

        $this->checkInService->checkInById($participant->id);
    }

    /** @test */
    public function it_logs_check_in_undo_operations()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Check-in undone', \Mockery::type('array'));

        $workshop = Workshop::factory()->create();
        $ticketType = TicketType::factory()->create(['workshop_id' => $workshop->id]);
        $participant = Participant::factory()->create([
            'workshop_id' => $workshop->id,
            'ticket_type_id' => $ticketType->id,
            'is_checked_in' => true
        ]);

        $this->checkInService->undoCheckIn($participant);
    }
}