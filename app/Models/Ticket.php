<?php

namespace App\Models;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Builders\TicketBuilder;
use App\Models\Concerns\RecordsAttributeChanges;
use App\Policies\TicketPolicy;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[UseFactory(TicketFactory::class)]
#[UseEloquentBuilder(TicketBuilder::class)]
#[UsePolicy(TicketPolicy::class)]
#[Fillable(['reference', 'requester_id', 'assignee_id', 'title', 'description', 'status', 'priority'])]
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory, Prunable, RecordsAttributeChanges, SoftDeletes;

    private const RETENTION_MONTHS = 24;

    /**
     * @return array<int, string>
     */
    public function recordedAttributes(): array
    {
        return ['status', 'priority', 'assignee_id', 'title'];
    }

    /**
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        return static::onlyTrashed()->where('deleted_at', '<=', now()->subMonths(self::RETENTION_MONTHS));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'priority' => TicketPriority::class,
            'assigned_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'escalated_at' => 'datetime',
            'escalation_count' => 'integer',
            'resolved_within_target' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * @return HasMany<TicketStatusChange, $this>
     */
    public function statusChanges(): HasMany
    {
        return $this->hasMany(TicketStatusChange::class);
    }
}
