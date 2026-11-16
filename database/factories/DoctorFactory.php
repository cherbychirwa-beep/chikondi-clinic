<?php

namespace Database\Factories;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Doctor> */
class DoctorFactory extends Factory
{
    protected $model = Doctor::class;

    public function definition(): array
    {
        return [
            'user_id' => fn () => User::factory()->role('doctor')->create()->id,
            'doctor_number' => 'DR-'.fake()->unique()->numerify('####'),
            'phone' => '+265 999 '.fake()->numerify('### ###'),
            'consulting_room' => (string) fake()->numberBetween(1, 20),
            'employment_type' => Doctor::FULL_TIME,
            'is_active' => true,
        ];
    }

    public function visiting(?string $from = null, int $days = 3): static
    {
        return $this->state(fn () => [
            'employment_type' => Doctor::VISITING,
            'next_visit_date' => $from ?? today()->toDateString(),
            'next_visit_duration_days' => $days,
        ]);
    }
}
