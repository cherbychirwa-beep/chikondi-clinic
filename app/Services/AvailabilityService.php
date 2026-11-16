<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Doctor;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/** Calculates doctor availability and bookable slots. */
class AvailabilityService
{
    /**
     * All slots for a doctor on a date, flagged with availability.
     *
     * @return Collection<int,array{time:string,available:bool,booked:bool,past:bool}>
     */
    public function slotsFor(Doctor $doctor, CarbonInterface $date): Collection
    {
        $day = Carbon::parse($date->toDateString());

        if (! $doctor->is_active || ! $doctor->domain()->isAvailableOn($day)) {
            return collect();
        }

        $schedules = $doctor->schedules()
            ->where('is_active', true)
            ->where('day_of_week', $day->dayOfWeek)
            ->orderBy('start_time')
            ->get();

        $booked = Appointment::where('doctor_id', $doctor->id)
            ->whereDate('appointment_date', $day->toDateString())
            ->where('status', '!=', Appointment::CANCELLED)
            ->pluck('appointment_time')
            ->map(fn ($t) => substr($t, 0, 5))
            ->all();

        $slots = collect();
        foreach ($schedules as $schedule) {
            $cursor = Carbon::parse($day->toDateString().' '.$schedule->start_time);
            $end = Carbon::parse($day->toDateString().' '.$schedule->end_time);
            $step = max(5, (int) $schedule->slot_minutes);

            while ($cursor->copy()->addMinutes($step)->lte($end)) {
                $time = $cursor->format('H:i');
                $isBooked = in_array($time, $booked, true);
                $isPast = $cursor->isPast();
                $slots->push([
                    'time' => $time,
                    'available' => ! $isBooked && ! $isPast,
                    'booked' => $isBooked,
                    'past' => $isPast,
                ]);
                $cursor->addMinutes($step);
            }
        }

        return $slots->unique('time')->sortBy('time')->values();
    }

    /** @return Collection<int,array{time:string,available:bool,booked:bool,past:bool}> */
    public function availableSlots(Doctor $doctor, CarbonInterface $date): Collection
    {
        return $this->slotsFor($doctor, $date)->where('available', true)->values();
    }

    /**
     * Other active doctors who still have at least one open slot on the date.
     *
     * @return Collection<int,array{doctor:Doctor,slots:Collection}>
     */
    public function alternativeDoctors(CarbonInterface $date, ?int $excludeDoctorId = null): Collection
    {
        return Doctor::active()
            ->with('user')
            ->when($excludeDoctorId, fn ($q) => $q->where('id', '!=', $excludeDoctorId))
            ->get()
            ->map(fn (Doctor $d) => ['doctor' => $d, 'slots' => $this->availableSlots($d, $date)])
            ->filter(fn ($row) => $row['slots']->isNotEmpty())
            ->values();
    }
}
