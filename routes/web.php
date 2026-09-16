<?php

use App\Http\Controllers\LogoutController;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Dashboard;
use App\Livewire\Tickets\Form as TicketForm;
use App\Livewire\Tickets\Import as TicketImport;
use App\Livewire\Tickets\Index as TicketIndex;
use App\Livewire\Tickets\Show as TicketShow;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/', Dashboard::class)->name('dashboard');

    Route::get('/tickets', TicketIndex::class)->name('tickets.index');
    Route::get('/tickets/nouveau', TicketForm::class)->name('tickets.create');
    Route::get('/tickets/import', TicketImport::class)->name('tickets.import');
    Route::get('/tickets/{ticket}', TicketShow::class)->name('tickets.show');
    Route::get('/tickets/{ticket}/modifier', TicketForm::class)->name('tickets.edit');

    Route::post('/logout', LogoutController::class)->name('logout');
});
