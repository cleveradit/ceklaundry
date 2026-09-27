<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return ['nama' => fake()->name(), 'email' => fake()->unique()->safeEmail(), 'password' => Str::random(32), 'role' => 'developer', 'is_active' => true, 'must_change_password' => false];
    }
}
