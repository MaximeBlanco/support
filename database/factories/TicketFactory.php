<?php

namespace Database\Factories;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $createdAt = fake()->dateTimeBetween('-60 days', '-1 hour');

        return [
            'reference' => 'TCK-'.Str::upper(Str::random(6)),
            'requester_id' => User::factory(),
            'assignee_id' => null,
            'title' => Str::ucfirst(fake()->words(rand(4, 8), true)),
            'description' => fake()->paragraphs(rand(1, 3), true),
            'status' => TicketStatus::Open,
            'priority' => fake()->randomElement(TicketPriority::cases()),
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }

    public function priority(TicketPriority $priority): self
    {
        return $this->state(fn (): array => ['priority' => $priority]);
    }

    public function assignedTo(User $technician): self
    {
        return $this->state(fn (array $attributes): array => [
            'assignee_id' => $technician->getKey(),
            'status' => TicketStatus::Assigned,
            'assigned_at' => fake()->dateTimeBetween($attributes['created_at'], 'now'),
        ]);
    }

    public function inProgress(User $technician): self
    {
        return $this->assignedTo($technician)
            ->state(fn (): array => ['status' => TicketStatus::InProgress]);
    }

    public function resolved(User $technician): self
    {
        return $this->inProgress($technician)->state(function (array $attributes): array {
            $resolvedAt = fake()->dateTimeBetween($attributes['created_at'], 'now');

            return [
                'status' => TicketStatus::Resolved,
                'resolved_at' => $resolvedAt,
                'resolved_within_target' => fake()->boolean(70),
            ];
        });
    }

    public function closed(User $technician): self
    {
        return $this->resolved($technician)->state(fn (array $attributes): array => [
            'status' => TicketStatus::Closed,
            'closed_at' => fake()->dateTimeBetween($attributes['resolved_at'], 'now'),
        ]);
    }
}
