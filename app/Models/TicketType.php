<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class TicketType extends Model
{
    use HasFactory;
    protected $fillable = [
        'workshop_id',
        'name',
        'fee',
    ];

    protected function casts(): array
    {
        return [
            'fee' => 'decimal:2',
        ];
    }

    /**
     * Get the workshop that owns the ticket type.
     */
    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }

    /**
     * Get the participants for the ticket type.
     */
    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    /**
     * Scope a query to only include ticket types for a specific workshop.
     */
    public function scopeForWorkshop(Builder $query, int $workshopId): void
    {
        $query->where('workshop_id', $workshopId);
    }

    /**
     * Check if the ticket type can be deleted (no participants assigned).
     */
    public function canBeDeleted(): bool
    {
        return $this->participants()->count() === 0;
    }
}
