<?php

namespace Database\Factories;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Patient> */
class PatientFactory extends Factory
{
    protected $model = Patient::class;

    public function definition(): array
    {
        return [
            'patient_number' => 'CC-'.fake()->unique()->numerify('9#####'),
            'full_name' => fake()->name(),
            'date_of_birth' => fake()->dateTimeBetween('-80 years', '-1 year')->format('Y-m-d'),
            'gender' => fake()->randomElement(['male', 'female']),
            'address' => fake()->streetAddress(),
            'phone' => '+265 888 '.fake()->unique()->numerify('### ###'),
            'next_of_kin_name' => fake()->name(),
            'next_of_kin_phone' => '+265 999 '.fake()->numerify('### ###'),
            'primary_doctor_id' => null,
            'registered_on' => today()->toDateString(),
            'registered_by' => fn () => User::factory()->role('attendant')->create()->id,
        ];
    }

    public function withDoctor(?Doctor $doctor = null): static
    {
        return $this->state(fn () => ['primary_doctor_id' => $doctor?->id ?? Doctor::factory()->create()->id]);
    }
}
