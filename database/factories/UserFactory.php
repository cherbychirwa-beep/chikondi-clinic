<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'Password123',
            'role_id' => fn () => Role::firstOrCreate(['slug' => 'receptionist'], ['name' => 'Receptionist'])->id,
            'is_active' => true,
            'must_change_password' => false,
        ];
    }

    public function role(string $slug): static
    {
        return $this->state(fn () => [
            'role_id' => Role::where('slug', $slug)->firstOrFail()->id,
        ]);
    }

    public function mustChangePassword(): static
    {
        return $this->state(fn () => ['must_change_password' => true]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
