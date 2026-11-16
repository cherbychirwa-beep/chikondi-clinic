<?php

namespace App\Domain\Doctors;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class VisitingDoctor extends Doctor
{
    public function employmentType(): string
    {
        return 'visiting';
    }

    public function employmentLabel(): string
    {
        return 'Visiting';
    }

    public function visitStart(): ?Carbon
    {
        return $this->record->next_visit_date ? Carbon::parse($this->record->next_visit_date)->startOfDay() : null;
    }

    /** Last day of the configured visit window (duration is in days, minimum 1). */
    public function visitEnd(): ?Carbon
    {
        $start = $this->visitStart();

        return $start ? $start->copy()->addDays(max(1, (int) $this->record->next_visit_duration_days) - 1)->endOfDay() : null;
    }

    /** Visiting doctors only accept bookings inside the visit window AND on a scheduled weekday. */
    public function isAvailableOn(CarbonInterface $date): bool
    {
        if (! $this->record->is_active || ! $this->visitStart()) {
            return false;
        }

        $day = Carbon::parse($date->toDateString());
        if ($day->lt($this->visitStart()) || $day->gt($this->visitEnd())) {
            return false;
        }

        return $this->record->schedules()
            ->where('is_active', true)
            ->where('day_of_week', $day->dayOfWeek)
            ->exists();
    }

    public function availabilitySummary(): string
    {
        if (! $this->visitStart()) {
            return 'No visit scheduled';
        }

        return 'Visiting '.$this->visitStart()->format('d M Y').' to '.$this->visitEnd()->format('d M Y');
    }
}
