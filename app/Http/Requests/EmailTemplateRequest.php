<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmailTemplateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'workshop_id' => 'required|exists:workshops,id',
            'type' => [
                'required',
                Rule::in(['invite', 'confirm', 'ticket', 'reminder', 'thank_you'])
            ],
            'subject' => 'required|string|max:255',
            'content' => 'required|string|max:10000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'workshop_id.required' => 'Workshop is required.',
            'workshop_id.exists' => 'Selected workshop does not exist.',
            'type.required' => 'Email template type is required.',
            'type.in' => 'Invalid email template type. Must be one of: invite, confirm, ticket, reminder, thank_you.',
            'subject.required' => 'Email subject is required.',
            'subject.max' => 'Email subject cannot exceed 255 characters.',
            'content.required' => 'Email content is required.',
            'content.max' => 'Email content cannot exceed 10,000 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'workshop_id' => 'workshop',
            'type' => 'template type',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Check for duplicate template type per workshop (except when updating)
            if ($this->filled(['workshop_id', 'type'])) {
                $emailTemplateId = $this->route('email_template') ? $this->route('email_template')->id : null;
                
                $existingTemplate = \App\Models\EmailTemplate::where('workshop_id', $this->workshop_id)
                    ->where('type', $this->type)
                    ->when($emailTemplateId, function ($query) use ($emailTemplateId) {
                        return $query->where('id', '!=', $emailTemplateId);
                    })
                    ->first();

                if ($existingTemplate) {
                    $validator->errors()->add('type', 'An email template of this type already exists for this workshop.');
                }
            }

            // Validate template variables
            $this->validateTemplateVariables($validator);
        });
    }

    /**
     * Validate template variables in subject and content.
     */
    private function validateTemplateVariables($validator): void
    {
        $availableVariables = \App\Models\EmailTemplate::getAvailableVariables();
        
        // Check subject variables
        if ($this->filled('subject')) {
            $subjectVariables = $this->extractVariables($this->subject);
            foreach ($subjectVariables as $variable) {
                if (!array_key_exists($variable, $availableVariables)) {
                    $validator->errors()->add('subject', "Invalid variable in subject: {{ {$variable} }}. Available variables: " . implode(', ', array_keys($availableVariables)));
                }
            }
        }
        
        // Check content variables
        if ($this->filled('content')) {
            $contentVariables = $this->extractVariables($this->content);
            foreach ($contentVariables as $variable) {
                if (!array_key_exists($variable, $availableVariables)) {
                    $validator->errors()->add('content', "Invalid variable in content: {{ {$variable} }}. Available variables: " . implode(', ', array_keys($availableVariables)));
                }
            }
        }
    }

    /**
     * Extract variables from template text.
     */
    private function extractVariables(string $text): array
    {
        preg_match_all('/\{\{\s*(\w+)\s*\}\}/', $text, $matches);
        return $matches[1] ?? [];
    }
}
