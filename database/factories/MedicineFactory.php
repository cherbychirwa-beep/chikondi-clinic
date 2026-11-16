<?php

namespace Database\Factories;

use App\Models\Medicine;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Medicine> */
class MedicineFactory extends Factory
{
    protected $model = Medicine::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word().' '.fake()->randomNumber(3),
            'generic_name' => null,
            'strength' => '500mg',
            'dosage_form' => 'Tablet',
            'is_active' => true,
        ];
    }
}
