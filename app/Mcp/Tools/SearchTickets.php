<?php

namespace App\Mcp\Tools;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Builders\TicketBuilder;
use App\Models\Ticket;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Search the support tickets the authenticated user is allowed to see. Returns the reference, title, status, priority, requester and assignee of each match.')]
class SearchTickets extends Tool
{
    private const MAX_RESULTS = 50;

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:'.implode(',', array_column(TicketStatus::cases(), 'value'))],
            'priority' => ['nullable', 'string', 'in:'.implode(',', array_column(TicketPriority::cases(), 'value'))],
            'search' => ['nullable', 'string', 'max:150'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_RESULTS],
        ]);

        $tickets = Ticket::query()
            ->visibleTo($request->user())
            ->with(['requester:id,name', 'assignee:id,name'])
            ->whereStatus(TicketStatus::tryFrom($validated['status'] ?? ''))
            ->when(
                TicketPriority::tryFrom($validated['priority'] ?? ''),
                fn (TicketBuilder $query, TicketPriority $priority): TicketBuilder => $query->where('priority', $priority),
            )
            ->search($validated['search'] ?? null)
            ->sortBy('created_at', 'desc')
            ->limit($validated['limit'] ?? 20)
            ->get();

        if ($tickets->isEmpty()) {
            return Response::text('No ticket matches this search.');
        }

        return Response::json([
            'count' => $tickets->count(),
            'tickets' => $tickets->map(fn (Ticket $ticket): array => [
                'reference' => $ticket->reference,
                'title' => $ticket->title,
                'status' => $ticket->status->value,
                'priority' => $ticket->priority->value,
                'target_resolution_hours' => $ticket->priority->targetResolutionHours(),
                'requester' => $ticket->requester->name,
                'assignee' => $ticket->assignee?->name,
                'created_at' => $ticket->created_at->toIso8601String(),
            ])->all(),
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()
                ->enum(array_column(TicketStatus::cases(), 'value'))
                ->description('Keep only the tickets in this status.'),

            'priority' => $schema->string()
                ->enum(array_column(TicketPriority::cases(), 'value'))
                ->description('Keep only the tickets at this priority.'),

            'search' => $schema->string()
                ->description('Free text matched against the reference, the title and the description.'),

            'limit' => $schema->integer()
                ->description('How many tickets to return, at most '.self::MAX_RESULTS.'. Defaults to 20.'),
        ];
    }
}
