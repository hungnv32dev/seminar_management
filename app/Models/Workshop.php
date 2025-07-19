<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Workshop extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
        'date_time',
        'location',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'date_time' => 'datetime',
        ];
    }

    /**
     * The organizers that belong to the workshop.
     */
    public function organizers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_workshop');
    }

    /**
     * Get the ticket types for the workshop.
     */
    public function ticketTypes(): HasMany
    {
        return $this->hasMany(TicketType::class);
    }

    /**
     * Get the participants for the workshop.
     */
    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    /**
     * Get the email templates for the workshop.
     */
    public function emailTemplates(): HasMany
    {
        return $this->hasMany(EmailTemplate::class);
    }

    /**
     * Scope a query to only include active workshops.
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereIn('status', ['published', 'ongoing']);
    }

    /**
     * Scope a query to only include upcoming workshops.
     */
    public function scopeUpcoming(Builder $query): void
    {
        $query->where('date_time', '>', Carbon::now())
              ->whereIn('status', ['published', 'draft']);
    }

    /**
     * Scope a query to only include past workshops.
     */
    public function scopePast(Builder $query): void
    {
        $query->where('date_time', '<', Carbon::now())
              ->whereIn('status', ['completed', 'cancelled']);
    }
}
