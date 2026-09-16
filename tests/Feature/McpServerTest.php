<?php

namespace Tests\Feature;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Mcp\Resources\TicketRules;
use App\Mcp\Servers\SupportServer;
use App\Mcp\Tools\GetTicket;
use App\Mcp\Tools\OpenTicket;
use App\Mcp\Tools\SearchTickets;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class McpServerTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();
        Notification::fake();
    }

    public function test_the_three_tools_and_the_resource_are_registered(): void
    {
        SupportServer::tools()
            ->assertRegistered(SearchTickets::class)
            ->assertRegistered(GetTicket::class)
            ->assertRegistered(OpenTicket::class);

        SupportServer::resources()->assertRegistered(TicketRules::class);
    }

    public function test_an_agent_signed_in_as_a_requester_only_searches_their_own_tickets(): void
    {
        $requester = $this->requester();

        $own = Ticket::factory()->create([
            'requester_id' => $requester->getKey(),
            'title' => 'Mon imprimante est en panne',
        ]);

        $foreign = Ticket::factory()->create(['title' => 'Le ticket de quelqu’un d’autre']);

        SupportServer::actingAs($requester)
            ->tool(SearchTickets::class)
            ->assertOk()
            ->assertSee($own->reference)
            ->assertDontSee($foreign->reference);
    }

    public function test_a_manager_searches_every_ticket(): void
    {
        $manager = $this->manager();
        $ticket = Ticket::factory()->create();

        SupportServer::actingAs($manager)
            ->tool(SearchTickets::class)
            ->assertOk()
            ->assertSee($ticket->reference);
    }

    public function test_the_search_filters_on_status(): void
    {
        $manager = $this->manager();

        $open = Ticket::factory()->create(['status' => TicketStatus::Open]);
        $resolved = Ticket::factory()->resolved($this->technician())->create();

        SupportServer::actingAs($manager)
            ->tool(SearchTickets::class, ['status' => TicketStatus::Open->value])
            ->assertOk()
            ->assertSee($open->reference)
            ->assertDontSee($resolved->reference);
    }

    public function test_the_search_refuses_a_status_that_is_not_one_of_ours(): void
    {
        SupportServer::actingAs($this->manager())
            ->tool(SearchTickets::class, ['status' => 'en-attente-de-cafe'])
            ->assertHasErrors();
    }

    public function test_reading_a_ticket_returns_its_history(): void
    {
        $manager = $this->manager();
        $ticket = Ticket::factory()->create();
        $ticket->statusChanges()->create([
            'author_id' => $ticket->requester_id,
            'from_status' => null,
            'to_status' => TicketStatus::Open,
        ]);

        SupportServer::actingAs($manager)
            ->tool(GetTicket::class, ['reference' => $ticket->reference])
            ->assertOk()
            ->assertSee($ticket->title)
            ->assertSee('history');
    }

    public function test_reading_a_ticket_you_may_not_see_comes_back_as_an_explicit_error(): void
    {
        $requester = $this->requester();
        $foreign = Ticket::factory()->create();

        SupportServer::actingAs($requester)
            ->tool(GetTicket::class, ['reference' => $foreign->reference])
            ->assertHasErrors()
            ->assertSee('not allowed to read it');
    }

    public function test_an_agent_opens_a_ticket_through_the_domain_action(): void
    {
        $requester = $this->requester();

        SupportServer::actingAs($requester)
            ->tool(OpenTicket::class, [
                'title' => 'Le VPN refuse mes identifiants',
                'description' => 'Depuis la mise à jour de ce matin, le client renvoie une erreur 812.',
                'priority' => TicketPriority::High->value,
            ])
            ->assertOk()
            ->assertHasNoErrors();

        $ticket = Ticket::sole();

        $this->assertSame($requester->getKey(), $ticket->requester_id);
        $this->assertSame(TicketStatus::Open, $ticket->status);
        $this->assertSame(TicketPriority::High, $ticket->priority);

        $this->assertMatchesRegularExpression('/^TCK-\d{5}$/', $ticket->reference);
        $this->assertSame(1, $ticket->statusChanges()->count());
    }

    public function test_a_technician_is_refused_when_opening_a_ticket(): void
    {
        SupportServer::actingAs($this->technician())
            ->tool(OpenTicket::class, [
                'title' => 'Un objet parfaitement valable',
                'description' => 'Une description bien assez longue pour passer la validation.',
                'priority' => TicketPriority::Low->value,
            ])
            ->assertHasErrors()
            ->assertSee('not allowed to open a ticket');

        $this->assertSame(0, Ticket::count());
    }

    public function test_opening_a_ticket_validates_its_input(): void
    {
        SupportServer::actingAs($this->requester())
            ->tool(OpenTicket::class, [
                'title' => 'bug',
                'description' => 'court',
                'priority' => 'urgentissime',
            ])
            ->assertHasErrors();

        $this->assertSame(0, Ticket::count());
    }

    public function test_the_resource_hands_the_agent_the_rules_the_domain_enforces(): void
    {
        $response = SupportServer::actingAs($this->requester())
            ->resource(TicketRules::class)
            ->assertOk();

        foreach (TicketPriority::cases() as $priority) {
            $response->assertSee($priority->value);
            $response->assertSee((string) $priority->targetResolutionHours());
        }

        foreach (TicketStatus::cases() as $status) {
            $response->assertSee($status->value);
        }
    }

    public function test_a_guest_reaches_nothing(): void
    {
        $this->postJson('/mcp/support', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
        ])->assertUnauthorized();
    }

    /**
     * The point of the extension: a tool reuses the domain, it does not restate it.
     */
    public function test_no_tool_rebuilds_what_the_domain_already_decides(): void
    {
        $sources = collect(glob(app_path('Mcp/Tools/*.php')))
            ->map(fn (string $file): string => (string) file_get_contents($file))
            ->implode("\n");

        $this->assertStringNotContainsString('str_pad', $sources, 'Un Tool fabrique lui-même une référence.');
        $this->assertStringNotContainsString('TicketStatus::Open,', $sources, 'Un Tool décide lui-même du statut d’ouverture.');
        $this->assertStringNotContainsString('statusChanges()->create', $sources, 'Un Tool écrit lui-même l’historique.');
        $this->assertStringNotContainsString('canTransitionTo', $sources, 'Un Tool rejoue la machine à états.');

        $this->assertStringContainsString('CreateTicket', $sources, 'L’ouverture ne passe pas par l’action du domaine.');
        $this->assertStringContainsString('visibleTo', $sources, 'La lecture ne passe pas par le périmètre du domaine.');
    }
}
