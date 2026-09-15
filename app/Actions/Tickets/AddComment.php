<?php

namespace App\Actions\Tickets;

use App\Models\Comment;
use App\Models\Ticket;
use App\Models\User;

class AddComment
{
    public function handle(Ticket $ticket, User $author, string $body): Comment
    {
        return $ticket->comments()->create([
            'author_id' => $author->getKey(),
            'body' => $body,
        ]);
    }
}
