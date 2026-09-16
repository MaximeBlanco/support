<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/**
 * Every ticket event is delivered on the recipient's own channel, so the only
 * question left here is whether the socket really belongs to that person.
 */
Broadcast::channel('users.{id}', fn (User $user, string $id): bool => (int) $user->getKey() === (int) $id);
