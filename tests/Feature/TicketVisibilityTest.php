<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class TicketVisibilityTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    private User $requester;

    private User $technician;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();

        $this->requester = $this->requester();
        $this->technician = $this->technician();
        $this->manager = $this->manager();

        Ticket::factory()->count(3)->create(['requester_id' => $this->requester->getKey()]);
        Ticket::factory()->count(2)->assignedTo($this->technician)->create();
        Ticket::factory()->count(4)->create();
    }

    public function test_a_requester_only_sees_their_own_tickets(): void
    {
        $visible = Ticket::query()->visibleTo($this->requester)->get();

        $this->assertCount(3, $visible);
        $this->assertTrue($visible->every(
            fn (Ticket $ticket): bool => $ticket->requester_id === $this->requester->getKey()
        ));
    }

    public function test_a_technician_only_sees_the_tickets_assigned_to_them(): void
    {
        $visible = Ticket::query()->visibleTo($this->technician)->get();

        $this->assertCount(2, $visible);
        $this->assertTrue($visible->every(
            fn (Ticket $ticket): bool => $ticket->assignee_id === $this->technician->getKey()
        ));
    }

    public function test_a_manager_sees_every_ticket(): void
    {
        $this->assertSame(
            Ticket::count(),
            Ticket::query()->visibleTo($this->manager)->count(),
        );
    }

    public function test_a_user_without_any_viewing_permission_sees_nothing(): void
    {
        $stranger = User::factory()->create();

        $this->assertSame(0, Ticket::query()->visibleTo($stranger)->count());
    }

    public function test_the_restriction_is_applied_in_the_sql_not_after_the_query(): void
    {
        $sql = Ticket::query()->visibleTo($this->requester)->toSql();

        $this->assertStringContainsString('requester_id', $sql);
        $this->assertStringNotContainsString('requester_id', Ticket::query()->visibleTo($this->manager)->toSql());
    }

    public function test_a_requester_cannot_open_someone_elses_ticket(): void
    {
        $foreign = Ticket::factory()->create();

        $this->actingAs($this->requester)
            ->get(route('tickets.show', $foreign))
            ->assertForbidden();
    }

    public function test_a_requester_can_open_their_own_ticket(): void
    {
        $own = Ticket::factory()->create(['requester_id' => $this->requester->getKey()]);

        $this->actingAs($this->requester)
            ->get(route('tickets.show', $own))
            ->assertOk()
            ->assertSee($own->title);
    }

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('tickets.index'))->assertRedirect(route('login'));
    }
}
