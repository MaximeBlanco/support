<?php

namespace App\Exceptions;

use App\Enums\TicketPriority;
use RuntimeException;

class NoNotificationPolicy extends RuntimeException
{
    public static function for(TicketPriority $priority): self
    {
        return new self(sprintf(
            'No notification policy answers for the [%s] priority.',
            $priority->value,
        ));
    }
}
