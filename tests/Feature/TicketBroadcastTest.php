<?php

namespace Tests\Feature;

use App\Broadcasting\TicketAudience;
use App\Enums\TicketStatus;
use App\Events\TicketAssigned;
use App\Events\TicketCreated;
use App\Events\TicketStatusChanged;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class TicketBroadcastTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();
        Notification::fake();
    }

    /**
     * @return array<int, string>
     */
    private function channelNames(ShouldBroadcast $event): array
    {
        return array_map(
            fn (PrivateChannel $channel): string => (string) $channel,
            $event->broadcastOn(),
        );
    }

    public function test_the_domain_events_are_the_ones_that_travel(): void
    {
        foreach ([TicketCreated::class, TicketAssigned::class, TicketStatusChanged::class] as $event) {
            $this->assertTrue(
                is_subclass_of($event, ShouldBroadcast::class),
                $event.' ne diffuse pas.',
            );
        }
    }

    public function test_the_requester_and_every_watcher_hear_about_their_ticket(): void
    {
        $manager = $this->manager();
        $requester = $this->requester();
        $ticket = Ticket::factory()->create(['requester_id' => $requester->getKey()]);

        $channels = $this->channelNames(new TicketCreated($ticket));

        $this->assertContains('private-users.'.$requester->getKey(), $channels);
        $this->assertContains('private-users.'.$manager->getKey(), $channels);
    }

    public function test_a_requester_never_hears_about_someone_elses_ticket(): void
    {
        $stranger = $this->requester();
        $ticket = Ticket::factory()->create();

        $channels = $this->channelNames(new TicketCreated($ticket));

        $this->assertNotContains('private-users.'.$stranger->getKey(), $channels);
    }

    public function test_a_technician_only_hears_about_the_tickets_assigned_to_them(): void
    {
        $mine = $this->technician();
        $other = $this->technician();

        $ticket = Ticket::factory()->assignedTo($mine)->create();

        $channels = $this->channelNames(new TicketStatusChanged(
            $ticket,
            TicketStatus::Assigned,
            TicketStatus::InProgress,
        ));

        $this->assertContains('private-users.'.$mine->getKey(), $channels);
        $this->assertNotContains('private-users.'.$other->getKey(), $channels);
    }

    public function test_every_channel_is_private(): void
    {
        $this->manager();
        $ticket = Ticket::factory()->create();

        foreach ((new TicketCreated($ticket))->broadcastOn() as $channel) {
            $this->assertInstanceOf(PrivateChannel::class, $channel);
            $this->assertStringStartsWith('private-', (string) $channel);
        }
    }

    public function test_the_payload_carries_no_ticket_content(): void
    {
        $this->manager();
        $ticket = Ticket::factory()->create(['title' => 'Un objet confidentiel']);

        $payload = (new TicketCreated($ticket))->broadcastWith();

        $this->assertSame(['ticket_id', 'reference'], array_keys($payload));
        $this->assertStringNotContainsString('confidentiel', json_encode($payload));
    }

    public function test_the_audience_matches_who_the_policy_lets_in(): void
    {
        $manager = $this->manager();
        $requester = $this->requester();
        $technician = $this->technician();
        $stranger = $this->requester();

        $ticket = Ticket::factory()
            ->assignedTo($technician)
            ->create(['requester_id' => $requester->getKey()]);

        $audience = app(TicketAudience::class)->recipients($ticket);

        foreach (User::all() as $user) {
            $this->assertSame(
                $user->can('view', $ticket),
                $audience->contains(fn (User $heard): bool => $heard->getKey() === $user->getKey()),
                "L'audience et la policy divergent pour l'utilisateur {$user->getKey()}.",
            );
        }

        unset($manager, $stranger);
    }
}
