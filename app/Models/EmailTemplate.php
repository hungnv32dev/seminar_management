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
            'workshop_date' => 'Workshop date and time',
            'workshop_location' => 'Workshop location',
            'ticket_code' => 'Unique ticket code',
            'ticket_type' => 'Ticket type name',
            'ticket_fee' => 'Ticket fee amount',
            'qr_code_url' => 'QR code image URL',
            'payment_status' => 'Payment status (Paid/Unpaid)',
            'phone' => 'Participant phone number',
            'company' => 'Participant company',
            'position' => 'Participant position',
            'occupation' => 'Participant occupation',
        ];
    }

    /**
     * Advanced template rendering with Blade-like syntax support.
     */
    public function renderAdvanced(array $variables = []): array
    {
        $renderedSubject = $this->renderSubject($variables);
        $renderedContent = $this->renderContent($variables);

        // Additional processing for conditional statements
        $renderedContent = $this->processConditionals($renderedContent, $variables);
        
        return [
            'subject' => $renderedSubject,
            'content' => $renderedContent,
        ];
    }

    /**
     * Process conditional statements in template content.
     */
    private function processConditionals(string $content, array $variables): string
    {
        // Process @if statements
        $content = preg_replace_callback(
            '/@if\s*\(\s*(\w+)\s*\)(.*?)@endif/s',
            function ($matches) use ($variables) {
                $variable = $matches[1];
                $conditionalContent = $matches[2];
                
                if (isset($variables[$variable]) && $variables[$variable]) {
                    return $conditionalContent;
                }
                
                return '';
            },
            $content
        );

        // Process @unless statements
        $content = preg_replace_callback(
            '/@unless\s*\(\s*(\w+)\s*\)(.*?)@endunless/s',
            function ($matches) use ($variables) {
                $variable = $matches[1];
                $conditionalContent = $matches[2];
                
                if (!isset($variables[$variable]) || !$variables[$variable]) {
                    return $conditionalContent;
                }
                
                return '';
            },
            $content
        );

        return $content;
    }

    /**
     * Validate template syntax.
     */
    public function validateSyntax(): array
    {
        $errors = [];
        $availableVariables = self::getAvailableVariables();

        // Check subject for invalid variables
        $subjectVariables = $this->extractVariables($this->subject);
        foreach ($subjectVariables as $variable) {
            if (!array_key_exists($variable, $availableVariables)) {
                $errors[] = "Invalid variable in subject: {{ {$variable} }}";
            }
        }

        // Check content for invalid variables
        $contentVariables = $this->extractVariables($this->content);
        foreach ($contentVariables as $variable) {
            if (!array_key_exists($variable, $availableVariables)) {
                $errors[] = "Invalid variable in content: {{ {$variable} }}";
            }
        }

        // Check for unclosed variable tags
        if (preg_match('/\{\{(?![^}]*\}\})/', $this->subject . $this->content)) {
            $errors[] = "Unclosed variable tags found. Make sure all {{ are closed with }}";
        }

        // Check for malformed conditional statements
        $conditionalErrors = $this->validateConditionals($this->content);
        $errors = array_merge($errors, $conditionalErrors);

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
     * Validate conditional statements.
     */
    private function validateConditionals(string $content): array
    {
        $errors = [];

        // Check for unmatched @if/@endif
        $ifCount = preg_match_all('/@if\s*\([^)]+\)/', $content);
        $endifCount = preg_match_all('/@endif/', $content);
        
        if ($ifCount !== $endifCount) {
            $errors[] = "Unmatched @if/@endif statements. Found {$ifCount} @if and {$endifCount} @endif";
        }

        // Check for unmatched @unless/@endunless
        $unlessCount = preg_match_all('/@unless\s*\([^)]+\)/', $content);
        $endunlessCount = preg_match_all('/@endunless/', $content);
        
        if ($unlessCount !== $endunlessCount) {
            $errors[] = "Unmatched @unless/@endunless statements. Found {$unlessCount} @unless and {$endunlessCount} @endunless";
        }

        return $errors;
    }

    /**
     * Get template statistics.
     */
    public function getStatistics(): array
    {
        $subjectVariables = $this->extractVariables($this->subject);
        $contentVariables = $this->extractVariables($this->content);
        $allVariables = array_unique(array_merge($subjectVariables, $contentVariables));

        return [
            'subject_length' => strlen($this->subject),
            'content_length' => strlen($this->content),
            'total_variables' => count($allVariables),
            'subject_variables' => count($subjectVariables),
            'content_variables' => count($contentVariables),
            'variables_used' => $allVariables,
            'word_count' => str_word_count(strip_tags($this->content)),
            'has_conditionals' => $this->hasConditionals(),
        ];
    }

    /**
     * Check if template has conditional statements.
     */
    private function hasConditionals(): bool
    {
        return preg_match('/@(if|unless)\s*\([^)]+\)/', $this->content) > 0;
    }
}
