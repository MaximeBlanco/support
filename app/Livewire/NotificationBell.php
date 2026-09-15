<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Livewire\Component;

class NotificationBell extends Component
{
    public function markAllRead(): void
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);
    }

    /**
     * @return Collection<int, DatabaseNotification>
     */
    private function latest(): Collection
    {
        return auth()->user()
            ->notifications()
            ->latest()
            ->take(8)
            ->get();
    }

    public function render(): View
    {
        return view('livewire.notification-bell', [
            'notifications' => $this->latest(),
            'unreadCount' => auth()->user()->unreadNotifications()->count(),
        ]);
    }
}
