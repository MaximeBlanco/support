<?php

namespace App\Actions\Tickets;

use App\Enums\TicketPriority;
use App\Events\TicketEscalated;
use App\Models\Builders\TicketBuilder;
use App\Models\Ticket;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Finds the open tickets that blew through the target their priority promised.
 *
 * The overdue test depends on a column, so it cannot be one flat where clause.
 * It is one query per priority — four, whatever the number of tickets — rather
 * than loading every open ticket and filtering in PHP.
 */
class EscalateOverdueTickets
{
    public function handle(?Carbon $now = null): EscalationReport
    {
        return $this->run($now ?? now(), apply: true);
    }

    public function preview(?Carbon $now = null): EscalationReport
    {
        return $this->run($now ?? now(), apply: false);
    }

    private function run(Carbon $now, bool $apply): EscalationReport
    {
        $examined = 0;
        $escalated = collect();
        $flagged = collect();

        foreach (TicketPriority::cases() as $priority) {
            $overdue = $this->overdueFor($priority, $now);
            $examined += $overdue->count();

            foreach ($overdue as $ticket) {
                $target = $priority->next();

                if ($apply) {
                    $this->raise($ticket, $priority, $target, $now);
                }

                $target === null
                    ? $flagged->push($ticket)
                    : $escalated->push($ticket);
            }
        }

        return new EscalationReport($examined, $escalated, $flagged);
    }

    private function raise(Ticket $ticket, TicketPriority $from, ?TicketPriority $to, Carbon $now): void
    {
        $ticket->forceFill([
            'priority' => $to ?? $from,
            'escalated_at' => $now,
            'escalation_count' => $ticket->escalation_count + 1,
        ])->save();

        TicketEscalated::dispatch($ticket, $from, $to);
    }

    /**
     * @return Collection<int, Ticket>
     */
    private function overdueFor(TicketPriority $priority, Carbon $now): Collection
    {
        $deadline = $now->copy()->subHours($priority->targetResolutionHours());

        return Ticket::query()
            ->whereStillOpen()
            ->where('priority', $priority)
            ->where('created_at', '<=', $deadline)
            ->where(function (TicketBuilder $query) use ($deadline): void {
                $query->whereNull('escalated_at')
                    ->orWhere('escalated_at', '<=', $deadline);
            })
            ->get();
    }
}
