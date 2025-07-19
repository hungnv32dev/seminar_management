<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ParticipantRequest extends FormRequest
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
        $participantId = $this->route('participant') ? $this->route('participant')->id : null;
        $workshopId = $this->input('workshop_id');

        return [
            'workshop_id' => 'required|exists:workshops,id',
            'ticket_type_id' => 'required|exists:ticket_types,id',
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('participants')
                    ->where('workshop_id', $workshopId)
                    ->ignore($participantId),
            ],
            'phone' => 'nullable|string|max:20',
            'occupation' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:1000',
            'company' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'is_paid' => 'boolean',
            'is_checked_in' => 'boolean',
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
            'ticket_type_id.required' => 'Ticket type is required.',
            'ticket_type_id.exists' => 'Selected ticket type does not exist.',
            'name.required' => 'Participant name is required.',
            'name.max' => 'Name cannot exceed 255 characters.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already registered for this workshop.',
            'email.max' => 'Email cannot exceed 255 characters.',
            'phone.max' => 'Phone number cannot exceed 20 characters.',
            'occupation.max' => 'Occupation cannot exceed 255 characters.',
            'address.max' => 'Address cannot exceed 1000 characters.',
            'company.max' => 'Company name cannot exceed 255 characters.',
            'position.max' => 'Position cannot exceed 255 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'workshop_id' => 'workshop',
            'ticket_type_id' => 'ticket type',
            'is_paid' => 'payment status',
            'is_checked_in' => 'check-in status',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validate that ticket type belongs to the selected workshop
            if ($this->filled(['workshop_id', 'ticket_type_id'])) {
                $ticketType = \App\Models\TicketType::find($this->ticket_type_id);
                if ($ticketType && $ticketType->workshop_id != $this->workshop_id) {
                    $validator->errors()->add('ticket_type_id', 'Selected ticket type does not belong to the selected workshop.');
                }
            }
        });
    }
}
