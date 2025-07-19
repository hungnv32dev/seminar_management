<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TicketTypeRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'fee' => 'required|numeric|min:0|max:999999.99',
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
            'name.required' => 'Ticket type name is required.',
            'name.max' => 'Ticket type name cannot exceed 255 characters.',
            'fee.required' => 'Fee is required.',
            'fee.numeric' => 'Fee must be a valid number.',
            'fee.min' => 'Fee cannot be negative.',
            'fee.max' => 'Fee cannot exceed 999,999.99.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'workshop_id' => 'workshop',
            'fee' => 'ticket fee',
        ];
    }
}
