<?php

namespace Database\Factories;

use App\Models\Purok;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Resident>
 */
class ResidentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName(),
            'middle_name' => $this->faker->lastName(),
            'last_name' => $this->faker->lastName(),
            'suffix' => $this->faker->optional()->suffix(),
            'birth_date' => $this->faker->date(),
            'sex' => $this->faker->randomElement(['Male', 'Female']),
            'civil_status' => $this->faker->randomElement(['Single', 'Married', 'Widowed', 'Separated']),
            'address' => $this->faker->address(),
            'purok_id' => Purok::factory(),
            'residency_status' => 'Active',
            'date_registered' => $this->faker->date(),
            'contact_number' => $this->faker->phoneNumber(),
        ];
    }
}
