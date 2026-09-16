<?php

namespace App\Console\Commands;

use App\Actions\Tickets\EscalateOverdueTickets as EscalateOverdueTicketsAction;
use App\Actions\Tickets\EscalationReport;
use App\Models\Ticket;
use Illuminate\Console\Command;

class EscalateOverdueTickets extends Command
{
    protected $signature = 'tickets:escalate {--dry-run : Report what would be escalated without writing anything}';

    protected $description = 'Raise the priority of the open tickets that blew past their target resolution time';

    public function handle(EscalateOverdueTicketsAction $escalate): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->components->warn(__('console.escalate.dry_run'));
        }

        $report = $dryRun ? $escalate->preview() : $escalate->handle();

        $this->components->info(trans_choice('console.escalate.examined', $report->examined, ['count' => $report->examined]));

        if ($report->changedNothing()) {
            $this->components->info(__('console.escalate.nothing'));

            return self::SUCCESS;
        }

        $this->render($report);

        return self::SUCCESS;
    }

    private function render(EscalationReport $report): void
    {
        $this->table(
            [__('ticket.fields.reference'), __('ticket.fields.title'), __('ticket.fields.priority')],
            $report->escalated
                ->merge($report->flagged)
                ->map(fn (Ticket $ticket): array => [
                    $ticket->reference,
                    str($ticket->title)->limit(40)->value(),
                    $ticket->priority->label(),
                ])
                ->all(),
        );

        $this->components->info(trans_choice(
            'console.escalate.raised',
            $report->escalated->count(),
            ['count' => $report->escalated->count()],
        ));

        if ($report->flagged->isNotEmpty()) {
            $this->components->warn(trans_choice(
                'console.escalate.capped',
                $report->flagged->count(),
                ['count' => $report->flagged->count()],
            ));
        }
    }
}
