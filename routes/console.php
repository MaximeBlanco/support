<?php

use App\Console\Commands\EscalateOverdueTickets;
use Illuminate\Support\Facades\Schedule;

Schedule::command(EscalateOverdueTickets::class)
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer();
