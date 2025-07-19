<?php

namespace App\Jobs;

use App\Imports\ParticipantsImport;
use App\Models\Workshop;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;

class ProcessParticipantImportJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    protected $filePath;
    protected $workshopId;
    protected $ticketTypeId;
    protected $userId;
    protected $importId;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct($filePath, $workshopId, $ticketTypeId = null, $userId = null, $importId = null)
    {
        $this->filePath = $filePath;
        $this->workshopId = $workshopId;
        $this->ticketTypeId = $ticketTypeId;
        $this->userId = $userId;
        $this->importId = $importId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Starting participant import', [
                'file' => $this->filePath,
                'workshop_id' => $this->workshopId,
                'ticket_type_id' => $this->ticketTypeId,
                'user_id' => $this->userId,
                'import_id' => $this->importId,
            ]);

            // Check if file exists
            if (!Storage::exists($this->filePath)) {
                throw new \Exception('Import file not found: ' . $this->filePath);
            }

            // Get workshop
            $workshop = Workshop::findOrFail($this->workshopId);

            // Create import instance
            $import = new ParticipantsImport($this->workshopId, $this->ticketTypeId);

            // Process the Excel file
            Excel::import($import, Storage::path($this->filePath));

            // Count successful imports
            $importedCount = $workshop->participants()->count();

            Log::info('Participant import completed successfully', [
                'imported_count' => $importedCount,
                'workshop_id' => $this->workshopId,
                'import_id' => $this->importId,
            ]);

            // Send notification email to user if specified
            if ($this->userId) {
                $user = User::find($this->userId);
                if ($user) {
                    $this->sendCompletionNotification($user, $workshop, $importedCount, []);
                }
            }

            // Clean up the uploaded file
            Storage::delete($this->filePath);

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errors = [];
            
            foreach ($failures as $failure) {
                $errors[] = [
                    'row' => $failure->row(),
                    'attribute' => $failure->attribute(),
                    'errors' => $failure->errors(),
                    'values' => $failure->values(),
                ];
            }

            Log::error('Participant import validation failed', [
                'errors' => $errors,
                'workshop_id' => $this->workshopId,
                'import_id' => $this->importId,
            ]);

            // Send error notification
            if ($this->userId) {
                $user = User::find($this->userId);
                if ($user) {
                    $workshop = Workshop::find($this->workshopId);
                    $this->sendCompletionNotification($user, $workshop, 0, $errors);
                }
            }

            // Clean up the uploaded file
            Storage::delete($this->filePath);

            throw $e;

        } catch (\Exception $e) {
            Log::error('Participant import failed', [
                'error' => $e->getMessage(),
                'workshop_id' => $this->workshopId,
                'import_id' => $this->importId,
            ]);

            // Send error notification
            if ($this->userId) {
                $user = User::find($this->userId);
                if ($user) {
                    $workshop = Workshop::find($this->workshopId);
                    $this->sendCompletionNotification($user, $workshop, 0, [['error' => $e->getMessage()]]);
                }
            }

            // Clean up the uploaded file
            Storage::delete($this->filePath);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Participant import job failed', [
            'error' => $exception->getMessage(),
            'workshop_id' => $this->workshopId,
            'import_id' => $this->importId,
        ]);

        // Clean up the uploaded file
        if (Storage::exists($this->filePath)) {
            Storage::delete($this->filePath);
        }

        // Send failure notification
        if ($this->userId) {
            $user = User::find($this->userId);
            if ($user) {
                $workshop = Workshop::find($this->workshopId);
                $this->sendCompletionNotification($user, $workshop, 0, [['error' => $exception->getMessage()]]);
            }
        }
    }

    /**
     * Send completion notification to user.
     */
    private function sendCompletionNotification(User $user, Workshop $workshop, int $importedCount, array $errors): void
    {
        // This would typically send an email notification
        // For now, we'll just log it
        Log::info('Import completion notification', [
            'user_email' => $user->email,
            'workshop_name' => $workshop->name,
            'imported_count' => $importedCount,
            'error_count' => count($errors),
        ]);

        // TODO: Implement actual email notification
        // Mail::to($user)->send(new ImportCompletionMail($workshop, $importedCount, $errors));
    }
}
