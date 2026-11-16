<?php

namespace App\Services;

use App\Models\FeeSetting;
use App\Models\User;
use Carbon\Carbon;

class FeeService
{
    public function __construct(protected AuditService $audit)
    {
    }

    /** The fee in force today (latest effective_from that is not in the future). */
    public function current(): ?FeeSetting
    {
        return FeeSetting::whereDate('effective_from', '<=', today()->toDateString())
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();
    }

    public function currentAmount(): string
    {
        return number_format((float) ($this->current()?->amount ?? 0), 2, '.', '');
    }

    /** New fees are appended (never overwritten) so history stays intact. */
    public function set(float $amount, string $effectiveFrom, ?string $note, User $by): FeeSetting
    {
        $fee = FeeSetting::create([
            'amount' => $amount,
            'effective_from' => Carbon::parse($effectiveFrom)->toDateString(),
            'note' => $note,
            'created_by' => $by->id,
        ]);

        $this->audit->log('fee.changed', 'Consultation fee set to '.money($amount).' effective '.$effectiveFrom, $fee, $by);

        return $fee;
    }
}
