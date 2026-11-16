<?php

namespace Tests;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Doctor;
use App\Models\FeeSetting;
use App\Models\Patient;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    protected function staff(string $role, array $attributes = []): User
    {
        return User::factory()->role($role)->create($attributes);
    }

    /**
     * A doctor (with login user). By default available all seven days 08:00-17:00 in 30-minute slots,
     * so tests do not depend on the weekday they run on.
     */
    protected function doctor(string $type = 'full_time', bool $withSchedule = true, array $attributes = []): Doctor
    {
        $factory = Doctor::factory();
        if ($type === 'visiting') {
            $factory = $factory->visiting();
        }

        $doctor = $factory->create($attributes);

        if ($withSchedule) {
            foreach (range(0, 6) as $day) {
                $doctor->schedules()->create([
                    'day_of_week' => $day, 'start_time' => '08:00:00', 'end_time' => '17:00:00',
                    'slot_minutes' => 30, 'is_active' => true,
                ]);
            }
        }

        return $doctor->load('user');
    }

    protected function patient(?Doctor $doctor = null, array $attributes = []): Patient
    {
        $factory = Patient::factory();
        if ($doctor) {
            $factory = $factory->withDoctor($doctor);
        }

        return $factory->create($attributes);
    }

    protected function fee(float $amount = 15000): FeeSetting
    {
        return FeeSetting::create([
            'amount' => $amount,
            'effective_from' => today()->subDay()->toDateString(),
            'created_by' => $this->staff('admin')->id,
        ]);
    }

    protected function appointment(Doctor $doctor, Patient $patient, string $status = 'scheduled', ?string $date = null, string $time = '10:00:00'): Appointment
    {
        return Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'status' => $status,
            'appointment_date' => $date ?? today()->toDateString(),
            'appointment_time' => $time,
        ]);
    }

    /** A completed appointment plus its consultation (fee snapshot set directly). */
    protected function consultation(float $fee = 15000, ?Doctor $doctor = null, ?Patient $patient = null, string $time = '10:00:00'): Consultation
    {
        $doctor ??= $this->doctor();
        $patient ??= $this->patient($doctor);
        $appointment = $this->appointment($doctor, $patient, 'completed', null, $time);

        return Consultation::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'consultation_date' => today()->toDateString(),
            'diagnosis' => 'Test diagnosis',
            'fee_charged' => $fee,
        ]);
    }

    protected function tomorrow(): string
    {
        return today()->addDay()->toDateString();
    }
}
