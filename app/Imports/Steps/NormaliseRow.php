<?php

namespace App\Imports\Steps;

use App\Imports\ImportRow;
use App\Imports\ImportStep;
use Illuminate\Support\Str;

class NormaliseRow implements ImportStep
{
    /**
     * @param  array<int, ImportRow>  $rows
     * @return array<int, ImportRow>
     */
    public function handle(array $rows): array
    {
        foreach ($rows as $row) {
            foreach ($row->values as $key => $value) {
                $row->set($key, $this->clean($value));
            }

            $row->set('email', Str::lower((string) $row->get('email')));
            $row->set('priority', Str::lower((string) $row->get('priority')));
        }

        return $rows;
    }

    private function clean(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim(preg_replace('/\s+/u', ' ', (string) $value) ?? '');

        return $value === '' ? null : $value;
    }
}
