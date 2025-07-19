<?php

namespace App\Services;

use App\Models\Participant;
use App\Models\Workshop;
use App\Models\EmailTemplate;
use App\Jobs\SendTicketEmailJob;
use App\Mail\TicketMailable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class EmailService
{
    /**
     * Send ticket email to a participant.
     */
    public function sendTicketEmail(Participant $participant, ?EmailTemplate $emailTemplate = null, bool $queue = true): bool
    {
        try {
            if ($queue) {
                // Dispatch job to queue
                SendTicketEmailJob::dispatch(
                    $participant->id,
                    $emailTemplate?->id,
                    'ticket'
                );
                
                Log::info('Ticket email job dispatched', [
                    'participant_id' => $participant->id,
                    'participant_email' => $participant->email,
                ]);
                
                return true;
            } else {
                // Send immediately
                Mail::to($participant->email)
                    ->send(new TicketMailable($participant, $emailTemplate));
                
                Log::info('Ticket email sent immediately', [
                    'participant_id' => $participant->id,
                    'participant_email' => $participant->email,
                ]);
                
                return true;
            }
        } catch (\Exception $e) {
            Log::error('Failed to send ticket email', [
                'participant_id' => $participant->id,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Send ticket emails to multiple participants.
     */
    public function sendBulkTicketEmails(Collection $participants, ?EmailTemplate $emailTemplate = null, bool $queue = true): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($participants as $participant) {
            try {
                $success = $this->sendTicketEmail($participant, $emailTemplate, $queue);
                
                if ($success) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = "Failed to send email to {$participant->email}";
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Error sending to {$participant->email}: " . $e->getMessage();
            }
        }

        Log::info('Bulk ticket emails processed', [
            'total_participants' => $participants->count(),
            'success_count' => $results['success'],
            'failed_count' => $results['failed'],
        ]);

        return $results;
    }

    /**
     * Resend ticket email to a participant.
     */
    public function resendTicketEmail(Participant $participant, ?EmailTemplate $emailTemplate = null): bool
    {
        Log::info('Resending ticket email', [
            'participant_id' => $participant->id,
            'participant_email' => $participant->email,
        ]);

        return $this->sendTicketEmail($participant, $emailTemplate, true);
    }

    /**
     * Send workshop notification emails.
     */
    public function sendWorkshopNotification(Workshop $workshop, string $emailType, ?string $customSubject = null, ?string $customContent = null): array
    {
        // Get email template for this workshop and type
        $emailTemplate = EmailTemplate::where('workshop_id', $workshop->id)
            ->where('type', $emailType)
            ->first();

        // If no template found and custom content provided, create temporary template
        if (!$emailTemplate && ($customSubject || $customContent)) {
            $emailTemplate = new EmailTemplate([
                'workshop_id' => $workshop->id,
                'type' => $emailType,
                'subject' => $customSubject ?? "Workshop Notification: {$workshop->name}",
                'content' => $customContent ?? "This is a notification about {$workshop->name}.",
            ]);
        }

        if (!$emailTemplate) {
            throw new \Exception("No email template found for workshop and type: {$emailType}");
        }

        // Get all participants for this workshop
        $participants = $workshop->participants()->get();

        return $this->sendBulkTicketEmails($participants, $emailTemplate);
    }

    /**
     * Send reminder emails to unpaid participants.
     */
    public function sendPaymentReminders(Workshop $workshop): array
    {
        $unpaidParticipants = $workshop->participants()
            ->where('is_paid', false)
            ->get();

        if ($unpaidParticipants->isEmpty()) {
            return [
                'success' => 0,
                'failed' => 0,
                'errors' => ['No unpaid participants found'],
            ];
        }

        // Get reminder email template
        $emailTemplate = EmailTemplate::where('workshop_id', $workshop->id)
            ->where('type', 'reminder')
            ->first();

        return $this->sendBulkTicketEmails($unpaidParticipants, $emailTemplate);
    }

    /**
     * Send thank you emails to checked-in participants.
     */
    public function sendThankYouEmails(Workshop $workshop): array
    {
        $checkedInParticipants = $workshop->participants()
            ->where('is_checked_in', true)
            ->get();

        if ($checkedInParticipants->isEmpty()) {
            return [
                'success' => 0,
                'failed' => 0,
                'errors' => ['No checked-in participants found'],
            ];
        }

        // Get thank you email template
        $emailTemplate = EmailTemplate::where('workshop_id', $workshop->id)
            ->where('type', 'thank_you')
            ->first();

        return $this->sendBulkTicketEmails($checkedInParticipants, $emailTemplate);
    }

    /**
     * Send confirmation emails to newly registered participants.
     */
    public function sendConfirmationEmail(Participant $participant): bool
    {
        // Get confirmation email template
        $emailTemplate = EmailTemplate::where('workshop_id', $participant->workshop_id)
            ->where('type', 'confirm')
            ->first();

        return $this->sendTicketEmail($participant, $emailTemplate);
    }

    /**
     * Send invitation emails to potential participants.
     */
    public function sendInvitationEmails(Workshop $workshop, array $emailAddresses, ?string $customMessage = null): array
    {
        // Get invitation email template
        $emailTemplate = EmailTemplate::where('workshop_id', $workshop->id)
            ->where('type', 'invite')
            ->first();

        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($emailAddresses as $email) {
            try {
                // Create temporary participant for email sending
                $tempParticipant = new Participant([
                    'workshop_id' => $workshop->id,
                    'name' => 'Invited Guest',
                    'email' => $email,
                    'ticket_code' => 'INVITATION',
                ]);
                $tempParticipant->workshop = $workshop;

                // Send invitation email immediately (not queued for invitations)
                Mail::to($email)->send(new TicketMailable($tempParticipant, $emailTemplate));
                
                $results['success']++;
                
                Log::info('Invitation email sent', [
                    'workshop_id' => $workshop->id,
                    'email' => $email,
                ]);
                
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Failed to send invitation to {$email}: " . $e->getMessage();
                
                Log::error('Failed to send invitation email', [
                    'workshop_id' => $workshop->id,
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Test email template by sending to a test email address.
     */
    public function testEmailTemplate(EmailTemplate $emailTemplate, string $testEmail): bool
    {
        try {
            // Create a test participant
            $testParticipant = new Participant([
                'workshop_id' => $emailTemplate->workshop_id,
                'name' => 'Test User',
                'email' => $testEmail,
                'ticket_code' => 'TEST123',
            ]);
            
            // Load workshop relationship
            $testParticipant->workshop = $emailTemplate->workshop;
            
            // Create a dummy ticket type
            $testParticipant->ticketType = (object) [
                'name' => 'Test Ticket',
                'fee' => 0.00,
            ];

            // Send test email immediately
            Mail::to($testEmail)->send(new TicketMailable($testParticipant, $emailTemplate));

            Log::info('Test email sent successfully', [
                'template_id' => $emailTemplate->id,
                'test_email' => $testEmail,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send test email', [
                'template_id' => $emailTemplate->id,
                'test_email' => $testEmail,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get email sending statistics.
     */
    public function getEmailStatistics(Workshop $workshop): array
    {
        $participants = $workshop->participants();

        return [
            'total_participants' => $participants->count(),
            'emails_to_send' => $participants->count(), // Assuming all participants should receive emails
            'pending_emails' => $this->getPendingEmailCount($workshop),
            'failed_emails' => $this->getFailedEmailCount($workshop),
        ];
    }

    /**
     * Get count of pending emails in queue.
     */
    private function getPendingEmailCount(Workshop $workshop): int
    {
        // This would require checking the jobs table
        // For now, return 0 as placeholder
        return 0;
    }

    /**
     * Get count of failed emails.
     */
    private function getFailedEmailCount(Workshop $workshop): int
    {
        // This would require checking the failed_jobs table
        // For now, return 0 as placeholder
        return 0;
    }

    /**
     * Validate email template variables.
     */
    public function validateEmailTemplate(EmailTemplate $emailTemplate): array
    {
        $errors = [];
        $availableVariables = EmailTemplate::getAvailableVariables();
        
        // Check subject for invalid variables
        $subjectVariables = $this->extractVariables($emailTemplate->subject);
        foreach ($subjectVariables as $variable) {
            if (!array_key_exists($variable, $availableVariables)) {
                $errors[] = "Invalid variable in subject: {{ {$variable} }}";
            }
        }
        
        // Check content for invalid variables
        $contentVariables = $this->extractVariables($emailTemplate->content);
        foreach ($contentVariables as $variable) {
            if (!array_key_exists($variable, $availableVariables)) {
                $errors[] = "Invalid variable in content: {{ {$variable} }}";
            }
        }
        
        return $errors;
    }

    /**
     * Extract variables from template text.
     */
    private function extractVariables(string $text): array
    {
        preg_match_all('/\{\{\s*(\w+)\s*\}\}/', $text, $matches);
        return $matches[1] ?? [];
    }

    /**
     * Preview email template with sample data.
     */
    public function previewEmailTemplate(EmailTemplate $emailTemplate): array
    {
        // Create sample participant data
        $sampleParticipant = new Participant([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'ticket_code' => 'SAMPLE123',
            'is_paid' => true,
        ]);
        
        $sampleParticipant->workshop = $emailTemplate->workshop;
        $sampleParticipant->ticketType = (object) [
            'name' => 'Standard Ticket',
            'fee' => 50.00,
        ];

        // Create sample variables
        $variables = [
            'name' => $sampleParticipant->name,
            'email' => $sampleParticipant->email,
            'workshop_name' => $emailTemplate->workshop->name,
            'workshop_date' => $emailTemplate->workshop->date_time->format('F j, Y \a\t g:i A'),
            'workshop_location' => $emailTemplate->workshop->location,
            'ticket_code' => $sampleParticipant->ticket_code,
            'ticket_type' => 'Standard Ticket',
            'ticket_fee' => '50.00',
            'qr_code_url' => 'https://example.com/qr-code.png',
            'payment_status' => 'Paid',
        ];

        return [
            'subject' => $emailTemplate->renderSubject($variables),
            'content' => $emailTemplate->renderContent($variables),
            'variables' => $variables,
        ];
    }
}