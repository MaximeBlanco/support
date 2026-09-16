<?php

namespace App\Imports\Steps;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Imports\ImportRow;
use App\Imports\ImportStep;
use App\Models\Ticket;
use App\Models\TicketStatusChange;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Writes the surviving rows, all of them or none.
 *
 * The boundary is deliberate. An import is a single operator gesture, and a
 * half-written one leaves nobody able to say what landed: the rejected lines are
 * reported with their line number, so the fix is to correct the file and send it
 * again rather than to reconcile a partial run by hand.
 */
class CreateTickets implements ImportStep
{
    private const CHUNK = 500;

    /**
     * @param  array<int, ImportRow>  $rows
     * @return array<int, ImportRow>
     */
    public function handle(array $rows): array
    {
        $standing = array_values(array_filter($rows, fn (ImportRow $row): bool => ! $row->isRejected()));

        if ($standing === []) {
            return $rows;
        }

        DB::transaction(function () use ($standing): void {
            $sequence = (int) Ticket::withTrashed()->max('id');
            $now = now();
            $tickets = [];

            foreach ($standing as $row) {
                $reference = 'TCK-'.str_pad((string) ++$sequence, 5, '0', STR_PAD_LEFT);
                $row->set('reference', $reference);

                $tickets[] = [
                    'reference' => $reference,
                    'requester_id' => $row->get('requester_id'),
                    'title' => $row->get('title'),
                    'description' => $row->get('description'),
                    'status' => TicketStatus::Open->value,
                    'priority' => TicketPriority::from((string) $row->get('priority'))->value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($tickets, self::CHUNK) as $chunk) {
                Ticket::query()->insert($chunk);
            }

            $this->openTheTimeline(array_column($tickets, 'reference'), $now);
        });

        return $rows;
    }

    /**
     * @param  array<int, string>  $references
     */
    private function openTheTimeline(array $references, Carbon $now): void
    {
        foreach (array_chunk($references, self::CHUNK) as $chunk) {
            $created = Ticket::query()
                ->whereIn('reference', $chunk)
                ->get(['id', 'requester_id']);

            TicketStatusChange::query()->insert(
                $created->map(fn (Ticket $ticket): array => [
                    'ticket_id' => $ticket->getKey(),
                    'author_id' => $ticket->requester_id,
                    'from_status' => null,
                    'to_status' => TicketStatus::Open->value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all(),
            );
        }
    }
}
