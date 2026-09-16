<?php

namespace App\Mcp\Tools;

use App\Models\Ticket;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Read one support ticket in full: its description, its comments and the history of its status changes.')]
class GetTicket extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'reference' => ['required', 'string', 'max:20'],
        ]);

        $ticket = Ticket::query()
            ->visibleTo($request->user())
            ->where('reference', $validated['reference'])
            ->with(['requester:id,name', 'assignee:id,name', 'comments.author:id,name', 'statusChanges.author:id,name'])
            ->first();

        if ($ticket === null) {
            return Response::error(sprintf(
                'No ticket [%s] is visible to you. Either it does not exist, or you are not allowed to read it.',
                $validated['reference'],
            ));
        }

        return Response::json([
            'reference' => $ticket->reference,
            'title' => $ticket->title,
            'description' => $ticket->description,
            'status' => $ticket->status->value,
            'priority' => $ticket->priority->value,
            'target_resolution_hours' => $ticket->priority->targetResolutionHours(),
            'requester' => $ticket->requester->name,
            'assignee' => $ticket->assignee?->name,
            'created_at' => $ticket->created_at->toIso8601String(),
            'resolved_at' => $ticket->resolved_at?->toIso8601String(),
            'resolved_within_target' => $ticket->resolved_within_target,
            'comments' => $ticket->comments->map(fn ($comment): array => [
                'author' => $comment->author->name,
                'body' => $comment->body,
                'at' => $comment->created_at->toIso8601String(),
            ])->all(),
            'history' => $ticket->statusChanges->map(fn ($change): array => [
                'from' => $change->from_status?->value,
                'to' => $change->to_status->value,
                'author' => $change->author?->name,
                'at' => $change->created_at->toIso8601String(),
            ])->all(),
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'reference' => $schema->string()
                ->description('The ticket reference, shaped like TCK-00042.')
                ->required(),
        ];
    }
}
