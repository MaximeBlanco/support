<?php

namespace Tests\Unit;

use App\Enums\TicketPriority;
use PHPUnit\Framework\TestCase;

class TicketPriorityTest extends TestCase
{
    public function test_each_priority_carries_its_target_resolution_time(): void
    {
        $this->assertSame(72, TicketPriority::Low->targetResolutionHours());
        $this->assertSame(24, TicketPriority::Normal->targetResolutionHours());
        $this->assertSame(8, TicketPriority::High->targetResolutionHours());
        $this->assertSame(2, TicketPriority::Critical->targetResolutionHours());
    }

    public function test_the_target_shrinks_as_the_priority_rises(): void
    {
        $previous = PHP_INT_MAX;

        foreach (TicketPriority::cases() as $priority) {
            $this->assertLessThan($previous, $priority->targetResolutionHours());
            $previous = $priority->targetResolutionHours();
        }
    }

    public function test_escalation_stops_at_critical(): void
    {
        $this->assertSame(TicketPriority::Normal, TicketPriority::Low->next());
        $this->assertSame(TicketPriority::High, TicketPriority::Normal->next());
        $this->assertSame(TicketPriority::Critical, TicketPriority::High->next());
        $this->assertNull(TicketPriority::Critical->next());
    }

    public function test_weights_are_strictly_increasing(): void
    {
        $weights = array_map(
            fn (TicketPriority $priority): int => $priority->weight(),
            TicketPriority::cases(),
        );

        $this->assertSame([1, 2, 3, 4], $weights);
    }
}
