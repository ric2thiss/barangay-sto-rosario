<?php

namespace Database\Factories;

use App\Models\Resident;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BlotterRecord>
 */
class BlotterRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'form_number' => $this->faker->unique()->regexify('KP Form Number [0-9]{1,3}'),
            'complainant_id' => Resident::factory(),
            'respondent_id' => Resident::factory(),
            'incident_type' => $this->faker->randomElement(['Noise Complaint', 'Property Dispute', 'Physical Altercation', 'Theft', 'Vandalism']),
            'purpose' => $this->faker->sentence(),
            'incident_details' => $this->faker->paragraph(),
            'incident_date' => $this->faker->date(),
            'status' => $this->faker->randomElement(['Pending', 'Completed', 'Dismissed']),
            'recorded_by' => User::factory(),
        ];
    }
}
