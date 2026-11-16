<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Appointment> */
class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        return [
            'appointment_number' => 'APT-'.fake()->unique()->numerify('9#####'),
            'patient_id' => fn () => Patient::factory()->create()->id,
            'doctor_id' => fn () => Doctor::factory()->create()->id,
            'appointment_date' => today()->toDateString(),
            'appointment_time' => '10:00:00',
            'status' => Appointment::SCHEDULED,
            'booked_by' => fn () => User::factory()->role('receptionist')->create()->id,
        ];
    }

    /** Sets the status and keeps slot_key consistent with it (cancelled rows free their slot). */
    public function configure(): static
    {
        return $this->afterMaking(function (Appointment $a) {
            $a->slot_key = $a->status === Appointment::CANCELLED
                ? null
                : Appointment::makeSlotKey((int) $a->doctor_id, (string) (is_string($a->appointment_date) ? substr($a->appointment_date, 0, 10) : $a->appointment_date->toDateString()), (string) $a->appointment_time);
        });
    }
}
