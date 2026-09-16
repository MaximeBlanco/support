<?php

namespace App\Mcp\Servers;

use App\Mcp\Resources\TicketRules;
use App\Mcp\Tools\GetTicket;
use App\Mcp\Tools\OpenTicket;
use App\Mcp\Tools\SearchTickets;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Support')]
#[Version('1.0.0')]
#[Instructions(<<<'TEXT'
This server exposes the internal support desk.

You act as a real person: every tool runs under the authenticated user, and you
only ever see the tickets that user is allowed to read. A requester sees their
own tickets, a technician the ones assigned to them, a manager all of them.

Read the ticket-rules resource before opening a ticket — it carries the required
fields, the priorities with the resolution target each one promises, and the
legal status transitions. A refused operation comes back as an explicit error
saying what was refused and why; treat it as a fact about the domain rather than
something to retry differently.
TEXT)]
class SupportServer extends Server
{
    protected array $tools = [
        SearchTickets::class,
        GetTicket::class,
        OpenTicket::class,
    ];

    protected array $resources = [
        TicketRules::class,
    ];

    protected array $prompts = [
        //
    ];
}
