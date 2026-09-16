<?php

namespace App\Imports\Steps;

use App\Enums\TicketPriority;
use App\Imports\ImportRow;
use App\Imports\ImportStep;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ValidateRow implements ImportStep
{
    /**
     * @param  array<int, ImportRow>  $rows
     * @return array<int, ImportRow>
     */
    public function handle(array $rows): array
    {
        foreach ($rows as $row) {
            if ($row->isRejected()) {
                continue;
            }

            $validator = Validator::make($row->values, [
                'title' => ['required', 'string', 'min:5', 'max:150'],
                'description' => ['required', 'string', 'min:10', 'max:5000'],
                'priority' => ['required', Rule::enum(TicketPriority::class)],
                'email' => ['required', 'email'],
            ]);

            if ($validator->fails()) {
                $row->reject($validator->errors()->first());
            }
        }

        return $rows;
    }
}
