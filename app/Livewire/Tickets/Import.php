<?php

namespace App\Livewire\Tickets;

use App\Jobs\ImportTicketsFromCsv;
use App\Models\Ticket;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
class Import extends Component
{
    use AuthorizesRequests, WithFileUploads;

    public ?TemporaryUploadedFile $file = null;

    public function mount(): void
    {
        $this->authorize('import', Ticket::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => __('import.errors.file'),
            'file.mimes' => __('import.errors.file'),
            'file.max' => __('import.errors.file'),
        ];
    }

    public function save(): void
    {
        $this->authorize('import', Ticket::class);
        $this->validate();

        $path = $this->file->store('imports', 'local');

        ImportTicketsFromCsv::dispatch($path, auth()->id());

        $this->reset('file');

        session()->flash('status', __('import.queued'));
        $this->redirect(route('tickets.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.tickets.import');
    }
}
