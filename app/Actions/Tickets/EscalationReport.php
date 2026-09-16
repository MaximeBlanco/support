<?php

namespace App\Actions\Tickets;

use App\Models\Ticket;
use Illuminate\Support\Collection;

class EscalationReport
{
    /**
     * @param  Collection<int, Ticket>  $escalated  tickets that moved up a level
     * @param  Collection<int, Ticket>  $flagged  already critical, reported but not raised
     */
    public function __construct(
        public readonly int $examined,
        public readonly Collection $escalated,
        public readonly Collection $flagged,
    ) {}

    public function changedNothing(): bool
    {
        return $this->escalated->isEmpty() && $this->flagged->isEmpty();
    }
}
