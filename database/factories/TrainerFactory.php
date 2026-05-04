<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Trainer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Trainer>
 */
class TrainerFactory extends Factory
{
    protected $model = Trainer::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->trainerUser(),
            'full_name' => fake()->name(),
            'dob' => fake()->dateTimeBetween('-50 years', '-25 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(['male', 'female']),
            'qualification' => fake()->randomElement(['B.Tech', 'M.Tech', 'MBA', 'B.Sc', 'Diploma']),
            'experience_years' => fake()->numberBetween(2, 20),
            'tot_certificate_no' => 'TOT-'.strtoupper(Str::random(8)),
            'tot_issued_by' => 'NSDC',
            'tot_valid_until' => now()->addYears(2),
            'sectors' => fake()->randomElements(['IT-ITeS', 'Retail', 'BFSI'], 2),
            'status' => 'verified',
        ];
    }
}
