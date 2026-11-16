<?php

namespace App\Domain\Doctors;

use Carbon\CarbonInterface;

class FullTimeDoctor extends Doctor
{
    public function employmentType(): string
    {
        return 'full_time';
    }

    public function employmentLabel(): string
    {
        return 'Full-time';
    }

    /** Full-time doctors are available every day they have an active schedule. */
    public function isAvailableOn(CarbonInterface $date): bool
    {
        return $this->record->is_active
            && $this->record->schedules()
                ->where('is_active', true)
                ->where('day_of_week', $date->dayOfWeek)
                ->exists();
    }

    public function availabilitySummary(): string
    {
        return 'Available on scheduled weekdays';
    }
}
