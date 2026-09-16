<?php

namespace App\Imports;

class ImportReport
{
    /**
     * @param  array<int, array{line: int, reason: string}>  $rejections
     */
    public function __construct(
        public readonly int $read = 0,
        public readonly int $created = 0,
        public readonly array $rejections = [],
    ) {}

    /**
     * @param  array<int, ImportRow>  $rows
     */
    public static function from(array $rows): self
    {
        $rejected = array_values(array_filter($rows, fn (ImportRow $row): bool => $row->isRejected()));

        return new self(
            read: count($rows),
            created: count($rows) - count($rejected),
            rejections: array_map(
                fn (ImportRow $row): array => ['line' => $row->number, 'reason' => (string) $row->rejection],
                $rejected,
            ),
        );
    }

    public function rejectedCount(): int
    {
        return count($this->rejections);
    }

    public function isClean(): bool
    {
        return $this->rejections === [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'read' => $this->read,
            'created' => $this->created,
            'rejections' => $this->rejections,
        ];
    }
}
