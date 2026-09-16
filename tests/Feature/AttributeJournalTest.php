<?php

namespace Tests\Feature;

use App\Actions\Tickets\AssignTicket;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\AttributeChange;
use App\Models\Comment;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class AttributeJournalTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();
        Notification::fake();
    }

    public function test_changing_a_recorded_attribute_writes_the_old_and_the_new_value(): void
    {
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

        $ticket->update(['status' => TicketStatus::Assigned]);

        $change = $ticket->attributeChanges()->where('attribute', 'status')->sole();

        $this->assertSame(TicketStatus::Open->value, $change->old_value);
        $this->assertSame(TicketStatus::Assigned->value, $change->new_value);
    }

    public function test_changing_an_unrecorded_attribute_writes_nothing(): void
    {
        $ticket = Ticket::factory()->create();

        $ticket->update(['description' => 'Une description entièrement réécrite.']);

        $this->assertSame(0, $ticket->attributeChanges()->count());
    }

    public function test_saving_without_a_change_writes_nothing(): void
    {
        $ticket = Ticket::factory()->create(['title' => 'Un objet stable']);

        $ticket->update(['title' => 'Un objet stable']);

        $this->assertSame(0, $ticket->attributeChanges()->count());
    }

    public function test_the_author_is_the_logged_in_user(): void
    {
        $manager = $this->manager();
        $ticket = Ticket::factory()->create();

        $this->actingAs($manager);
        $ticket->update(['priority' => TicketPriority::Critical]);

        $this->assertSame($manager->getKey(), $ticket->attributeChanges()->sole()->author_id);
    }

    public function test_a_change_made_without_a_logged_in_user_is_still_recorded(): void
    {
        $ticket = Ticket::factory()->create();

        $ticket->update(['priority' => TicketPriority::High]);

        $change = $ticket->attributeChanges()->sole();

        $this->assertNull($change->author_id);
        $this->assertSame(TicketPriority::High->value, $change->new_value);
    }

    public function test_the_same_trait_works_on_comments_with_no_extra_code(): void
    {
        $comment = Comment::factory()->create(['body' => 'Première version']);

        $comment->update(['body' => 'Version corrigée']);

        $change = $comment->attributeChanges()->sole();

        $this->assertSame('Première version', $change->old_value);
        $this->assertSame('Version corrigée', $change->new_value);
        $this->assertSame(Comment::class, $change->recordable_type);
    }

    public function test_several_attributes_changed_at_once_produce_one_line_each(): void
    {
        $ticket = Ticket::factory()->create([
            'status' => TicketStatus::Open,
            'priority' => TicketPriority::Low,
        ]);

        $ticket->update([
            'status' => TicketStatus::Assigned,
            'priority' => TicketPriority::High,
            'description' => 'Ignorée, pas suivie.',
        ]);

        $this->assertSame(
            ['priority', 'status'],
            $ticket->attributeChanges()->pluck('attribute')->sort()->values()->all(),
        );
    }

    public function test_the_domain_actions_feed_the_journal(): void
    {
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

        app(AssignTicket::class)->handle($ticket, $this->technician(), $this->manager());

        $attributes = $ticket->attributeChanges()->pluck('attribute')->sort()->values()->all();

        $this->assertContains('status', $attributes);
        $this->assertContains('assignee_id', $attributes);
    }

    public function test_a_journal_line_cannot_be_edited(): void
    {
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);
        $ticket->update(['status' => TicketStatus::Assigned]);

        $change = $ticket->attributeChanges()->sole();
        $change->new_value = 'falsifié';

        $this->assertFalse($change->save());
        $this->assertSame(TicketStatus::Assigned->value, $change->fresh()->new_value);
    }

    public function test_the_journal_is_pruned_past_the_retention_window(): void
    {
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);
        $ticket->update(['status' => TicketStatus::Assigned]);

        AttributeChange::query()->update(['created_at' => now()->subYears(3)]);

        $this->artisan('model:prune', ['--model' => [AttributeChange::class]])->assertSuccessful();

        $this->assertSame(0, AttributeChange::count());
    }

    public function test_a_recent_journal_line_survives_the_prune(): void
    {
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);
        $ticket->update(['status' => TicketStatus::Assigned]);

        $this->artisan('model:prune', ['--model' => [AttributeChange::class]])->assertSuccessful();

        $this->assertSame(1, AttributeChange::count());
    }

    public function test_the_project_contains_no_observer(): void
    {
        $this->assertDirectoryDoesNotExist(app_path('Observers'));

        $observed = collect(glob(app_path('Models/*.php')))
            ->filter(fn (string $file): bool => str_contains((string) file_get_contents($file), 'ObservedBy'));

        $this->assertTrue($observed->isEmpty(), 'Un modèle est encore surveillé par un Observer.');
    }
}
