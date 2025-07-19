<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmailTemplateRequest;
use App\Models\EmailTemplate;
use App\Models\Workshop;
use App\Services\EmailService;
use Illuminate\Http\Request;

class EmailTemplateController extends Controller
{
    protected $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = EmailTemplate::with('workshop');

        // Filter by workshop
        if ($request->filled('workshop_id')) {
            $query->where('workshop_id', $request->workshop_id);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Search by subject or content
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('subject', 'like', '%' . $request->search . '%')
                  ->orWhere('content', 'like', '%' . $request->search . '%');
            });
        }

        $emailTemplates = $query->orderBy('workshop_id')->orderBy('type')->paginate(15);
        
        // Get filter options
        $workshops = Workshop::orderBy('name')->get();
        $types = ['invite', 'confirm', 'ticket', 'reminder', 'thank_you'];

        return view('email-templates.index', compact('emailTemplates', 'workshops', 'types'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $workshops = Workshop::orderBy('name')->get();
        $selectedWorkshop = $request->workshop_id ? Workshop::find($request->workshop_id) : null;
        $types = ['invite', 'confirm', 'ticket', 'reminder', 'thank_you'];
        $availableVariables = EmailTemplate::getAvailableVariables();

        return view('email-templates.create', compact('workshops', 'selectedWorkshop', 'types', 'availableVariables'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(EmailTemplateRequest $request)
    {
        $emailTemplate = EmailTemplate::create($request->validated());

        return redirect()->route('email-templates.index', ['workshop_id' => $emailTemplate->workshop_id])
            ->with('success', 'Email template created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(EmailTemplate $emailTemplate)
    {
        $emailTemplate->load('workshop');
        $availableVariables = EmailTemplate::getAvailableVariables();
        
        // Get preview with sample data
        $preview = $this->emailService->previewEmailTemplate($emailTemplate);

        return view('email-templates.show', compact('emailTemplate', 'availableVariables', 'preview'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EmailTemplate $emailTemplate)
    {
        $emailTemplate->load('workshop');
        $workshops = Workshop::orderBy('name')->get();
        $types = ['invite', 'confirm', 'ticket', 'reminder', 'thank_you'];
        $availableVariables = EmailTemplate::getAvailableVariables();

        return view('email-templates.edit', compact('emailTemplate', 'workshops', 'types', 'availableVariables'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(EmailTemplateRequest $request, EmailTemplate $emailTemplate)
    {
        $emailTemplate->update($request->validated());

        return redirect()->route('email-templates.index', ['workshop_id' => $emailTemplate->workshop_id])
            ->with('success', 'Email template updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EmailTemplate $emailTemplate)
    {
        $workshopId = $emailTemplate->workshop_id;
        $emailTemplate->delete();

        return redirect()->route('email-templates.index', ['workshop_id' => $workshopId])
            ->with('success', 'Email template deleted successfully.');
    }

    /**
     * Preview email template with sample data.
     */
    public function preview(EmailTemplate $emailTemplate)
    {
        try {
            $preview = $this->emailService->previewEmailTemplate($emailTemplate);
            
            return response()->json([
                'success' => true,
                'preview' => $preview,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Test email template by sending to a test email address.
     */
    public function test(Request $request, EmailTemplate $emailTemplate)
    {
        $request->validate([
            'test_email' => 'required|email',
        ]);

        try {
            $success = $this->emailService->testEmailTemplate($emailTemplate, $request->test_email);
            
            if ($success) {
                return redirect()->back()
                    ->with('success', "Test email sent successfully to {$request->test_email}");
            } else {
                return redirect()->back()
                    ->with('error', 'Failed to send test email. Please check the logs for details.');
            }
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error sending test email: ' . $e->getMessage());
        }
    }

    /**
     * Validate email template variables.
     */
    public function validate(EmailTemplate $emailTemplate)
    {
        try {
            $errors = $this->emailService->validateEmailTemplate($emailTemplate);
            
            return response()->json([
                'success' => true,
                'valid' => empty($errors),
                'errors' => $errors,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Duplicate email template.
     */
    public function duplicate(EmailTemplate $emailTemplate)
    {
        try {
            $newTemplate = $emailTemplate->replicate();
            $newTemplate->subject = $emailTemplate->subject . ' (Copy)';
            $newTemplate->save();

            return redirect()->route('email-templates.edit', $newTemplate)
                ->with('success', 'Email template duplicated successfully. Please review and update the details.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to duplicate email template: ' . $e->getMessage());
        }
    }

    /**
     * Get email templates for a specific workshop (AJAX).
     */
    public function getByWorkshop(Workshop $workshop)
    {
        $emailTemplates = $workshop->emailTemplates()->orderBy('type')->get();
        
        return response()->json($emailTemplates);
    }

    /**
     * Get available template variables (AJAX).
     */
    public function getVariables()
    {
        $variables = EmailTemplate::getAvailableVariables();
        
        return response()->json([
            'success' => true,
            'variables' => $variables,
        ]);
    }

    /**
     * Bulk delete email templates.
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'template_ids' => 'required|array',
            'template_ids.*' => 'exists:email_templates,id'
        ]);

        try {
            $deletedCount = EmailTemplate::whereIn('id', $request->template_ids)->delete();

            return redirect()->route('email-templates.index')
                ->with('success', "Deleted {$deletedCount} email template(s) successfully.");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete email templates: ' . $e->getMessage());
        }
    }

    /**
     * Export email template.
     */
    public function export(EmailTemplate $emailTemplate)
    {
        $data = [
            'workshop_name' => $emailTemplate->workshop->name,
            'type' => $emailTemplate->type,
            'subject' => $emailTemplate->subject,
            'content' => $emailTemplate->content,
            'created_at' => $emailTemplate->created_at->format('Y-m-d H:i:s'),
        ];

        $filename = "email_template_{$emailTemplate->type}_{$emailTemplate->workshop->name}.json";
        $filename = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $filename);

        return response()->json($data)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Content-Type', 'application/json');
    }
}
