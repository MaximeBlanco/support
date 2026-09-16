<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One line of an append-only journal: who changed which attribute, and to what.
 *
 * A journal is never edited or re-timestamped, so it carries no updated_at and
 * refuses writes once created.
 */
#[Fillable(['author_id', 'attribute', 'old_value', 'new_value'])]
class AttributeChange extends Model
{
    use Prunable;

    public const UPDATED_AT = null;

    private const RETENTION_MONTHS = 18;

    /**
     * @return MorphTo<Model, $this>
     */
    public function recordable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<=', now()->subMonths(self::RETENTION_MONTHS));
    }

    protected static function booted(): void
    {
        static::updating(fn (): bool => false);
        static::deleting(fn (self $change): bool => $change->isPrunableDeletion());
    }

    private function isPrunableDeletion(): bool
    {
        return $this->created_at !== null
            && $this->created_at->lessThanOrEqualTo(now()->subMonths(self::RETENTION_MONTHS));
    }
}
