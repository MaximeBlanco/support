<?php

namespace App\Jobs;

use App\Imports\TicketImporter;
use App\Models\User;
use App\Notifications\TicketImportFailed;
use App\Notifications\TicketImportFinished;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ImportTicketsFromCsv implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(
        private readonly string $path,
        private readonly int $uploadedBy,
    ) {}

    public function handle(TicketImporter $importer): void
    {
        $report = $importer->import(Storage::disk('local')->path($this->path));

        $this->uploader()?->notify(new TicketImportFinished($report));

        Storage::disk('local')->delete($this->path);
    }

    /**
     * A broken file is not a rejected row: the operator is told the run stopped.
     */
    public function failed(Throwable $exception): void
    {
        $this->uploader()?->notify(new TicketImportFailed($exception->getMessage()));

        Storage::disk('local')->delete($this->path);
    }

    private function uploader(): ?User
    {
        return User::find($this->uploadedBy);
    }
}
