<?php

namespace App\Mail;

use App\Models\Participant;
use App\Models\EmailTemplate;
use App\Services\QRCodeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class TicketMailable extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $participant;
    public $emailTemplate;
    public $qrCodeService;

    /**
     * Create a new message instance.
     */
    public function __construct(Participant $participant, ?EmailTemplate $emailTemplate = null)
    {
        $this->participant = $participant->load(['workshop', 'ticketType']);
        $this->emailTemplate = $emailTemplate;
        $this->qrCodeService = new QRCodeService();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->getEmailSubject();
        
        return new Envelope(
            subject: $subject,
            from: config('mail.from.address'),
            replyTo: config('mail.from.address'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.ticket',
            with: [
                'participant' => $this->participant,
                'workshop' => $this->participant->workshop,
                'ticketType' => $this->participant->ticketType,
                'emailContent' => $this->getEmailContent(),
                'qrCodeDataUri' => $this->qrCodeService->generateQRCodeDataUri($this->participant->ticket_code),
                'qrCodeUrl' => $this->qrCodeService->getParticipantQRCodeUrl($this->participant),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];

        try {
            // Generate QR code and attach as file
            $qrCodePath = $this->qrCodeService->generateParticipantQRCode($this->participant);
            $fullPath = Storage::disk('public')->path($qrCodePath);

            if (file_exists($fullPath)) {
                $attachments[] = Attachment::fromPath($fullPath)
                    ->as('ticket-qr-code.png')
                    ->withMime('image/png');
            }
        } catch (\Exception $e) {
            // Log error but don't fail email sending
            \Log::error('Failed to attach QR code to email', [
                'participant_id' => $this->participant->id,
                'error' => $e->getMessage()
            ]);
        }

        return $attachments;
    }

    /**
     * Get email subject with variable substitution.
     */
    private function getEmailSubject(): string
    {
        if ($this->emailTemplate) {
            return $this->emailTemplate->renderSubject($this->getTemplateVariables());
        }

        return "Your ticket for {$this->participant->workshop->name}";
    }

    /**
     * Get email content with variable substitution.
     */
    private function getEmailContent(): string
    {
        if ($this->emailTemplate) {
            return $this->emailTemplate->renderContent($this->getTemplateVariables());
        }

        return $this->getDefaultEmailContent();
    }

    /**
     * Get template variables for substitution.
     */
    private function getTemplateVariables(): array
    {
        return [
            'name' => $this->participant->name,
            'email' => $this->participant->email,
            'workshop_name' => $this->participant->workshop->name,
            'workshop_date' => $this->participant->workshop->date_time->format('F j, Y \a\t g:i A'),
            'workshop_location' => $this->participant->workshop->location,
            'ticket_code' => $this->participant->ticket_code,
            'ticket_type' => $this->participant->ticketType->name,
            'ticket_fee' => number_format($this->participant->ticketType->fee, 2),
            'qr_code_url' => $this->qrCodeService->getParticipantQRCodeUrl($this->participant),
            'payment_status' => $this->participant->is_paid ? 'Paid' : 'Unpaid',
        ];
    }

    /**
     * Get default email content when no template is provided.
     */
    private function getDefaultEmailContent(): string
    {
        $workshop = $this->participant->workshop;
        $ticketType = $this->participant->ticketType;

        return "
        <h2>Your Workshop Ticket</h2>
        
        <p>Dear {$this->participant->name},</p>
        
        <p>Thank you for registering for <strong>{$workshop->name}</strong>!</p>
        
        <h3>Workshop Details:</h3>
        <ul>
            <li><strong>Date & Time:</strong> {$workshop->date_time->format('F j, Y \a\t g:i A')}</li>
            <li><strong>Location:</strong> {$workshop->location}</li>
            <li><strong>Ticket Type:</strong> {$ticketType->name}</li>
            <li><strong>Fee:</strong> $" . number_format($ticketType->fee, 2) . "</li>
        </ul>
        
        <h3>Your Ticket Information:</h3>
        <ul>
            <li><strong>Ticket Code:</strong> {$this->participant->ticket_code}</li>
            <li><strong>Payment Status:</strong> " . ($this->participant->is_paid ? 'Paid' : 'Unpaid') . "</li>
        </ul>
        
        <p>Please bring this email or show the QR code attached for check-in at the workshop.</p>
        
        <p>If you have any questions, please contact us.</p>
        
        <p>Best regards,<br>Workshop Management Team</p>
        ";
    }

    /**
     * Build the message with custom styling.
     */
    public function build()
    {
        return $this->subject($this->getEmailSubject())
            ->view('emails.ticket')
            ->with([
                'participant' => $this->participant,
                'workshop' => $this->participant->workshop,
                'ticketType' => $this->participant->ticketType,
                'emailContent' => $this->getEmailContent(),
                'qrCodeDataUri' => $this->qrCodeService->generateQRCodeDataUri($this->participant->ticket_code),
                'templateVariables' => $this->getTemplateVariables(),
            ]);
    }
}
