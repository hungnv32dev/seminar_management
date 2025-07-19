<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkshopRequest;
use App\Models\Workshop;
use App\Models\User;
use App\Services\WorkshopService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkshopController extends Controller
{
    protected $workshopService;

    public function __construct(WorkshopService $workshopService)
    {
        $this->workshopService = $workshopService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Workshop::with(['organizers', 'participants', 'ticketTypes']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('date_time', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('date_time', '<=', $request->date_to);
        }

        // Search by name or location
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('location', 'like', '%' . $request->search . '%');
            });
        }

        $workshops = $query->orderBy('date_time', 'desc')->paginate(15);

        return view('workshops.index', compact('workshops'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $users = User::active()->get();
        return view('workshops.create', compact('users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(WorkshopRequest $request)
    {
        try {
            $this->workshopService->createWorkshop(
                $request->validated(),
                $request->organizers ?? []
            );

            return redirect()->route('workshops.index')
                ->with('success', 'Workshop created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Workshop $workshop)
    {
        $workshop->load([
            'organizers',
            'ticketTypes.participants',
            'participants.ticketType',
            'emailTemplates'
        ]);

        // Get statistics from service
        $statistics = $this->workshopService->getWorkshopStatistics($workshop);

        return view('workshops.show', compact('workshop', 'statistics'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Workshop $workshop)
    {
        $workshop->load('organizers');
        $users = User::active()->get();
        $selectedOrganizers = $workshop->organizers->pluck('id')->toArray();

        return view('workshops.edit', compact('workshop', 'users', 'selectedOrganizers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(WorkshopRequest $request, Workshop $workshop)
    {
        try {
            $this->workshopService->updateWorkshop(
                $workshop,
                $request->validated(),
                $request->organizers ?? []
            );

            return redirect()->route('workshops.index')
                ->with('success', 'Workshop updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Workshop $workshop)
    {
        try {
            $this->workshopService->deleteWorkshop($workshop);

            return redirect()->route('workshops.index')
                ->with('success', 'Workshop deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->route('workshops.index')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Update workshop status.
     */
    public function updateStatus(Request $request, Workshop $workshop)
    {
        $request->validate([
            'status' => 'required|in:draft,published,ongoing,completed,cancelled'
        ]);

        try {
            $this->workshopService->updateStatus($workshop, $request->status);

            return redirect()->back()
                ->with('success', 'Workshop status updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Assign organizers to workshop.
     */
    public function assignOrganizers(Request $request, Workshop $workshop)
    {
        $request->validate([
            'organizers' => 'required|array',
            'organizers.*' => 'exists:users,id'
        ]);

        try {
            $this->workshopService->assignOrganizers($workshop, $request->organizers);

            return redirect()->back()
                ->with('success', 'Organizers assigned successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Remove organizer from workshop.
     */
    public function removeOrganizer(Workshop $workshop, User $user)
    {
        try {
            $this->workshopService->removeOrganizer($workshop, $user->id);

            return redirect()->back()
                ->with('success', 'Organizer removed successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Duplicate workshop.
     */
    public function duplicate(Workshop $workshop)
    {
        try {
            $newWorkshop = $this->workshopService->duplicateWorkshop($workshop);

            return redirect()->route('workshops.edit', $newWorkshop)
                ->with('success', 'Workshop duplicated successfully. Please review and update the details.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }
}
