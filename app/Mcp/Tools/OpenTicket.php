<?php

namespace App\Mcp\Tools;

use App\Actions\Tickets\CreateTicket;
use App\Enums\TicketPriority;
use App\Models\Ticket;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

/**
 * Opens a ticket through the very action the interface and the CSV import use.
 *
 * Nothing about how a ticket comes into being is restated here: the reference,
 * the opening status and the first line of the history all come from the domain,
 * so a rule changed there changes for the agent too.
 */
#[Description('Open a new support ticket on behalf of the authenticated user.')]
class OpenTicket extends Tool
{
    public function handle(Request $request, CreateTicket $createTicket): Response
    {
        $user = $request->user();

        try {
            $this->authorizeOpening($user);
        } catch (AuthorizationException $exception) {
            return Response::error($exception->getMessage());
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'min:5', 'max:150'],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
            'priority' => ['required', 'string', 'in:'.implode(',', array_column(TicketPriority::cases(), 'value'))],
        ]);

        $priority = TicketPriority::from($validated['priority']);

        $ticket = $createTicket->handle(
            $user,
            $validated['title'],
            $validated['description'],
            $priority,
        );

        return Response::json([
            'reference' => $ticket->reference,
            'status' => $ticket->status->value,
            'priority' => $ticket->priority->value,
            'target_resolution_hours' => $priority->targetResolutionHours(),
        ]);
    }

    private function authorizeOpening(mixed $user): void
    {
        if ($user === null || $user->cannot('create', Ticket::class)) {
            throw new AuthorizationException('You are not allowed to open a ticket.');
        }
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()
                ->description('One sentence naming the problem, between 5 and 150 characters.')
                ->required(),

            'description' => $schema->string()
                ->description('What happens, what was already tried, and since when. At least 10 characters.')
                ->required(),

            'priority' => $schema->string()
                ->enum(array_column(TicketPriority::cases(), 'value'))
                ->description('How fast it must be handled. Read the ticket-rules resource for the target each level promises.')
                ->required(),
        ];
    }
}
