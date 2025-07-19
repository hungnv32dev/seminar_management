<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
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
     */
    public function rules(): array
    {
        $userId = $this->route('user') ? $this->route('user')->id : null;
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z\s\-\'\.]+$/', // Only letters, spaces, hyphens, apostrophes, and dots
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($userId),
            ],
            'password' => [
                $isUpdate ? 'nullable' : 'required',
                'string',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
                'confirmed',
            ],
            'password_confirmation' => [
                $isUpdate ? 'nullable' : 'required_with:password',
                'string',
            ],
            'role_id' => [
                'required',
                'exists:roles,id',
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'name.string' => 'The name must be a valid string.',
            'name.max' => 'The name may not be greater than 255 characters.',
            'name.regex' => 'The name may only contain letters, spaces, hyphens, apostrophes, and dots.',
            
            'email.required' => 'The email field is required.',
            'email.email' => 'The email must be a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'email.max' => 'The email may not be greater than 255 characters.',
            
            'password.required' => 'The password field is required.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.confirmed' => 'The password confirmation does not match.',
            'password.uncompromised' => 'The given password has appeared in a data leak. Please choose a different password.',
            
            'password_confirmation.required_with' => 'The password confirmation field is required when password is present.',
            
            'role_id.required' => 'Please select a role for the user.',
            'role_id.exists' => 'The selected role is invalid.',
            
            'is_active.boolean' => 'The active status must be true or false.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'full name',
            'email' => 'email address',
            'password' => 'password',
            'password_confirmation' => 'password confirmation',
            'role_id' => 'role',
            'is_active' => 'active status',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Trim whitespace from name and email
        if ($this->has('name')) {
            $this->merge([
                'name' => trim($this->name),
            ]);
        }

        if ($this->has('email')) {
            $this->merge([
                'email' => strtolower(trim($this->email)),
            ]);
        }

        // Convert role_id to integer if present
        if ($this->has('role_id') && $this->role_id !== null) {
            $this->merge([
                'role_id' => (int) $this->role_id,
            ]);
        }

        // Handle is_active checkbox
        if (!$this->has('is_active')) {
            $this->merge([
                'is_active' => false,
            ]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Additional custom validation logic can be added here
            
            // Check if trying to deactivate the current user
            if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
                $user = $this->route('user');
                if ($user && $user->id === auth()->id() && $this->has('is_active') && !$this->boolean('is_active')) {
                    $validator->errors()->add('is_active', 'You cannot deactivate your own account.');
                }
            }

            // Validate role permissions (if needed)
            if ($this->has('role_id')) {
                $role = \App\Models\Role::find($this->role_id);
                if ($role) {
                    // Add any role-specific validation logic here
                    // For example, check if current user can assign this role
                }
            }
        });
    }

    /**
     * Get the validated data from the request with additional processing.
     */
    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated($key, $default);

        // Remove password confirmation from validated data
        unset($validated['password_confirmation']);

        // Remove empty password for updates
        if (($this->isMethod('PUT') || $this->isMethod('PATCH')) && empty($validated['password'])) {
            unset($validated['password']);
        }

        return $validated;
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        // Log validation failures for security monitoring
        if (app()->environment('production')) {
            \Illuminate\Support\Facades\Log::warning('User form validation failed', [
                'errors' => $validator->errors()->toArray(),
                'input' => $this->except(['password', 'password_confirmation']),
                'user_id' => auth()->id(),
                'ip' => $this->ip(),
            ]);
        }

        parent::failedValidation($validator);
    }
}