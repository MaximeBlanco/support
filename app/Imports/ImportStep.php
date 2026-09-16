<?php

namespace App\Imports;

/**
 * One stage of the import pipeline.
 *
 * A stage receives every row that is still standing and may reject some of them.
 * Rejecting a row is data being wrong; throwing is the machinery being broken,
 * and only the second one stops the import.
 */
interface ImportStep
{
    /**
     * @param  array<int, ImportRow>  $rows
     * @return array<int, ImportRow>
     */
    public function handle(array $rows): array;
}
