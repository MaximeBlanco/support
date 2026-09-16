<?php

namespace App\Imports;

use App\Exceptions\UnreadableImportFile;
use App\Imports\Steps\CreateTickets;
use App\Imports\Steps\NormaliseRow;
use App\Imports\Steps\ResolveRequester;
use App\Imports\Steps\ValidateRow;

/**
 * Reads a CSV of tickets and walks it through the import stages.
 *
 * Every stage sees the whole batch rather than one row at a time, which is what
 * lets the reference resolution answer a thousand rows in a single query.
 */
class TicketImporter
{
    private const COLUMNS = ['email', 'title', 'description', 'priority'];

    /**
     * @var array<int, class-string<ImportStep>>
     */
    private const STEPS = [
        NormaliseRow::class,
        ValidateRow::class,
        ResolveRequester::class,
        CreateTickets::class,
    ];

    public function import(string $path): ImportReport
    {
        $rows = $this->read($path);

        if ($rows === []) {
            return new ImportReport;
        }

        foreach (self::STEPS as $step) {
            $rows = app($step)->handle($rows);
        }

        return ImportReport::from($rows);
    }

    /**
     * @return array<int, ImportRow>
     */
    private function read(string $path): array
    {
        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            throw UnreadableImportFile::at($path);
        }

        try {
            $header = fgetcsv($handle, escape: '');

            if ($header === false) {
                return [];
            }

            $header = array_map(
                fn (mixed $column): string => strtolower(trim((string) $column)),
                $header,
            );

            $this->assertColumns($header, $path);

            $rows = [];
            $number = 1;

            while (($line = fgetcsv($handle, escape: '')) !== false) {
                $number++;

                if ($line === [null] || $line === []) {
                    continue;
                }

                $rows[] = new ImportRow($number, $this->combine($header, $line));
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  array<int, string>  $header
     */
    private function assertColumns(array $header, string $path): void
    {
        $missing = array_diff(self::COLUMNS, $header);

        if ($missing !== []) {
            throw UnreadableImportFile::missingColumns($path, array_values($missing));
        }
    }

    /**
     * @param  array<int, string>  $header
     * @param  array<int, string|null>  $line
     * @return array<string, string|null>
     */
    private function combine(array $header, array $line): array
    {
        $line = array_pad(array_slice($line, 0, count($header)), count($header), null);

        return array_combine($header, $line);
    }
}
