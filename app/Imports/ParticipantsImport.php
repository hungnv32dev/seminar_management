<?php

namespace App\Imports;

use App\Models\Participant;
use App\Models\Workshop;
use App\Models\TicketType;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Validation\Rule;

class ParticipantsImport implements ToModel, WithHeadingRow, WithValidation, WithBatchInserts, WithChunkReading
{
    protected $workshopId;
    protected $ticketTypeId;
    protected $errors = [];

    public function __construct($workshopId, $ticketTypeId = null)
    {
        $this->workshopId = $workshopId;
        $this->ticketTypeId = $ticketTypeId;
    }

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Skip empty rows
        if (empty($row['name']) || empty($row['email'])) {
            return null;
        }

        // Determine ticket type
        $ticketTypeId = $this->ticketTypeId;
        if (!$ticketTypeId && isset($row['ticket_type'])) {
            $ticketType = TicketType::where('workshop_id', $this->workshopId)
                ->where('name', $row['ticket_type'])
                ->first();
            $ticketTypeId = $ticketType ? $ticketType->id : null;
        }

        // Use default ticket type if none specified
        if (!$ticketTypeId) {
            $defaultTicketType = TicketType::where('workshop_id', $this->workshopId)->first();
            $ticketTypeId = $defaultTicketType ? $defaultTicketType->id : null;
        }

        if (!$ticketTypeId) {
            return null; // Skip if no valid ticket type
        }

        return new Participant([
            'workshop_id' => $this->workshopId,
            'ticket_type_id' => $ticketTypeId,
            'name' => $row['name'],
            'email' => $row['email'],
            'phone' => $row['phone'] ?? null,
            'occupation' => $row['occupation'] ?? null,
            'address' => $row['address'] ?? null,
            'company' => $row['company'] ?? null,
            'position' => $row['position'] ?? null,
            'is_paid' => isset($row['is_paid']) ? 
                (strtolower($row['is_paid']) === 'yes' || $row['is_paid'] === '1' || strtolower($row['is_paid']) === 'true') : false,
            'is_checked_in' => false, // Default to not checked in
        ]);
    }

    /**
     * Validation rules for each row.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('participants')
                    ->where('workshop_id', $this->workshopId),
            ],
            'phone' => 'nullable|string|max:20',
            'occupation' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:1000',
            'company' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
        ];
    }

    /**
     * Custom validation messages.
     */
    public function customValidationMessages()
    {
        return [
            'name.required' => 'Name is required.',
            'email.required' => 'Email is required.',
            'email.email' => 'Email must be valid.',
            'email.unique' => 'Email already exists for this workshop.',
        ];
    }

    /**
     * Batch size for processing.
     */
    public function batchSize(): int
    {
        return 100;
    }

    /**
     * Chunk size for reading.
     */
    public function chunkSize(): int
    {
        return 100;
    }

    /**
     * Get import errors.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
