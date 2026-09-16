<?php

namespace Tests\Feature;

use App\Actions\Tickets\AssignTicket;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Notifications\Channels\UrgencyChannel;
use App\Notifications\TicketAssignedNotification;
use App\Notifications\TicketNotificationPolicies;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class NotificationPolicyTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();
        Notification::fake();
    }

    /**
     * @return array<string, array{TicketPriority, array<int, string>}>
     */
    public static function channelsPerPriority(): array
    {
        return [
            'low stays on mail' => [TicketPriority::Low, ['mail', 'database']],
            'normal stays on mail' => [TicketPriority::Normal, ['mail', 'database']],
            'high adds the urgency channel' => [TicketPriority::High, ['mail', 'database', UrgencyChannel::class]],
            'critical adds the urgency channel' => [TicketPriority::Critical, ['mail', 'database', UrgencyChannel::class]],
        ];
    }

    /**
     * @param  array<int, string>  $expected
     */
    #[DataProvider('channelsPerPriority')]
    public function test_each_priority_resolves_its_own_channels(TicketPriority $priority, array $expected): void
    {
        $channels = app(TicketNotificationPolicies::class)->for($priority)->channels();

        $this->assertSame($expected, $channels);
    }

    public function test_every_priority_finds_a_policy(): void
    {
        foreach (TicketPriority::cases() as $priority) {
            $this->assertNotEmpty(app(TicketNotificationPolicies::class)->for($priority)->channels());
        }
    }

    public function test_a_critical_ticket_also_warns_whoever_can_reassign_it(): void
    {
        $manager = $this->manager();
        $technician = $this->technician();

        $ticket = Ticket::factory()
            ->priority(TicketPriority::Critical)
            ->create(['status' => TicketStatus::Open]);

        app(AssignTicket::class)->handle($ticket, $technician, $manager);

        Notification::assertSentTo($technician, TicketAssignedNotification::class);
        Notification::assertSentTo($manager, TicketAssignedNotification::class);
    }

    public function test_a_normal_ticket_only_warns_the_technician(): void
    {
        $manager = $this->manager();
        $technician = $this->technician();

        $ticket = Ticket::factory()
            ->priority(TicketPriority::Normal)
            ->create(['status' => TicketStatus::Open]);

        app(AssignTicket::class)->handle($ticket, $technician, $manager);

        Notification::assertSentTo($technician, TicketAssignedNotification::class);
        Notification::assertNotSentTo($manager, TicketAssignedNotification::class);
    }

    public function test_the_registry_discovers_every_policy_in_the_directory(): void
    {
        $discovered = app(TicketNotificationPolicies::class)->all();

        $this->assertCount(
            count(glob(app_path('Notifications/Policies/*.php'))),
            $discovered,
        );
    }
}
