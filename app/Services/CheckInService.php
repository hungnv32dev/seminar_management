<?php

namespace App\Services;

use App\Models\Participant;
use App\Models\Workshop;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CheckInService
{
    protected $qrCodeService;

    public function __construct(QRCodeService $qrCodeService)
    {
        $this->qrCodeService = $qrCodeService;
    }

    /**
     * Process check-in by ticket code.
     */
    public function checkInByTicketCode(string $ticketCode, ?int $workshopId = null): array
    {
        try {
            // Find participant by ticket code
            $participant = Participant::where('ticket_code', $ticketCode)
                ->with(['workshop', 'ticketType'])
                ->first();

            if (!$participant) {
                return [
                    'success' => false,
                    'error' => 'Invalid ticket code. Participant not found.',
                    'error_type' => 'not_found'
                ];
            }

            // Check if workshop matches (if specified)
            if ($workshopId && $participant->workshop_id != $workshopId) {
                return [
                    'success' => false,
                    'error' => 'This ticket is not valid for the selected workshop.',
                    'error_type' => 'wrong_workshop',
                    'participant' => [
                        'name' => $participant->name,
                        'workshop' => $participant->workshop->name,
                    ]
                ];
            }

            // Check if already checked in
            if ($participant->is_checked_in) {
                return [
                    'success' => false,
                    'error' => 'Participant is already checked in.',
                    'error_type' => 'already_checked_in',
                    'participant' => $this->formatParticipantData($participant)
                ];
            }

            // Perform check-in
            return $this->performCheckIn($participant);

        } catch (\Exception $e) {
            Log::error('Check-in by ticket code failed', [
                'ticket_code' => $ticketCode,
                'workshop_id' => $workshopId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Check-in failed. Please try again.',
                'error_type' => 'system_error'
            ];
        }
    }

    /**
     * Process check-in by participant ID.
     */
    public function checkInById(int $participantId): array
    {
        try {
            $participant = Participant::with(['workshop', 'ticketType'])
                ->findOrFail($participantId);

            if ($participant->is_checked_in) {
                return [
                    'success' => false,
                    'error' => 'Participant is already checked in.',
                    'error_type' => 'already_checked_in',
                    'participant' => $this->formatParticipantData($participant)
                ];
            }

            return $this->performCheckIn($participant);

        } catch (\Exception $e) {
            Log::error('Check-in by ID failed', [
                'participant_id' => $participantId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Check-in failed. Please try again.',
                'error_type' => 'system_error'
            ];
        }
    }

    /**
     * Perform the actual check-in process.
     */
    private function performCheckIn(Participant $participant): array
    {
        return DB::transaction(function () use ($participant) {
            $participant->update(['is_checked_in' => true]);

            Log::info('Participant checked in successfully', [
                'participant_id' => $participant->id,
                'participant_name' => $participant->name,
                'workshop_id' => $participant->workshop_id,
                'workshop_name' => $participant->workshop->name,
                'ticket_code' => $participant->ticket_code,
                'checked_in_at' => now(),
            ]);

            return [
                'success' => true,
                'message' => 'Check-in successful!',
                'participant' => $this->formatParticipantData($participant, true)
            ];
        });
    }

    /**
     * Undo check-in for a participant.
     */
    public function undoCheckIn(Participant $participant): array
    {
        try {
            if (!$participant->is_checked_in) {
                return [
                    'success' => false,
                    'error' => 'Participant is not checked in.',
                    'error_type' => 'not_checked_in'
                ];
            }

            $participant->update(['is_checked_in' => false]);

            Log::info('Check-in undone', [
                'participant_id' => $participant->id,
                'participant_name' => $participant->name,
                'workshop_name' => $participant->workshop->name,
                'undone_at' => now(),
            ]);

            return [
                'success' => true,
                'message' => 'Check-in undone successfully.',
                'participant' => $this->formatParticipantData($participant)
            ];

        } catch (\Exception $e) {
            Log::error('Undo check-in failed', [
                'participant_id' => $participant->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Undo check-in failed. Please try again.',
                'error_type' => 'system_error'
            ];
        }
    }

    /**
     * Bulk check-in participants.
     */
    public function bulkCheckIn(array $participantIds): array
    {
        try {
            return DB::transaction(function () use ($participantIds) {
                $participants = Participant::whereIn('id', $participantIds)
                    ->with(['workshop', 'ticketType'])
                    ->get();

                $results = [
                    'success' => true,
                    'checked_in' => 0,
                    'already_checked_in' => 0,
                    'failed' => 0,
                    'participants' => [],
                    'errors' => []
                ];

                foreach ($participants as $participant) {
                    try {
                        if ($participant->is_checked_in) {
                            $results['already_checked_in']++;
                        } else {
                            $participant->update(['is_checked_in' => true]);
                            $results['checked_in']++;
                            $results['participants'][] = $this->formatParticipantData($participant, true);
                        }
                    } catch (\Exception $e) {
                        $results['failed']++;
                        $results['errors'][] = "Failed to check in {$participant->name}: " . $e->getMessage();
                    }
                }

                Log::info('Bulk check-in completed', [
                    'total_requested' => count($participantIds),
                    'checked_in' => $results['checked_in'],
                    'already_checked_in' => $results['already_checked_in'],
                    'failed' => $results['failed'],
                ]);

                return $results;
            });

        } catch (\Exception $e) {
            Log::error('Bulk check-in failed', [
                'participant_ids' => $participantIds,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Bulk check-in failed. Please try again.',
                'error_type' => 'system_error'
            ];
        }
    }

    /**
     * Get check-in statistics for a workshop.
     */
    public function getWorkshopStatistics(Workshop $workshop): array
    {
        $participants = $workshop->participants();
        
        $stats = [
            'total_participants' => $participants->count(),
            'checked_in' => $participants->where('is_checked_in', true)->count(),
            'not_checked_in' => $participants->where('is_checked_in', false)->count(),
            'paid_participants' => $participants->where('is_paid', true)->count(),
            'unpaid_participants' => $participants->where('is_paid', false)->count(),
            'checked_in_paid' => $participants->where('is_checked_in', true)->where('is_paid', true)->count(),
            'checked_in_unpaid' => $participants->where('is_checked_in', true)->where('is_paid', false)->count(),
        ];

        $stats['checkin_percentage'] = $stats['total_participants'] > 0 
            ? round(($stats['checked_in'] / $stats['total_participants']) * 100, 1) 
            : 0;

        $stats['payment_percentage'] = $stats['total_participants'] > 0 
            ? round(($stats['paid_participants'] / $stats['total_participants']) * 100, 1) 
            : 0;

        return $stats;
    }

    /**
     * Get check-in statistics for all workshops.
     */
    public function getAllWorkshopsStatistics(): array
    {
        $workshops = Workshop::with('participants')->get();
        $overallStats = [
            'total_workshops' => $workshops->count(),
            'total_participants' => 0,
            'total_checked_in' => 0,
            'total_paid' => 0,
            'workshop_stats' => []
        ];

        foreach ($workshops as $workshop) {
            $stats = $this->getWorkshopStatistics($workshop);
            $stats['workshop_name'] = $workshop->name;
            $stats['workshop_date'] = $workshop->date_time->format('Y-m-d H:i');
            $stats['workshop_status'] = $workshop->status;
            
            $overallStats['workshop_stats'][] = $stats;
            $overallStats['total_participants'] += $stats['total_participants'];
            $overallStats['total_checked_in'] += $stats['checked_in'];
            $overallStats['total_paid'] += $stats['paid_participants'];
        }

        $overallStats['overall_checkin_percentage'] = $overallStats['total_participants'] > 0 
            ? round(($overallStats['total_checked_in'] / $overallStats['total_participants']) * 100, 1) 
            : 0;

        $overallStats['overall_payment_percentage'] = $overallStats['total_participants'] > 0 
            ? round(($overallStats['total_paid'] / $overallStats['total_participants']) * 100, 1) 
            : 0;

        return $overallStats;
    }

    /**
     * Search participants for check-in.
     */
    public function searchParticipants(string $query, ?int $workshopId = null, int $limit = 20): array
    {
        try {
            $searchQuery = Participant::with(['workshop', 'ticketType'])
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', '%' . $query . '%')
                      ->orWhere('email', 'like', '%' . $query . '%')
                      ->orWhere('ticket_code', 'like', '%' . $query . '%')
                      ->orWhere('phone', 'like', '%' . $query . '%');
                });

            if ($workshopId) {
                $searchQuery->where('workshop_id', $workshopId);
            }

            $participants = $searchQuery->limit($limit)->get();

            return [
                'success' => true,
                'participants' => $participants->map(function ($participant) {
                    return $this->formatParticipantData($participant);
                })->toArray(),
                'total_found' => $participants->count()
            ];

        } catch (\Exception $e) {
            Log::error('Participant search failed', [
                'query' => $query,
                'workshop_id' => $workshopId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Search failed. Please try again.',
                'participants' => []
            ];
        }
    }

    /**
     * Validate QR code data.
     */
    public function validateQRCode(string $qrData): array
    {
        try {
            // Try to decode as JSON first (for advanced QR codes)
            $decodedData = json_decode($qrData, true);
            
            if ($decodedData && isset($decodedData['ticket_code'])) {
                // Advanced QR code with JSON data
                return $this->validateAdvancedQRCode($decodedData);
            } else {
                // Simple QR code with just ticket code
                return $this->validateSimpleQRCode($qrData);
            }

        } catch (\Exception $e) {
            Log::error('QR code validation failed', [
                'qr_data' => $qrData,
                'error' => $e->getMessage(),
            ]);

            return [
                'valid' => false,
                'error' => 'Invalid QR code format.',
                'error_type' => 'invalid_format'
            ];
        }
    }

    /**
     * Validate simple QR code (just ticket code).
     */
    private function validateSimpleQRCode(string $ticketCode): array
    {
        $participant = $this->qrCodeService->validateTicketCode($ticketCode);
        
        if (!$participant) {
            return [
                'valid' => false,
                'error' => 'Invalid ticket code.',
                'error_type' => 'not_found'
            ];
        }

        return [
            'valid' => true,
            'type' => 'simple',
            'ticket_code' => $ticketCode,
            'participant' => $this->formatParticipantData($participant)
        ];
    }

    /**
     * Validate advanced QR code (JSON data).
     */
    private function validateAdvancedQRCode(array $data): array
    {
        if (!isset($data['ticket_code'])) {
            return [
                'valid' => false,
                'error' => 'Missing ticket code in QR data.',
                'error_type' => 'missing_ticket_code'
            ];
        }

        $participant = $this->qrCodeService->validateTicketCode($data['ticket_code']);
        
        if (!$participant) {
            return [
                'valid' => false,
                'error' => 'Invalid ticket code.',
                'error_type' => 'not_found'
            ];
        }

        // Additional validation for advanced QR codes
        if (isset($data['workshop_id']) && $participant->workshop_id != $data['workshop_id']) {
            return [
                'valid' => false,
                'error' => 'Workshop mismatch in QR code.',
                'error_type' => 'workshop_mismatch'
            ];
        }

        if (isset($data['participant_id']) && $participant->id != $data['participant_id']) {
            return [
                'valid' => false,
                'error' => 'Participant mismatch in QR code.',
                'error_type' => 'participant_mismatch'
            ];
        }

        return [
            'valid' => true,
            'type' => 'advanced',
            'data' => $data,
            'participant' => $this->formatParticipantData($participant)
        ];
    }

    /**
     * Format participant data for API responses.
     */
    private function formatParticipantData(Participant $participant, bool $includeCheckInTime = false): array
    {
        $data = [
            'id' => $participant->id,
            'name' => $participant->name,
            'email' => $participant->email,
            'phone' => $participant->phone,
            'company' => $participant->company,
            'position' => $participant->position,
            'occupation' => $participant->occupation,
            'workshop' => $participant->workshop->name,
            'workshop_id' => $participant->workshop_id,
            'ticket_type' => $participant->ticketType->name,
            'ticket_code' => $participant->ticket_code,
            'is_paid' => $participant->is_paid,
            'is_checked_in' => $participant->is_checked_in,
            'payment_status' => $participant->is_paid ? 'Paid' : 'Unpaid',
            'checkin_status' => $participant->is_checked_in ? 'Checked In' : 'Not Checked In',
        ];

        if ($includeCheckInTime && $participant->is_checked_in) {
            $data['checked_in_at'] = now()->format('Y-m-d H:i:s');
        }

        return $data;
    }

    /**
     * Get recent check-ins.
     */
    public function getRecentCheckIns(int $limit = 10, ?int $workshopId = null): array
    {
        try {
            $query = Participant::with(['workshop', 'ticketType'])
                ->where('is_checked_in', true)
                ->orderBy('updated_at', 'desc');

            if ($workshopId) {
                $query->where('workshop_id', $workshopId);
            }

            $participants = $query->limit($limit)->get();

            return [
                'success' => true,
                'recent_checkins' => $participants->map(function ($participant) {
                    $data = $this->formatParticipantData($participant);
                    $data['checked_in_at'] = $participant->updated_at->format('Y-m-d H:i:s');
                    return $data;
                })->toArray()
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get recent check-ins', [
                'error' => $e->getMessage(),
                'workshop_id' => $workshopId,
            ]);

            return [
                'success' => false,
                'error' => 'Failed to get recent check-ins.',
                'recent_checkins' => []
            ];
        }
    }

    /**
     * Export check-in data.
     */
    public function exportCheckInData(Workshop $workshop): array
    {
        $participants = $workshop->participants()
            ->with('ticketType')
            ->orderBy('is_checked_in', 'desc')
            ->orderBy('name')
            ->get();

        $data = [];
        foreach ($participants as $participant) {
            $data[] = [
                'Name' => $participant->name,
                'Email' => $participant->email,
                'Phone' => $participant->phone,
                'Company' => $participant->company,
                'Position' => $participant->position,
                'Ticket Type' => $participant->ticketType->name,
                'Ticket Code' => $participant->ticket_code,
                'Payment Status' => $participant->is_paid ? 'Paid' : 'Unpaid',
                'Check-in Status' => $participant->is_checked_in ? 'Checked In' : 'Not Checked In',
                'Check-in Time' => $participant->is_checked_in ? $participant->updated_at->format('Y-m-d H:i:s') : '',
            ];
        }

        return $data;
    }
}