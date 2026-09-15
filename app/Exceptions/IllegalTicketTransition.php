<?php

namespace App\Exceptions;

use App\Enums\TicketStatus;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class IllegalTicketTransition extends RuntimeException implements HttpExceptionInterface
{
    private function __construct(
        public readonly TicketStatus $from,
        public readonly TicketStatus $to,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function between(TicketStatus $from, TicketStatus $to): self
    {
        return new self($from, $to, sprintf(
            'A ticket cannot move from [%s] to [%s].',
            $from->value,
            $to->value,
        ));
    }

    public function translatedMessage(): string
    {
        return __('ticket.errors.illegal_transition', [
            'from' => $this->from->label(),
            'to' => $this->to->label(),
        ]);
    }

    public function getStatusCode(): int
    {
        return 409;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return [];
    }
}
