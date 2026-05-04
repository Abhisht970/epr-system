<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Scheme;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Scheme>
 */
class SchemeFactory extends Factory
{
    protected $model = Scheme::class;

    public function definition(): array
    {
        return [
            'code' => 'SCH-'.strtoupper(Str::random(6)),
            'name' => fake()->words(3, true),
            'sponsor' => fake()->randomElement(['nsdc', 'state_govt', 'corporate_csr']),
            'description' => fake()->sentence(),
            'start_date' => now()->subMonths(2),
            'end_date' => now()->addYears(1),
            'status' => 'active',
            'default_rules' => null,
        ];
    }
}
