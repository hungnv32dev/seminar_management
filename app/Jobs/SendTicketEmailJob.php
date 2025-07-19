<?php

namespace App\Jobs;

use App\Models\Participant;
use App\Models\EmailTemplate;
use App\Mail\TicketMailable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendTicketEmailJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    protected $participantId;
    protected $emailTemplateId;
    protected $emailType;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public $timeout = 120;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = [30, 60, 120];

    /**
     * Create a new job instance.
     */
    public function __construct(int $participantId, ?int $emailTemplateId = null, string $emailType = 'ticket')
    {
        $this->participantId = $participantId;
        $this->emailTemplateId = $emailTemplateId;
        $this->emailType = $emailType;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Get participant with relationships
            $participant = Participant::with(['workshop', 'ticketType'])
                ->findOrFail($this->participantId);

            // Get email template if specified
            $emailTemplate = null;
            if ($this->emailTemplateId) {
                $emailTemplate = EmailTemplate::find($this->emailTemplateId);
            } else {
                // Try to find default template for this workshop and email type
                $emailTemplate = EmailTemplate::where('workshop_id', $participant->workshop_id)
                    ->where('type', $this->emailType)
                    ->first();
            }

            Log::info('Sending ticket email', [
                'participant_id' => $this->participantId,
                'participant_email' => $participant->email,
                'workshop_name' => $participant->workshop->name,
                'email_template_id' => $this->emailTemplateId,
                'email_type' => $this->emailType,
            ]);

            // Send the email
            Mail::to($participant->email)
                ->send(new TicketMailable($participant, $emailTemplate));

            Log::info('Ticket email sent successfully', [
                'participant_id' => $this->participantId,
                'participant_email' => $participant->email,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send ticket email', [
                'participant_id' => $this->participantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw the exception to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Ticket email job failed permanently', [
            'participant_id' => $this->participantId,
            'email_template_id' => $this->emailTemplateId,
            'email_type' => $this->emailType,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Optionally, you could send a notification to administrators
        // or update the participant record to indicate email failure
        try {
            $participant = Participant::find($this->participantId);
            if ($participant) {
                // You could add an email_status field to track this
                Log::warning('Email sending failed for participant', [
                    'participant_name' => $participant->name,
                    'participant_email' => $participant->email,
                    'workshop_name' => $participant->workshop->name ?? 'Unknown',
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to log email failure details', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [30, 60, 120]; // Wait 30s, then 60s, then 120s between retries
    }

    /**
     * Determine if the job should be retried based on the exception.
     */
    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(10); // Stop retrying after 10 minutes
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'email',
            'ticket',
            'participant:' . $this->participantId,
            'type:' . $this->emailType,
        ];
    }
}
