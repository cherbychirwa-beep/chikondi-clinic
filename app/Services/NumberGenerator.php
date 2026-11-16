<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Generates gap-free, never-duplicated reference numbers (patients, appointments, receipts, doctors).
 * A row in number_sequences is locked (SELECT ... FOR UPDATE) inside a transaction, so concurrent
 * requests are serialised and can never receive the same number.
 */
class NumberGenerator
{
    public function next(string $sequence, string $prefix, int $pad = 6): string
    {
        return DB::transaction(function () use ($sequence, $prefix, $pad) {
            $row = DB::table('number_sequences')->where('name', $sequence)->lockForUpdate()->first();

            if (! $row) {
                DB::table('number_sequences')->insertOrIgnore(['name' => $sequence, 'last_value' => 0]);
                $row = DB::table('number_sequences')->where('name', $sequence)->lockForUpdate()->first();
            }

            $next = ((int) $row->last_value) + 1;
            DB::table('number_sequences')->where('name', $sequence)->update(['last_value' => $next]);

            return $prefix.str_pad((string) $next, $pad, '0', STR_PAD_LEFT);
        });
    }
}
