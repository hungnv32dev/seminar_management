<?php

namespace App\Services;

use App\Models\Workshop;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WorkshopService
{
    /**
     * Create a new workshop with organizers.
     */
    public function createWorkshop(array $data, array $organizerIds = []): Workshop
    {
        return DB::transaction(function () use ($data, $organizerIds) {
            $workshop = Workshop::create($data);

            if (!empty($organizerIds)) {
                $workshop->organizers()->sync($organizerIds);
            }

            return $workshop->load('organizers');
        });
    }

    /**
     * Update workshop with organizers.
     */
    public function updateWorkshop(Workshop $workshop, array $data, array $organizerIds = []): Workshop
    {
        return DB::transaction(function () use ($workshop, $data, $organizerIds) {
            $workshop->update($data);

            $workshop->organizers()->sync($organizerIds);

            return $workshop->load('organizers');
        });
    }

    /**
     * Delete workshop with dependency checks.
     */
    public function deleteWorkshop(Workshop $workshop): bool
    {
        // Check if workshop has participants
        if ($workshop->participants()->count() > 0) {
            throw new \Exception('Cannot delete workshop that has participants registered.');
        }

        // Check if workshop has ticket types
        if ($workshop->ticketTypes()->count() > 0) {
            throw new \Exception('Cannot delete workshop that has ticket types. Please delete ticket types first.');
        }

        return $workshop->delete();
    }

    /**
     * Update workshop status with validation.
     */
    public function updateStatus(Workshop $workshop, string $status): Workshop
    {
        $validStatuses = ['draft', 'published', 'ongoing', 'completed', 'cancelled'];
        
        if (!in_array($status, $validStatuses)) {
            throw new \InvalidArgumentException('Invalid workshop status.');
        }

        // Business logic for status transitions
        $this->validateStatusTransition($workshop, $status);

        $workshop->update(['status' => $status]);

        return $workshop;
    }

    /**
     * Assign organizers to workshop.
     */
    public function assignOrganizers(Workshop $workshop, array $userIds): Workshop
    {
        // Validate that all users exist and are active
        $users = User::whereIn('id', $userIds)->active()->get();
        
        if ($users->count() !== count($userIds)) {
            throw new \Exception('Some selected users are not found or inactive.');
        }

        $workshop->organizers()->sync($userIds);

        return $workshop->load('organizers');
    }

    /**
     * Remove organizer from workshop.
     */
    public function removeOrganizer(Workshop $workshop, int $userId): Workshop
    {
        $workshop->organizers()->detach($userId);

        return $workshop->load('organizers');
    }

    /**
     * Get workshop statistics.
     */
    public function getWorkshopStatistics(Workshop $workshop): array
    {
        $participants = $workshop->participants();

        return [
            'total_participants' => $participants->count(),
            'checked_in_participants' => $participants->where('is_checked_in', true)->count(),
            'paid_participants' => $participants->where('is_paid', true)->count(),
            'unpaid_participants' => $participants->where('is_paid', false)->count(),
            'total_revenue' => $this->calculateTotalRevenue($workshop),
            'potential_revenue' => $this->calculatePotentialRevenue($workshop),
            'ticket_types_count' => $workshop->ticketTypes()->count(),
            'organizers_count' => $workshop->organizers()->count(),
        ];
    }

    /**
     * Calculate total revenue from paid participants.
     */
    public function calculateTotalRevenue(Workshop $workshop): float
    {
        return $workshop->participants()
            ->where('is_paid', true)
            ->join('ticket_types', 'participants.ticket_type_id', '=', 'ticket_types.id')
            ->sum('ticket_types.fee');
    }

    /**
     * Calculate potential revenue from all participants.
     */
    public function calculatePotentialRevenue(Workshop $workshop): float
    {
        return $workshop->participants()
            ->join('ticket_types', 'participants.ticket_type_id', '=', 'ticket_types.id')
            ->sum('ticket_types.fee');
    }

    /**
     * Get workshops by status.
     */
    public function getWorkshopsByStatus(string $status)
    {
        return Workshop::where('status', $status)
            ->with(['organizers', 'participants'])
            ->orderBy('date_time', 'desc')
            ->get();
    }

    /**
     * Get upcoming workshops.
     */
    public function getUpcomingWorkshops(int $limit = 10)
    {
        return Workshop::upcoming()
            ->with(['organizers', 'participants'])
            ->orderBy('date_time', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get past workshops.
     */
    public function getPastWorkshops(int $limit = 10)
    {
        return Workshop::past()
            ->with(['organizers', 'participants'])
            ->orderBy('date_time', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Duplicate workshop.
     */
    public function duplicateWorkshop(Workshop $originalWorkshop, array $newData = []): Workshop
    {
        return DB::transaction(function () use ($originalWorkshop, $newData) {
            $workshopData = $originalWorkshop->toArray();
            
            // Remove fields that shouldn't be duplicated
            unset($workshopData['id'], $workshopData['created_at'], $workshopData['updated_at']);
            
            // Apply new data
            $workshopData = array_merge($workshopData, $newData);
            
            // Set default values for duplicated workshop
            $workshopData['status'] = 'draft';
            $workshopData['name'] = $workshopData['name'] . ' (Copy)';
            
            $newWorkshop = Workshop::create($workshopData);

            // Copy organizers
            $organizerIds = $originalWorkshop->organizers()->pluck('users.id')->toArray();
            if (!empty($organizerIds)) {
                $newWorkshop->organizers()->sync($organizerIds);
            }

            // Copy ticket types
            foreach ($originalWorkshop->ticketTypes as $ticketType) {
                $newWorkshop->ticketTypes()->create([
                    'name' => $ticketType->name,
                    'fee' => $ticketType->fee,
                ]);
            }

            // Copy email templates
            foreach ($originalWorkshop->emailTemplates as $template) {
                $newWorkshop->emailTemplates()->create([
                    'type' => $template->type,
                    'subject' => $template->subject,
                    'content' => $template->content,
                ]);
            }

            return $newWorkshop->load(['organizers', 'ticketTypes', 'emailTemplates']);
        });
    }

    /**
     * Validate status transition.
     */
    private function validateStatusTransition(Workshop $workshop, string $newStatus): void
    {
        $currentStatus = $workshop->status;

        // Define allowed transitions
        $allowedTransitions = [
            'draft' => ['published', 'cancelled'],
            'published' => ['ongoing', 'cancelled'],
            'ongoing' => ['completed', 'cancelled'],
            'completed' => [], // Cannot change from completed
            'cancelled' => ['draft'], // Can restart from cancelled
        ];

        if (!isset($allowedTransitions[$currentStatus]) || 
            !in_array($newStatus, $allowedTransitions[$currentStatus])) {
            throw new \Exception("Cannot change status from {$currentStatus} to {$newStatus}.");
        }

        // Additional business rules
        if ($newStatus === 'published' && $workshop->date_time <= Carbon::now()) {
            throw new \Exception('Cannot publish workshop with past date.');
        }

        if ($newStatus === 'ongoing' && $workshop->date_time > Carbon::now()->addHours(2)) {
            throw new \Exception('Cannot mark workshop as ongoing if it starts more than 2 hours from now.');
        }
    }

    /**
     * Get workshop dashboard data.
     */
    public function getDashboardData(): array
    {
        return [
            'total_workshops' => Workshop::count(),
            'active_workshops' => Workshop::active()->count(),
            'upcoming_workshops' => Workshop::upcoming()->count(),
            'completed_workshops' => Workshop::where('status', 'completed')->count(),
            'draft_workshops' => Workshop::where('status', 'draft')->count(),
            'recent_workshops' => Workshop::with(['organizers', 'participants'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
        ];
    }
}