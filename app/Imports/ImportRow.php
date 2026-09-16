<?php

namespace App\Imports;

/**
 * One line of the file as it travels down the pipeline.
 *
 * It carries its line number from end to end: a rejection nobody can locate in
 * the source file is not a usable report.
 */
class ImportRow
{
    /**
     * @param  array<string, string|null>  $values
     */
    public function __construct(
        public readonly int $number,
        public array $values,
        public ?string $rejection = null,
    ) {}

    public function get(string $key): ?string
    {
        return $this->values[$key] ?? null;
    }

    public function set(string $key, mixed $value): void
    {
        $this->values[$key] = $value;
    }

    public function reject(string $reason): void
    {
        $this->rejection ??= $reason;
    }

    public function isRejected(): bool
    {
        return $this->rejection !== null;
    }
}
