<?php

namespace App\Imports\Steps;

use App\Imports\ImportRow;
use App\Imports\ImportStep;
use App\Models\User;

/**
 * Turns every e-mail in the file into a user id.
 *
 * A thousand rows resolve in one query, not a thousand: the whole column is
 * collected first and looked up in a single `whereIn`.
 */
class ResolveRequester implements ImportStep
{
    /**
     * @param  array<int, ImportRow>  $rows
     * @return array<int, ImportRow>
     */
    public function handle(array $rows): array
    {
        $standing = array_filter($rows, fn (ImportRow $row): bool => ! $row->isRejected());

        if ($standing === []) {
            return $rows;
        }

        $emails = array_unique(array_filter(array_map(
            fn (ImportRow $row): ?string => $row->get('email'),
            $standing,
        )));

        $byEmail = User::query()
            ->whereIn('email', $emails)
            ->pluck('id', 'email');

        foreach ($standing as $row) {
            $id = $byEmail[$row->get('email')] ?? null;

            if ($id === null) {
                $row->reject(__('import.errors.unknown_requester', ['email' => $row->get('email')]));

                continue;
            }

            $row->set('requester_id', $id);
        }

        return $rows;
    }
}
