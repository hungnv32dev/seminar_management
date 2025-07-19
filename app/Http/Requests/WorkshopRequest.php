<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkshopRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'date_time' => 'required|date|after:now',
            'location' => 'required|string|max:255',
            'status' => [
                'required',
                Rule::in(['draft', 'published', 'ongoing', 'completed', 'cancelled'])
            ],
            'organizers' => 'nullable|array',
            'organizers.*' => 'exists:users,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Workshop name is required.',
            'name.max' => 'Workshop name cannot exceed 255 characters.',
            'description.max' => 'Description cannot exceed 5000 characters.',
            'date_time.required' => 'Workshop date and time is required.',
            'date_time.date' => 'Please provide a valid date and time.',
            'date_time.after' => 'Workshop date must be in the future.',
            'location.required' => 'Workshop location is required.',
            'location.max' => 'Location cannot exceed 255 characters.',
            'status.required' => 'Workshop status is required.',
            'status.in' => 'Invalid workshop status.',
            'organizers.array' => 'Organizers must be an array.',
            'organizers.*.exists' => 'Selected organizer does not exist.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'date_time' => 'date and time',
            'organizers.*' => 'organizer',
        ];
    }
}
