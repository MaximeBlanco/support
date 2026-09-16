<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * The file itself is unusable — not one of its lines.
 *
 * This is the machinery being broken rather than the data being wrong, so it
 * stops the import instead of being collected as a rejection.
 */
class UnreadableImportFile extends RuntimeException
{
    public static function at(string $path): self
    {
        return new self(sprintf('The import file [%s] could not be opened.', $path));
    }

    /**
     * @param  array<int, string>  $columns
     */
    public static function missingColumns(string $path, array $columns): self
    {
        return new self(sprintf(
            'The import file [%s] is missing the column(s): %s.',
            $path,
            implode(', ', $columns),
        ));
    }
}
