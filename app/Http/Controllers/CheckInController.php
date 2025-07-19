<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use App\Models\Workshop;
use App\Services\QRCodeService;
use App\Services\CheckInService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckInController extends Controller
{
    protected $qrCodeService;
    protected $checkInService;

    public function __construct(QRCodeService $qrCodeService, CheckInService $checkInService)
    {
        $this->qrCodeService = $qrCodeService;
        $this->checkInService = $checkInService;
    }

    /**
     * Show check-in interface.
     */
    public function index(Request $request)
    {
        $workshops = Workshop::with(['participants' => function ($query) {
            $query->orderBy('created_at', 'desc');
        }])
        ->whereIn('status', ['published', 'ongoing'])
        ->orderBy('date_time', 'asc')
        ->get();

        $selectedWorkshop = null;
        if ($request->filled('workshop_id')) {
            $selectedWorkshop = Workshop::with(['participants.ticketType'])
                ->find($request->workshop_id);
        }

        return view('checkin.index', compact('workshops', 'selectedWorkshop'));
    }

    /**
     * Show mobile check-in interface.
     */
    public function mobile(Request $request)
    {
        $workshops = Workshop::with(['participants' => function ($query) {
            $query->orderBy('created_at', 'desc');
        }])
        ->whereIn('status', ['published', 'ongoing'])
        ->orderBy('date_time', 'asc')
        ->get();

        $selectedWorkshop = null;
        if ($request->filled('workshop_id')) {
            $selectedWorkshop = Workshop::with(['participants.ticketType'])
                ->find($request->workshop_id);
        }

        return view('checkin.mobile', compact('workshops', 'selectedWorkshop'));
    }

    /**
     * Process QR code scan for check-in.
     */
    public function scan(Request $request)
    {
        $request->validate([
            'ticket_code' => 'required|string',
            'workshop_id' => 'nullable|exists:workshops,id',
        ]);

        $result = $this->checkInService->checkInByTicketCode(
            $request->ticket_code,
            $request->workshop_id
        );

        $statusCode = $result['success'] ? 200 : 
            ($result['error_type'] === 'not_found' ? 404 : 
            ($result['error_type'] === 'system_error' ? 500 : 400));

        return response()->json($result, $statusCode);
    }

    /**
     * Manual check-in by participant ID.
     */
    public function manualCheckIn(Request $request, Participant $participant)
    {
        $result = $this->checkInService->checkInById($participant->id);

        if ($result['success']) {
            return redirect()->back()
                ->with('success', "Successfully checked in {$participant->name}.");
        } else {
            $messageType = $result['error_type'] === 'already_checked_in' ? 'warning' : 'error';
            return redirect()->back()
                ->with($messageType, $result['error']);
        }
    }

    /**
     * Undo check-in.
     */
    public function undoCheckIn(Request $request, Participant $participant)
    {
        $result = $this->checkInService->undoCheckIn($participant);

        if ($result['success']) {
            return redirect()->back()
                ->with('success', "Successfully undone check-in for {$participant->name}.");
        } else {
            $messageType = $result['error_type'] === 'not_checked_in' ? 'warning' : 'error';
            return redirect()->back()
                ->with($messageType, $result['error']);
        }
    }

    /**
     * Get participant details by ticket code (AJAX).
     */
    public function getParticipant(Request $request)
    {
        $request->validate([
            'ticket_code' => 'required|string',
        ]);

        $participant = Participant::where('ticket_code', $request->ticket_code)
            ->with(['workshop', 'ticketType'])
            ->first();

        if (!$participant) {
            return response()->json([
                'success' => false,
                'error' => 'Participant not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'participant' => [
                'id' => $participant->id,
                'name' => $participant->name,
                'email' => $participant->email,
                'phone' => $participant->phone,
                'company' => $participant->company,
                'position' => $participant->position,
                'workshop' => $participant->workshop->name,
                'ticket_type' => $participant->ticketType->name,
                'ticket_code' => $participant->ticket_code,
                'is_paid' => $participant->is_paid,
                'is_checked_in' => $participant->is_checked_in,
                'payment_status' => $participant->is_paid ? 'Paid' : 'Unpaid',
                'checkin_status' => $participant->is_checked_in ? 'Checked In' : 'Not Checked In',
            ]
        ]);
    }

    /**
     * Get check-in statistics for a workshop.
     */
    public function getStatistics(Workshop $workshop)
    {
        $stats = $this->checkInService->getWorkshopStatistics($workshop);

        return response()->json([
            'success' => true,
            'statistics' => $stats,
        ]);
    }

    /**
     * Bulk check-in participants.
     */
    public function bulkCheckIn(Request $request)
    {
        $request->validate([
            'participant_ids' => 'required|array',
            'participant_ids.*' => 'exists:participants,id'
        ]);

        $result = $this->checkInService->bulkCheckIn($request->participant_ids);

        if ($result['success']) {
            $message = "Successfully checked in {$result['checked_in']} participant(s).";
            if ($result['already_checked_in'] > 0) {
                $message .= " {$result['already_checked_in']} participant(s) were already checked in.";
            }
            if ($result['failed'] > 0) {
                $message .= " {$result['failed']} participant(s) failed to check in.";
            }

            return redirect()->back()->with('success', $message);
        } else {
            return redirect()->back()->with('error', $result['error']);
        }
    }

    /**
     * Export check-in report.
     */
    public function exportReport(Workshop $workshop)
    {
        $participants = $workshop->participants()
            ->with('ticketType')
            ->orderBy('is_checked_in', 'desc')
            ->orderBy('name')
            ->get();

        $filename = "checkin_report_{$workshop->name}_{$workshop->date_time->format('Y-m-d')}.csv";
        $filename = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $filename);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($participants, $workshop) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'Name', 'Email', 'Phone', 'Company', 'Position', 
                'Ticket Type', 'Ticket Code', 'Payment Status', 
                'Check-in Status', 'Check-in Time'
            ]);

            // CSV data
            foreach ($participants as $participant) {
                fputcsv($file, [
                    $participant->name,
                    $participant->email,
                    $participant->phone,
                    $participant->company,
                    $participant->position,
                    $participant->ticketType->name,
                    $participant->ticket_code,
                    $participant->is_paid ? 'Paid' : 'Unpaid',
                    $participant->is_checked_in ? 'Checked In' : 'Not Checked In',
                    $participant->is_checked_in ? $participant->updated_at->format('Y-m-d H:i:s') : '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Search participants for check-in.
     */
    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2',
            'workshop_id' => 'nullable|exists:workshops,id',
        ]);

        $result = $this->checkInService->searchParticipants(
            $request->query,
            $request->workshop_id,
            20
        );

        return response()->json($result);
    }
}
