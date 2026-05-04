<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        return [
            'code' => 'CRS-'.strtoupper(Str::random(6)),
            'name' => fake()->words(2, true),
            'sector' => fake()->randomElement(['IT-ITeS', 'Retail', 'Logistics', 'BFSI', 'Healthcare']),
            'qp_code' => 'QP-'.strtoupper(Str::random(4)),
            'nsqf_level' => fake()->numberBetween(3, 7),
            'duration_hours' => fake()->randomElement([200, 300, 400, 500]),
        ];
    }
}
