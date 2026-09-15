<?php

namespace App\Providers;

use App\Events\TicketAssigned;
use App\Events\TicketResolved;
use App\Listeners\NotifyAssignedTechnician;
use App\Listeners\NotifyRequesterOfResolution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Model::preventLazyLoading($this->app->isLocal());

        Password::defaults(fn (): Password => Password::min(8)->letters()->numbers());

        Event::listen(TicketAssigned::class, NotifyAssignedTechnician::class);
        Event::listen(TicketResolved::class, NotifyRequesterOfResolution::class);
    }
}
