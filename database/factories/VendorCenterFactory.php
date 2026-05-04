<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Vendor;
use App\Models\VendorCenter;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<VendorCenter>
 */
class VendorCenterFactory extends Factory
{
    protected $model = VendorCenter::class;

    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory(),
            'center_code' => 'VC-'.strtoupper(Str::random(8)),
            'name' => fake()->company().' Center',
            'address' => fake()->streetAddress(),
            'district' => fake()->city(),
            'state' => fake()->randomElement(['Maharashtra', 'Karnataka', 'Tamil Nadu', 'Delhi', 'UP']),
            'pincode' => fake()->numerify('######'),
            'capacity' => fake()->randomElement([30, 60, 90, 120]),
            'status' => 'active',
        ];
    }
}
