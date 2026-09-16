<?php

namespace App\Models\Concerns;

use App\Models\AttributeChange;
use BackedEnum;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Journals the attributes a model says it cares about.
 *
 * This is not an Observer, and the difference is not cosmetic. An Observer is
 * registered elsewhere and watches the model from the outside, so reading the
 * model tells you nothing about what happens when it is saved. Here the model
 * itself declares the behaviour — `use RecordsAttributeChanges` plus the list
 * of attributes — so the journal is visible at the only place someone looks.
 *
 * A model opts in by using the trait and listing what it wants kept:
 *
 *     public function recordedAttributes(): array
 *     {
 *         return ['status', 'priority'];
 *     }
 */
trait RecordsAttributeChanges
{
    /**
     * @return array<int, string>
     */
    abstract public function recordedAttributes(): array;

    public static function bootRecordsAttributeChanges(): void
    {
        static::updated(function (Model $model): void {
            $model->journalChangedAttributes();
        });
    }

    /**
     * @return MorphMany<AttributeChange, $this>
     */
    public function attributeChanges(): MorphMany
    {
        return $this->morphMany(AttributeChange::class, 'recordable')->latest();
    }

    public function journalChangedAttributes(): void
    {
        $now = now();

        $rows = [];

        foreach ($this->recordedAttributes() as $attribute) {
            if (! $this->wasChanged($attribute)) {
                continue;
            }

            $rows[] = [
                'recordable_type' => $this->getMorphClass(),
                'recordable_id' => $this->getKey(),
                'author_id' => auth()->id(),
                'attribute' => $attribute,
                'old_value' => $this->journalValue($this->getOriginal($attribute)),
                'new_value' => $this->journalValue($this->getAttribute($attribute)),
                'created_at' => $now,
            ];
        }

        if ($rows === []) {
            return;
        }

        AttributeChange::query()->insert($rows);
    }

    private function journalValue(mixed $value): ?string
    {
        return match (true) {
            $value === null => null,
            $value instanceof BackedEnum => (string) $value->value,
            $value instanceof DateTimeInterface => $value->format(DateTimeInterface::ATOM),
            is_bool($value) => $value ? '1' : '0',
            default => (string) $value,
        };
    }
}
