<?php

namespace Tests\Feature;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Livewire\Tickets\Index;
use App\Models\Comment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Concerns\CreatesUsers;
use Tests\TestCase;

class TicketListTest extends TestCase
{
    use CreatesUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();
    }

    public function test_it_filters_by_status(): void
    {
        $wanted = Ticket::factory()->create(['status' => TicketStatus::Open]);
        $other = Ticket::factory()->resolved($this->technician())->create();

        Livewire::actingAs($this->manager())
            ->test(Index::class)
            ->set('status', TicketStatus::Open->value)
            ->assertSee($wanted->reference)
            ->assertDontSee($other->reference);
    }

    public function test_it_filters_by_priority(): void
    {
        $critical = Ticket::factory()->priority(TicketPriority::Critical)->create();
        $low = Ticket::factory()->priority(TicketPriority::Low)->create();

        Livewire::actingAs($this->manager())
            ->test(Index::class)
            ->set('priority', TicketPriority::Critical->value)
            ->assertSee($critical->reference)
            ->assertDontSee($low->reference);
    }

    public function test_it_searches_on_the_reference_and_the_title(): void
    {
        $match = Ticket::factory()->create(['title' => 'Imprimante hors service']);
        $other = Ticket::factory()->create(['title' => 'Demande de licence']);

        Livewire::actingAs($this->manager())
            ->test(Index::class)
            ->set('search', 'Imprimante')
            ->assertSee($match->reference)
            ->assertDontSee($other->reference);
    }

    public function test_changing_a_filter_returns_to_the_first_page(): void
    {
        Ticket::factory()->count(30)->create();

        Livewire::actingAs($this->manager())
            ->test(Index::class)
            ->set('paginators.page', 2)
            ->set('status', TicketStatus::Open->value)
            ->assertSet('paginators.page', 1);
    }

    public function test_sorting_toggles_the_direction_on_the_same_column(): void
    {
        Livewire::actingAs($this->manager())
            ->test(Index::class)
            ->call('sort', 'title')
            ->assertSet('sortColumn', 'title')
            ->assertSet('sortDirection', 'asc')
            ->call('sort', 'title')
            ->assertSet('sortDirection', 'desc');
    }

    public function test_an_unknown_sort_column_is_ignored(): void
    {
        Livewire::actingAs($this->manager())
            ->test(Index::class)
            ->call('sort', 'password')
            ->assertSet('sortColumn', 'created_at');
    }

    public function test_the_query_count_does_not_grow_with_the_number_of_rows(): void
    {
        $manager = $this->manager();
        $technician = $this->technician();

        Ticket::factory()->count(3)->assignedTo($technician)->has(Comment::factory()->count(2))->create();

        $few = $this->countQueriesForList($manager);

        Ticket::factory()->count(20)->assignedTo($technician)->has(Comment::factory()->count(2))->create();

        $many = $this->countQueriesForList($manager);

        $this->assertSame($few, $many, "La liste passe de {$few} à {$many} requêtes : il y a un N+1.");
        $this->assertLessThanOrEqual(5, $many, 'La liste exécute trop de requêtes pour une seule page.');
    }

    public function test_a_requester_only_lists_their_own_tickets(): void
    {
        $requester = $this->requester();
        $own = Ticket::factory()->create(['requester_id' => $requester->getKey()]);
        $foreign = Ticket::factory()->create();

        Livewire::actingAs($requester)
            ->test(Index::class)
            ->assertSee($own->reference)
            ->assertDontSee($foreign->reference);
    }

    public function test_resetting_the_filters_clears_them_all(): void
    {
        Livewire::actingAs($this->manager())
            ->test(Index::class)
            ->set('search', 'vpn')
            ->set('status', TicketStatus::Open->value)
            ->set('priority', TicketPriority::High->value)
            ->call('resetFilters')
            ->assertSet('search', '')
            ->assertSet('status', '')
            ->assertSet('priority', '');
    }

    private function countQueriesForList(User $user): int
    {
        Livewire::actingAs($user)->test(Index::class);

        DB::flushQueryLog();
        DB::enableQueryLog();

        Livewire::actingAs($user)->test(Index::class);

        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }
}
