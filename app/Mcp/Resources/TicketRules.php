<?php

namespace App\Mcp\Resources;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Resource;

/**
 * Tells an agent what a valid ticket looks like before it tries to write one.
 *
 * Everything here is read from the enums, so the rules an agent is given can
 * never drift from the rules the domain enforces.
 */
#[Description('The shape of a ticket: the fields required to open one, the priorities and what each promises, and the legal status transitions.')]
class TicketRules extends Resource
{
    public function handle(Request $request): Response
    {
        return Response::json([
            'fields' => [
                'title' => 'Required. Between 5 and 150 characters.',
                'description' => 'Required. At least 10 characters, up to 5000.',
                'priority' => 'Required. One of: '.implode(', ', array_column(TicketPriority::cases(), 'value')).'.',
            ],
            'priorities' => array_map(
                fn (TicketPriority $priority): array => [
                    'value' => $priority->value,
                    'target_resolution_hours' => $priority->targetResolutionHours(),
                ],
                TicketPriority::cases(),
            ),
            'statuses' => array_map(
                fn (TicketStatus $status): array => [
                    'value' => $status->value,
                    'may_move_to' => array_column($status->allowedTransitions(), 'value'),
                    'is_terminal' => $status->isTerminal(),
                ],
                TicketStatus::cases(),
            ),
            'notes' => [
                'A ticket always opens in the "open" status; the reference is assigned by the server.',
                'Any transition missing from "may_move_to" is refused by the domain, not merely hidden by the interface.',
                'You only ever see the tickets the authenticated user is allowed to read.',
            ],
        ]);
    }
}
