<?php

namespace Tests\Unit;

use App\Enums\TicketStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TicketStatusTest extends TestCase
{
    /**
     * @return array<string, array{TicketStatus, TicketStatus, bool}>
     */
    public static function transitions(): array
    {
        return [
            'open to assigned' => [TicketStatus::Open, TicketStatus::Assigned, true],
            'open to in progress' => [TicketStatus::Open, TicketStatus::InProgress, false],
            'open to resolved' => [TicketStatus::Open, TicketStatus::Resolved, false],
            'open to closed' => [TicketStatus::Open, TicketStatus::Closed, false],

            'assigned to in progress' => [TicketStatus::Assigned, TicketStatus::InProgress, true],
            'assigned back to open' => [TicketStatus::Assigned, TicketStatus::Open, true],
            'assigned to resolved' => [TicketStatus::Assigned, TicketStatus::Resolved, false],
            'assigned to closed' => [TicketStatus::Assigned, TicketStatus::Closed, false],

            'in progress to resolved' => [TicketStatus::InProgress, TicketStatus::Resolved, true],
            'in progress back to assigned' => [TicketStatus::InProgress, TicketStatus::Assigned, true],
            'in progress to open' => [TicketStatus::InProgress, TicketStatus::Open, false],
            'in progress to closed' => [TicketStatus::InProgress, TicketStatus::Closed, false],

            'resolved to closed' => [TicketStatus::Resolved, TicketStatus::Closed, true],
            'resolved back to in progress' => [TicketStatus::Resolved, TicketStatus::InProgress, true],
            'resolved to open' => [TicketStatus::Resolved, TicketStatus::Open, false],
            'resolved to assigned' => [TicketStatus::Resolved, TicketStatus::Assigned, false],

            'closed to open' => [TicketStatus::Closed, TicketStatus::Open, false],
            'closed to assigned' => [TicketStatus::Closed, TicketStatus::Assigned, false],
            'closed to in progress' => [TicketStatus::Closed, TicketStatus::InProgress, false],
            'closed to resolved' => [TicketStatus::Closed, TicketStatus::Resolved, false],
        ];
    }

    #[DataProvider('transitions')]
    public function test_it_knows_which_transitions_are_legal(TicketStatus $from, TicketStatus $to, bool $expected): void
    {
        $this->assertSame($expected, $from->canTransitionTo($to));
    }

    public function test_only_closed_is_terminal(): void
    {
        foreach (TicketStatus::cases() as $status) {
            $this->assertSame($status === TicketStatus::Closed, $status->isTerminal());
        }
    }

    public function test_a_status_never_transitions_to_itself(): void
    {
        foreach (TicketStatus::cases() as $status) {
            $this->assertFalse($status->canTransitionTo($status));
        }
    }

    public function test_settled_statuses_are_resolved_and_closed(): void
    {
        $this->assertTrue(TicketStatus::Resolved->isSettled());
        $this->assertTrue(TicketStatus::Closed->isSettled());
        $this->assertFalse(TicketStatus::Open->isSettled());
        $this->assertFalse(TicketStatus::Assigned->isSettled());
        $this->assertFalse(TicketStatus::InProgress->isSettled());
    }

    public function test_open_states_exclude_settled_ones(): void
    {
        $this->assertSame(
            [TicketStatus::Open, TicketStatus::Assigned, TicketStatus::InProgress],
            TicketStatus::openStates(),
        );
    }
}
