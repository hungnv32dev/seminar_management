<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Participant extends Model
{
    use HasFactory;
    protected $fillable = [
        'workshop_id',
        'ticket_type_id',
        'name',
        'email',
        'phone',
        'occupation',
        'address',
        'company',
        'position',
        'ticket_code',
        'is_paid',
        'is_checked_in',
    ];

    protected function casts(): array
    {
        return [
            'is_paid' => 'boolean',
            'is_checked_in' => 'boolean',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($participant) {
            if (empty($participant->ticket_code)) {
                $participant->ticket_code = $participant->generateTicketCode();
            }
        });
    }

    /**
     * Get the workshop that owns the participant.
     */
    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }

    /**
     * Get the ticket type that owns the participant.
     */
    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    /**
     * Scope a query to only include paid participants.
     */
    public function scopePaid(Builder $query): void
    {
        $query->where('is_paid', true);
    }

    /**
     * Scope a query to only include checked-in participants.
     */
    public function scopeCheckedIn(Builder $query): void
    {
        $query->where('is_checked_in', true);
    }

    /**
     * Generate a unique ticket code.
     */
    public function generateTicketCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (self::where('ticket_code', $code)->exists());

        return $code;
    }
}
