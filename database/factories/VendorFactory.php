<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Vendor>
 */
class VendorFactory extends Factory
{
    protected $model = Vendor::class;

    public function definition(): array
    {
        $stateCodes = ['07', '09', '24', '27', '29', '33'];
        $pan = strtoupper(Str::random(5)).fake()->numerify('####').strtoupper(Str::random(1));

        return [
            'user_id' => User::factory()->vendorUser(),
            'application_no' => 'APP-'.strtoupper(Str::random(8)),
            'org_name' => fake()->company(),
            'org_type' => fake()->randomElement(['proprietorship', 'pvt_ltd', 'llp', 'society']),
            'pan' => $pan,
            'gst' => fake()->randomElement($stateCodes).$pan.'1Z'.strtoupper(Str::random(1)),
            'tan' => null,
            'registration_no' => 'REG-'.strtoupper(Str::random(6)),
            'registered_address' => fake()->address(),
            'communication_address' => fake()->address(),
            'contact_person' => fake()->name(),
            'contact_email' => fake()->companyEmail(),
            'contact_mobile' => '9'.fake()->numerify('#########'),
            'status' => 'submitted',
            'submitted_at' => now(),
        ];
    }
}
