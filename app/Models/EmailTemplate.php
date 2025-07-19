<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailTemplate extends Model
{
    protected $fillable = [
        'workshop_id',
        'type',
        'subject',
        'content',
    ];

    /**
     * Get the workshop that owns the email template.
     */
    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }

    /**
     * Render the email content with variable substitution.
     */
    public function renderContent(array $variables = []): string
    {
        $content = $this->content;
        
        foreach ($variables as $key => $value) {
            $content = str_replace('{{ ' . $key . ' }}', $value, $content);
        }
        
        return $content;
    }

    /**
     * Render the email subject with variable substitution.
     */
    public function renderSubject(array $variables = []): string
    {
        $subject = $this->subject;
        
        foreach ($variables as $key => $value) {
            $subject = str_replace('{{ ' . $key . ' }}', $value, $subject);
        }
        
        return $subject;
    }

    /**
     * Get available template variables.
     */
    public static function getAvailableVariables(): array
    {
        return [
            'name' => 'Participant name',
            'email' => 'Participant email',
            'workshop_name' => 'Workshop name',
            'workshop_date' => 'Workshop date',
            'workshop_location' => 'Workshop location',
            'ticket_code' => 'Ticket code',
            'qr_code_url' => 'QR code URL',
        ];
    }
}
