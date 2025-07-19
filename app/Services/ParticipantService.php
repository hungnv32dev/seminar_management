<?php

namespace App\Services;

use App\Models\Participant;
use App\Models\Workshop;
use App\Models\TicketType;
use App\Jobs\ProcessParticipantImportJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ParticipantService
{
    /**
     * Create a new participant with ticket code generation.
     */
    public function createParticipant(array $data): Participant
    {
        return DB::transaction(function () use ($data) {
            // Validate that ticket type belongs to workshop
            if (isset($data['workshop_id']) && isset($data['ticket_type_id'])) {
                $ticketType = TicketType::find($data['ticket_type_id']);
                if (!$ticketType || $ticketType->workshop_id != $data['workshop_id']) {
                    throw new \Exception('Selected ticket type does not belong to the selected workshop.');
                }
            }

            // Check for duplicate email in the same workshop
            if (isset($data['workshop_id']) && isset($data['email'])) {
                $existingParticipant = Participant::where('workshop_id', $data['workshop_id'])
                    ->where('email', $data['email'])
                    ->first();
                
                if ($existingParticipant) {
                    throw new \Exception('This email is already registered for this workshop.');
                }
            }

            $participant = Participant::create($data);

            return $participant->load(['workshop', 'ticketType']);
        });
    }

    /**
     * Update participant with validation.
     */
    public function updateParticipant(Participant $participant, array $data): Participant
    {
        return DB::transaction(function () use ($participant, $data) {
            // Validate that ticket type belongs to workshop if changed
            if (isset($data['workshop_id']) && isset($data['ticket_type_id'])) {
                $ticketType = TicketType::find($data['ticket_type_id']);
                if (!$ticketType || $ticketType->workshop_id != $data['workshop_id']) {
                    throw new \Exception('Selected ticket type does not belong to the selected workshop.');
                }
            }

            // Check for duplicate email in the same workshop (excluding current participant)
            if (isset($data['workshop_id']) && isset($data['email'])) {
                $existingParticipant = Participant::where('workshop_id', $data['workshop_id'])
                    ->where('email', $data['email'])
                    ->where('id', '!=', $participant->id)
                    ->first();
                
                if ($existingParticipant) {
                    throw new \Exception('This email is already registered for this workshop.');
                }
            }

            $participant->update($data);

            return $participant->load(['workshop', 'ticketType']);
        });
    }

    /**
     * Generate and validate ticket code.
     */
    public function generateTicketCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (Participant::where('ticket_code', $code)->exists());

        return $code;
    }

    /**
     * Validate ticket code.
     */
    public function validateTicketCode(string $ticketCode): ?Participant
    {
        return Participant::where('ticket_code', $ticketCode)
            ->with(['workshop', 'ticketType'])
            ->first();
    }

    /**
     * Update payment status for participant.
     */
    public function updatePaymentStatus(Participant $participant, bool $isPaid): Participant
    {
        $participant->update(['is_paid' => $isPaid]);
        
        return $participant;
    }

    /**
     * Update check-in status for participant.
     */
    public function updateCheckinStatus(Participant $participant, bool $isCheckedIn): Participant
    {
        $participant->update(['is_checked_in' => $isCheckedIn]);
        
        return $participant;
    }

    /**
     * Bulk update payment status.
     */
    public function bulkUpdatePaymentStatus(array $participantIds, bool $isPaid): int
    {
        return Participant::whereIn('id', $participantIds)
            ->update(['is_paid' => $isPaid]);
    }

    /**
     * Bulk update check-in status.
     */
    public function bulkUpdateCheckinStatus(array $participantIds, bool $isCheckedIn): int
    {
        return Participant::whereIn('id', $participantIds)
            ->update(['is_checked_in' => $isCheckedIn]);
    }

    /**
     * Get participant statistics for a workshop.
     */
    public function getWorkshopParticipantStatistics(Workshop $workshop): array
    {
        $participants = $workshop->participants();

        return [
            'total_participants' => $participants->count(),
            'paid_participants' => $participants->where('is_paid', true)->count(),
            'unpaid_participants' => $participants->where('is_paid', false)->count(),
            'checked_in_participants' => $participants->where('is_checked_in', true)->count(),
            'not_checked_in_participants' => $participants->where('is_checked_in', false)->count(),
            'total_revenue' => $this->calculateWorkshopRevenue($workshop),
            'potential_revenue' => $this->calculateWorkshopPotentialRevenue($workshop),
        ];
    }

    /**
     * Calculate actual revenue from paid participants.
     */
    public function calculateWorkshopRevenue(Workshop $workshop): float
    {
        return $workshop->participants()
            ->where('is_paid', true)
            ->join('ticket_types', 'participants.ticket_type_id', '=', 'ticket_types.id')
            ->sum('ticket_types.fee');
    }

    /**
     * Calculate potential revenue from all participants.
     */
    public function calculateWorkshopPotentialRevenue(Workshop $workshop): float
    {
        return $workshop->participants()
            ->join('ticket_types', 'participants.ticket_type_id', '=', 'ticket_types.id')
            ->sum('ticket_types.fee');
    }

    /**
     * Get participants by status.
     */
    public function getParticipantsByStatus(Workshop $workshop, string $status): \Illuminate\Database\Eloquent\Collection
    {
        $query = $workshop->participants()->with(['ticketType']);

        switch ($status) {
            case 'paid':
                $query->where('is_paid', true);
                break;
            case 'unpaid':
                $query->where('is_paid', false);
                break;
            case 'checked_in':
                $query->where('is_checked_in', true);
                break;
            case 'not_checked_in':
                $query->where('is_checked_in', false);
                break;
        }

        return $query->get();
    }

    /**
     * Process Excel import.
     */
    public function processImport(string $filePath, int $workshopId, ?int $ticketTypeId = null, ?int $userId = null): string
    {
        // Validate workshop exists
        $workshop = Workshop::findOrFail($workshopId);

        // Validate ticket type belongs to workshop if specified
        if ($ticketTypeId) {
            $ticketType = TicketType::findOrFail($ticketTypeId);
            if ($ticketType->workshop_id !== $workshopId) {
                throw new \Exception('Selected ticket type does not belong to the selected workshop.');
            }
        }

        // Generate import ID for tracking
        $importId = Str::uuid();

        // Dispatch the import job
        ProcessParticipantImportJob::dispatch(
            $filePath,
            $workshopId,
            $ticketTypeId,
            $userId,
            $importId
        );

        return $importId;
    }

    /**
     * Export participants to array for CSV/Excel.
     */
    public function exportParticipants($query): array
    {
        $participants = $query->with(['workshop', 'ticketType'])->get();
        $data = [];

        foreach ($participants as $participant) {
            $data[] = [
                'Name' => $participant->name,
                'Email' => $participant->email,
                'Phone' => $participant->phone,
                'Occupation' => $participant->occupation,
                'Company' => $participant->company,
                'Position' => $participant->position,
                'Address' => $participant->address,
                'Workshop' => $participant->workshop->name,
                'Ticket Type' => $participant->ticketType->name,
                'Ticket Code' => $participant->ticket_code,
                'Fee' => $participant->ticketType->fee,
                'Paid' => $participant->is_paid ? 'Yes' : 'No',
                'Checked In' => $participant->is_checked_in ? 'Yes' : 'No',
                'Registration Date' => $participant->created_at->format('Y-m-d H:i:s'),
            ];
        }

        return $data;
    }

    /**
     * Search participants across workshops.
     */
    public function searchParticipants(string $searchTerm, ?int $workshopId = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = Participant::with(['workshop', 'ticketType'])
            ->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('email', 'like', '%' . $searchTerm . '%')
                  ->orWhere('ticket_code', 'like', '%' . $searchTerm . '%')
                  ->orWhere('phone', 'like', '%' . $searchTerm . '%');
            });

        if ($workshopId) {
            $query->where('workshop_id', $workshopId);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get dashboard statistics for participants.
     */
    public function getDashboardStatistics(): array
    {
        return [
            'total_participants' => Participant::count(),
            'paid_participants' => Participant::where('is_paid', true)->count(),
            'checked_in_participants' => Participant::where('is_checked_in', true)->count(),
            'recent_registrations' => Participant::with(['workshop', 'ticketType'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(),
            'total_revenue' => Participant::join('ticket_types', 'participants.ticket_type_id', '=', 'ticket_types.id')
                ->where('participants.is_paid', true)
                ->sum('ticket_types.fee'),
        ];
    }

    /**
     * Delete participant with validation.
     */
    public function deleteParticipant(Participant $participant): bool
    {
        // Add any business logic for deletion validation here
        // For example, prevent deletion if participant is already checked in
        if ($participant->is_checked_in) {
            throw new \Exception('Cannot delete participant who has already checked in.');
        }

        return $participant->delete();
    }

    /**
     * Duplicate participant to another workshop.
     */
    public function duplicateParticipant(Participant $participant, int $newWorkshopId, ?int $newTicketTypeId = null): Participant
    {
        return DB::transaction(function () use ($participant, $newWorkshopId, $newTicketTypeId) {
            // Validate new workshop exists
            $newWorkshop = Workshop::findOrFail($newWorkshopId);

            // Use default ticket type if not specified
            if (!$newTicketTypeId) {
                $defaultTicketType = $newWorkshop->ticketTypes()->first();
                $newTicketTypeId = $defaultTicketType ? $defaultTicketType->id : null;
            }

            if (!$newTicketTypeId) {
                throw new \Exception('No ticket type available for the target workshop.');
            }

            // Check for duplicate email in target workshop
            $existingParticipant = Participant::where('workshop_id', $newWorkshopId)
                ->where('email', $participant->email)
                ->first();
            
            if ($existingParticipant) {
                throw new \Exception('This email is already registered for the target workshop.');
            }

            // Create duplicate participant
            $newParticipant = $participant->replicate();
            $newParticipant->workshop_id = $newWorkshopId;
            $newParticipant->ticket_type_id = $newTicketTypeId;
            $newParticipant->ticket_code = $this->generateTicketCode();
            $newParticipant->is_paid = false; // Reset payment status
            $newParticipant->is_checked_in = false; // Reset check-in status
            $newParticipant->save();

            return $newParticipant->load(['workshop', 'ticketType']);
        });
    }
}