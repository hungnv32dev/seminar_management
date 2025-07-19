<?php

namespace App\Http\Controllers;

use App\Http\Requests\TicketTypeRequest;
use App\Models\TicketType;
use App\Models\Workshop;
use Illuminate\Http\Request;

class TicketTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = TicketType::with(['workshop', 'participants']);

        // Filter by workshop if provided
        if ($request->filled('workshop_id')) {
            $query->where('workshop_id', $request->workshop_id);
        }

        // Search by name
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $ticketTypes = $query->orderBy('workshop_id')->orderBy('name')->paginate(15);
        $workshops = Workshop::orderBy('name')->get();

        return view('ticket-types.index', compact('ticketTypes', 'workshops'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $workshops = Workshop::orderBy('name')->get();
        $selectedWorkshop = $request->workshop_id ? Workshop::find($request->workshop_id) : null;

        return view('ticket-types.create', compact('workshops', 'selectedWorkshop'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TicketTypeRequest $request)
    {
        $ticketType = TicketType::create($request->validated());

        return redirect()->route('ticket-types.index', ['workshop_id' => $ticketType->workshop_id])
            ->with('success', 'Ticket type created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(TicketType $ticketType)
    {
        $ticketType->load(['workshop', 'participants']);
        
        // Get statistics
        $totalParticipants = $ticketType->participants->count();
        $paidParticipants = $ticketType->participants->where('is_paid', true)->count();
        $checkedInParticipants = $ticketType->participants->where('is_checked_in', true)->count();
        $totalRevenue = $paidParticipants * $ticketType->fee;
        $potentialRevenue = $totalParticipants * $ticketType->fee;

        return view('ticket-types.show', compact(
            'ticketType',
            'totalParticipants',
            'paidParticipants',
            'checkedInParticipants',
            'totalRevenue',
            'potentialRevenue'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TicketType $ticketType)
    {
        $workshops = Workshop::orderBy('name')->get();
        return view('ticket-types.edit', compact('ticketType', 'workshops'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TicketTypeRequest $request, TicketType $ticketType)
    {
        $ticketType->update($request->validated());

        return redirect()->route('ticket-types.index', ['workshop_id' => $ticketType->workshop_id])
            ->with('success', 'Ticket type updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TicketType $ticketType)
    {
        // Check if ticket type can be deleted
        if (!$ticketType->canBeDeleted()) {
            return redirect()->route('ticket-types.index')
                ->with('error', 'Cannot delete ticket type that has participants assigned. Please remove participants first.');
        }

        $workshopId = $ticketType->workshop_id;
        $ticketType->delete();

        return redirect()->route('ticket-types.index', ['workshop_id' => $workshopId])
            ->with('success', 'Ticket type deleted successfully.');
    }

    /**
     * Get ticket types for a specific workshop (AJAX).
     */
    public function getByWorkshop(Workshop $workshop)
    {
        $ticketTypes = $workshop->ticketTypes()->orderBy('name')->get();
        
        return response()->json($ticketTypes);
    }

    /**
     * Bulk delete ticket types.
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ticket_type_ids' => 'required|array',
            'ticket_type_ids.*' => 'exists:ticket_types,id'
        ]);

        $ticketTypes = TicketType::whereIn('id', $request->ticket_type_ids)->get();
        $deletedCount = 0;
        $errors = [];

        foreach ($ticketTypes as $ticketType) {
            if ($ticketType->canBeDeleted()) {
                $ticketType->delete();
                $deletedCount++;
            } else {
                $errors[] = "Cannot delete '{$ticketType->name}' - has participants assigned.";
            }
        }

        $message = "Deleted {$deletedCount} ticket type(s) successfully.";
        if (!empty($errors)) {
            $message .= ' Errors: ' . implode(' ', $errors);
        }

        return redirect()->route('ticket-types.index')
            ->with($deletedCount > 0 ? 'success' : 'warning', $message);
    }

    /**
     * Duplicate ticket type.
     */
    public function duplicate(TicketType $ticketType)
    {
        $newTicketType = $ticketType->replicate();
        $newTicketType->name = $ticketType->name . ' (Copy)';
        $newTicketType->save();

        return redirect()->route('ticket-types.edit', $newTicketType)
            ->with('success', 'Ticket type duplicated successfully. Please review and update the details.');
    }
}
