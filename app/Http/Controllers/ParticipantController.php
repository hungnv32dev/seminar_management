<?php

namespace App\Http\Controllers;

use App\Http\Requests\ParticipantRequest;
use App\Models\Participant;
use App\Models\Workshop;
use App\Models\TicketType;
use App\Services\ParticipantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ParticipantController extends Controller
{
    protected $participantService;

    public function __construct(ParticipantService $participantService)
    {
        $this->participantService = $participantService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Participant::with(['workshop', 'ticketType']);

        // Filter by workshop
        if ($request->filled('workshop_id')) {
            $query->where('workshop_id', $request->workshop_id);
        }

        // Filter by ticket type
        if ($request->filled('ticket_type_id')) {
            $query->where('ticket_type_id', $request->ticket_type_id);
        }

        // Filter by payment status
        if ($request->filled('payment_status')) {
            $query->where('is_paid', $request->payment_status === 'paid');
        }

        // Filter by check-in status
        if ($request->filled('checkin_status')) {
            $query->where('is_checked_in', $request->checkin_status === 'checked_in');
        }

        // Search by name or email
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('ticket_code', 'like', '%' . $request->search . '%');
            });
        }

        $participants = $query->orderBy('created_at', 'desc')->paginate(20);
        
        // Get filter options
        $workshops = Workshop::orderBy('name')->get();
        $ticketTypes = TicketType::with('workshop')->orderBy('name')->get();

        return view('participants.index', compact('participants', 'workshops', 'ticketTypes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $workshops = Workshop::with('ticketTypes')->orderBy('name')->get();
        $selectedWorkshop = $request->workshop_id ? Workshop::find($request->workshop_id) : null;
        $ticketTypes = $selectedWorkshop ? $selectedWorkshop->ticketTypes : collect();

        return view('participants.create', compact('workshops', 'selectedWorkshop', 'ticketTypes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ParticipantRequest $request)
    {
        try {
            $participant = $this->participantService->createParticipant($request->validated());

            return redirect()->route('participants.index', ['workshop_id' => $participant->workshop_id])
                ->with('success', 'Participant registered successfully. Ticket code: ' . $participant->ticket_code);
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Participant $participant)
    {
        $participant->load(['workshop', 'ticketType']);

        return view('participants.show', compact('participant'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Participant $participant)
    {
        $participant->load(['workshop', 'ticketType']);
        $workshops = Workshop::with('ticketTypes')->orderBy('name')->get();
        $ticketTypes = $participant->workshop->ticketTypes;

        return view('participants.edit', compact('participant', 'workshops', 'ticketTypes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ParticipantRequest $request, Participant $participant)
    {
        try {
            $participant = $this->participantService->updateParticipant($participant, $request->validated());

            return redirect()->route('participants.index', ['workshop_id' => $participant->workshop_id])
                ->with('success', 'Participant updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Participant $participant)
    {
        try {
            $workshopId = $participant->workshop_id;
            $this->participantService->deleteParticipant($participant);

            return redirect()->route('participants.index', ['workshop_id' => $workshopId])
                ->with('success', 'Participant deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Update payment status.
     */
    public function updatePaymentStatus(Request $request, Participant $participant)
    {
        $request->validate([
            'is_paid' => 'required|boolean'
        ]);

        $this->participantService->updatePaymentStatus($participant, $request->is_paid);

        $status = $request->is_paid ? 'paid' : 'unpaid';
        return redirect()->back()
            ->with('success', "Participant marked as {$status} successfully.");
    }

    /**
     * Update check-in status.
     */
    public function updateCheckinStatus(Request $request, Participant $participant)
    {
        $request->validate([
            'is_checked_in' => 'required|boolean'
        ]);

        $this->participantService->updateCheckinStatus($participant, $request->is_checked_in);

        $status = $request->is_checked_in ? 'checked in' : 'not checked in';
        return redirect()->back()
            ->with('success', "Participant marked as {$status} successfully.");
    }

    /**
     * Bulk update payment status.
     */
    public function bulkUpdatePayment(Request $request)
    {
        $request->validate([
            'participant_ids' => 'required|array',
            'participant_ids.*' => 'exists:participants,id',
            'is_paid' => 'required|boolean'
        ]);

        $count = $this->participantService->bulkUpdatePaymentStatus($request->participant_ids, $request->is_paid);

        $status = $request->is_paid ? 'paid' : 'unpaid';
        return redirect()->back()
            ->with('success', "Updated {$count} participants to {$status} status.");
    }

    /**
     * Bulk update check-in status.
     */
    public function bulkUpdateCheckin(Request $request)
    {
        $request->validate([
            'participant_ids' => 'required|array',
            'participant_ids.*' => 'exists:participants,id',
            'is_checked_in' => 'required|boolean'
        ]);

        $count = $this->participantService->bulkUpdateCheckinStatus($request->participant_ids, $request->is_checked_in);

        $status = $request->is_checked_in ? 'checked in' : 'not checked in';
        return redirect()->back()
            ->with('success', "Updated {$count} participants to {$status} status.");
    }

    /**
     * Export participants to CSV.
     */
    public function export(Request $request)
    {
        $query = Participant::with(['workshop', 'ticketType']);

        // Apply same filters as index
        if ($request->filled('workshop_id')) {
            $query->where('workshop_id', $request->workshop_id);
        }

        if ($request->filled('ticket_type_id')) {
            $query->where('ticket_type_id', $request->ticket_type_id);
        }

        if ($request->filled('payment_status')) {
            $query->where('is_paid', $request->payment_status === 'paid');
        }

        if ($request->filled('checkin_status')) {
            $query->where('is_checked_in', $request->checkin_status === 'checked_in');
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('ticket_code', 'like', '%' . $request->search . '%');
            });
        }

        $participants = $query->get();

        $filename = 'participants_' . date('Y-m-d_H-i-s') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($participants) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'Name', 'Email', 'Phone', 'Occupation', 'Company', 'Position',
                'Workshop', 'Ticket Type', 'Ticket Code', 'Fee', 'Paid', 'Checked In',
                'Registration Date'
            ]);

            // CSV data
            foreach ($participants as $participant) {
                fputcsv($file, [
                    $participant->name,
                    $participant->email,
                    $participant->phone,
                    $participant->occupation,
                    $participant->company,
                    $participant->position,
                    $participant->workshop->name,
                    $participant->ticketType->name,
                    $participant->ticket_code,
                    $participant->ticketType->fee,
                    $participant->is_paid ? 'Yes' : 'No',
                    $participant->is_checked_in ? 'Yes' : 'No',
                    $participant->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get ticket types for a workshop (AJAX).
     */
    public function getTicketTypes(Workshop $workshop)
    {
        $ticketTypes = $workshop->ticketTypes()->orderBy('name')->get();
        
        return response()->json($ticketTypes);
    }

    /**
     * Show import form.
     */
    public function showImport(Request $request)
    {
        $workshops = Workshop::with('ticketTypes')->orderBy('name')->get();
        $selectedWorkshop = $request->workshop_id ? Workshop::find($request->workshop_id) : null;
        $ticketTypes = $selectedWorkshop ? $selectedWorkshop->ticketTypes : collect();

        return view('participants.import', compact('workshops', 'selectedWorkshop', 'ticketTypes'));
    }

    /**
     * Process Excel import.
     */
    public function import(Request $request)
    {
        $request->validate([
            'workshop_id' => 'required|exists:workshops,id',
            'ticket_type_id' => 'nullable|exists:ticket_types,id',
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // 10MB max
        ]);

        try {
            // Validate that ticket type belongs to workshop if specified
            if ($request->filled('ticket_type_id')) {
                $ticketType = TicketType::find($request->ticket_type_id);
                if ($ticketType->workshop_id != $request->workshop_id) {
                    return redirect()->back()
                        ->with('error', 'Selected ticket type does not belong to the selected workshop.');
                }
            }

            // Store the uploaded file
            $file = $request->file('file');
            $filename = 'imports/' . Str::uuid() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('', $filename, 'local');

            // Generate import ID for tracking
            $importId = Str::uuid();

            // Dispatch the import job
            ProcessParticipantImportJob::dispatch(
                $filePath,
                $request->workshop_id,
                $request->ticket_type_id,
                Auth::id(),
                $importId
            );

            return redirect()->route('participants.index', ['workshop_id' => $request->workshop_id])
                ->with('success', 'Import started successfully. You will be notified when it completes.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Download import template.
     */
    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="participant_import_template.csv"',
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            
            // CSV headers with sample data
            fputcsv($file, [
                'name', 'email', 'phone', 'occupation', 'company', 'position', 
                'address', 'ticket_type', 'is_paid'
            ]);

            // Sample row
            fputcsv($file, [
                'John Doe',
                'john.doe@example.com',
                '+1234567890',
                'Software Engineer',
                'Tech Corp',
                'Senior Developer',
                '123 Main St, City, Country',
                'Standard',
                'no'
            ]);

            // Add instructions as comments
            fputcsv($file, []);
            fputcsv($file, ['# Instructions:']);
            fputcsv($file, ['# - name: Required. Full name of participant']);
            fputcsv($file, ['# - email: Required. Must be unique per workshop']);
            fputcsv($file, ['# - phone: Optional. Phone number']);
            fputcsv($file, ['# - occupation: Optional. Job title or occupation']);
            fputcsv($file, ['# - company: Optional. Company name']);
            fputcsv($file, ['# - position: Optional. Position in company']);
            fputcsv($file, ['# - address: Optional. Full address']);
            fputcsv($file, ['# - ticket_type: Optional. Must match existing ticket type name']);
            fputcsv($file, ['# - is_paid: Optional. Use "yes" or "no" (default: no)']);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
