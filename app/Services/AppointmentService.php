<?php

namespace App\Services;

use App\Exceptions\SlotUnavailableException;
use App\Exceptions\ValidationException;
use App\Models\Appointment;
use App\Models\AppointmentCancellation;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;

class AppointmentService
{
    public function __construct(
        protected DatabaseConnection $db,
        protected AvailabilityService $availability,
        protected NumberGenerator $numbers,
        protected AuditService $audit,
    ) {
    }

    /**
     * Book an appointment.
     *
     * Race-condition safety: the doctor row is locked inside a transaction AND the unique
     * appointments.slot_key index rejects a second live booking for the same doctor/date/time.
     *
     * @throws ValidationException|SlotUnavailableException
     */
    public function book(Patient $patient, Doctor $doctor, string $date, string $time, User $by): Appointment
    {
        try {
            $day = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
        } catch (\Throwable) {
            throw new ValidationException('Invalid appointment date.', ['appointment_date' => 'Invalid appointment date.']);
        }

        $time = substr($time, 0, 5);
        if (! preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time)) {
            throw new ValidationException('Invalid appointment time.', ['appointment_time' => 'Invalid appointment time.']);
        }

        if (Carbon::parse($date.' '.$time)->isPast()) {
            throw new ValidationException('Appointments cannot be booked in the past.', ['appointment_date' => 'Appointments cannot be booked in the past.']);
        }

        try {
            return $this->db->transaction(function () use ($patient, $doctor, $day, $date, $time, $by) {
                $locked = Doctor::whereKey($doctor->id)->lockForUpdate()->firstOrFail();

                if (! $locked->is_active) {
                    throw new SlotUnavailableException('This doctor is no longer active.');
                }

                if (! $locked->domain()->isAvailableOn($day)) {
                    throw new SlotUnavailableException($locked->name.' is not available on '.$day->format('d M Y').'.');
                }

                $slot = $this->availability->slotsFor($locked, $day)->firstWhere('time', $time);
                if (! $slot) {
                    throw new SlotUnavailableException('The selected time is not a valid slot for this doctor.');
                }
                if ($slot['booked']) {
                    throw new SlotUnavailableException('That slot has already been booked.');
                }

                $patientClash = Appointment::where('patient_id', $patient->id)
                    ->whereDate('appointment_date', $date)
                    ->where('appointment_time', $time.':00')
                    ->where('status', '!=', Appointment::CANCELLED)
                    ->exists();
                if ($patientClash) {
                    throw new SlotUnavailableException('This patient already has an appointment at that date and time.');
                }

                $appointment = Appointment::create([
                    'appointment_number' => $this->numbers->next('appointment', 'APT-'),
                    'patient_id' => $patient->id,
                    'doctor_id' => $locked->id,
                    'appointment_date' => $date,
                    'appointment_time' => $time.':00',
                    'status' => Appointment::SCHEDULED,
                    'slot_key' => Appointment::makeSlotKey($locked->id, $date, $time),
                    'booked_by' => $by->id,
                ]);

                $this->audit->log('appointment.created',
                    "Booked {$appointment->appointment_number} for {$patient->patient_number} with {$locked->name} on {$date} {$time}",
                    $appointment, $by);

                return $appointment;
            });
        } catch (UniqueConstraintViolationException) {
            throw new SlotUnavailableException('That slot has just been taken by another booking.');
        }
    }

    /** @throws ValidationException */
    public function cancel(Appointment $appointment, string $reason, User $by): Appointment
    {
        return $this->db->transaction(function () use ($appointment, $reason, $by) {
            $locked = Appointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === Appointment::COMPLETED) {
                throw new ValidationException('Completed appointments cannot be cancelled.');
            }
            if ($locked->status === Appointment::CANCELLED) {
                throw new ValidationException('This appointment is already cancelled.');
            }
            if ($locked->status === Appointment::NO_SHOW) {
                throw new ValidationException('A no-show appointment cannot be cancelled.');
            }

            // slot_key = NULL releases the slot for subsequent booking.
            $locked->update(['status' => Appointment::CANCELLED, 'slot_key' => null]);

            AppointmentCancellation::create([
                'appointment_id' => $locked->id,
                'reason' => $reason,
                'cancelled_at' => now(),
                'cancelled_by' => $by->id,
            ]);

            $this->audit->log('appointment.cancelled', "Cancelled {$locked->appointment_number}: {$reason}", $locked, $by);

            return $locked->refresh();
        });
    }

    /** @throws ValidationException */
    public function checkIn(Appointment $appointment, User $by): Appointment
    {
        return $this->db->transaction(function () use ($appointment, $by) {
            $locked = Appointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();

            match ($locked->status) {
                Appointment::CANCELLED => throw new ValidationException('Cancelled appointments cannot be checked in.'),
                Appointment::CHECKED_IN => throw new ValidationException('This patient has already been checked in.'),
                Appointment::COMPLETED => throw new ValidationException('This appointment has already been completed.'),
                Appointment::NO_SHOW => throw new ValidationException('This appointment was marked as a no-show.'),
                default => null,
            };

            if (! $locked->appointment_date->isToday()) {
                throw new ValidationException('This appointment is scheduled for '.$locked->appointment_date->format('d M Y').', not today.');
            }

            $locked->update([
                'status' => Appointment::CHECKED_IN,
                'checked_in_at' => now(),
                'checked_in_by' => $by->id,
            ]);

            $this->audit->log('appointment.checked_in', "Checked in {$locked->appointment_number}", $locked, $by);

            return $locked->refresh();
        });
    }

    /** @throws ValidationException */
    public function markNoShow(Appointment $appointment, User $by): Appointment
    {
        return $this->db->transaction(function () use ($appointment, $by) {
            $locked = Appointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== Appointment::SCHEDULED) {
                throw new ValidationException('Only scheduled appointments can be marked as no-show.');
            }
            if ($locked->appointment_date->isFuture()) {
                throw new ValidationException('A future appointment cannot be marked as no-show.');
            }

            $locked->update(['status' => Appointment::NO_SHOW]);
            $this->audit->log('appointment.no_show', "Marked {$locked->appointment_number} as no-show", $locked, $by);

            return $locked->refresh();
        });
    }

    /** Used by the scheduled command: previous-day appointments that were never checked in. */
    public function markPastScheduledAsNoShow(): int
    {
        $count = Appointment::where('status', Appointment::SCHEDULED)
            ->whereDate('appointment_date', '<', today()->toDateString())
            ->update(['status' => Appointment::NO_SHOW]);

        if ($count > 0) {
            $this->audit->log('appointment.no_show', "System marked {$count} past appointment(s) as no-show");
        }

        return $count;
    }
}
