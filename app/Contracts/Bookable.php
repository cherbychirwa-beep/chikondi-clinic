<?php

namespace App\Contracts;

use Carbon\CarbonInterface;

/** Anything that can receive appointments (implemented by the doctor domain classes). */
interface Bookable
{
    public function isAvailableOn(CarbonInterface $date): bool;

    public function availabilitySummary(): string;
}
