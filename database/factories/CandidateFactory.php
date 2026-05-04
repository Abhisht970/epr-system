<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Candidate;
use App\Models\Course;
use App\Models\Scheme;
use App\Models\Vendor;
use App\Models\VendorCenter;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Candidate>
 */
class CandidateFactory extends Factory
{
    protected $model = Candidate::class;

    public function definition(): array
    {
        $aadhaar = $this->generateValidAadhaar();

        return [
            'application_id' => 'CAND-'.strtoupper(Str::random(10)),
            'aadhaar' => $aadhaar,
            'full_name' => fake()->name(),
            'dob' => fake()->dateTimeBetween('-35 years', '-18 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(['male', 'female']),
            'mobile' => '9'.fake()->numerify('#########'),
            'email' => fake()->safeEmail(),
            'address' => fake()->address(),
            'district' => fake()->city(),
            'state' => fake()->randomElement(['Maharashtra', 'Karnataka', 'Tamil Nadu']),
            'pincode' => fake()->numerify('######'),
            'education' => fake()->randomElement(['10th', '12th', 'Graduate', 'Diploma']),
            'category' => fake()->randomElement(['general', 'sc', 'st', 'obc']),
            'pwd' => false,
            'bpl' => false,
            'vendor_id' => Vendor::factory(),
            'vendor_center_id' => VendorCenter::factory(),
            'scheme_id' => Scheme::factory(),
            'course_id' => Course::factory(),
            'batch_no' => 'B-'.fake()->numerify('####'),
            'status' => Candidate::STATUS_REGISTERED,
            'enrolled_at' => now(),
            'status_changed_at' => now(),
        ];
    }

    /**
     * Generate a Verhoeff-valid 12-digit Aadhaar test number.
     */
    private function generateValidAadhaar(): string
    {
        $d = [
            [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
            [1, 2, 3, 4, 0, 6, 7, 8, 9, 5],
            [2, 3, 4, 0, 1, 7, 8, 9, 5, 6],
            [3, 4, 0, 1, 2, 8, 9, 5, 6, 7],
            [4, 0, 1, 2, 3, 9, 5, 6, 7, 8],
            [5, 9, 8, 7, 6, 0, 4, 3, 2, 1],
            [6, 5, 9, 8, 7, 1, 0, 4, 3, 2],
            [7, 6, 5, 9, 8, 2, 1, 0, 4, 3],
            [8, 7, 6, 5, 9, 3, 2, 1, 0, 4],
            [9, 8, 7, 6, 5, 4, 3, 2, 1, 0],
        ];
        $p = [
            [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
            [1, 5, 7, 6, 2, 8, 3, 0, 9, 4],
            [5, 8, 0, 3, 7, 9, 6, 1, 4, 2],
            [8, 9, 1, 6, 0, 4, 3, 5, 2, 7],
            [9, 4, 5, 3, 1, 2, 6, 8, 7, 0],
            [4, 2, 8, 6, 5, 7, 3, 9, 0, 1],
            [2, 7, 9, 3, 8, 0, 6, 4, 1, 5],
            [7, 0, 4, 6, 9, 1, 3, 2, 5, 8],
        ];
        $invert = [0, 4, 3, 2, 1, 5, 6, 7, 8, 9];

        // Build 11 leading digits, ensuring first digit is 2-9 (UIDAI never issues 0/1).
        $first = (string) random_int(2, 9);
        $rest = '';
        for ($i = 0; $i < 10; $i++) {
            $rest .= (string) random_int(0, 9);
        }
        $leading = $first.$rest;

        $c = 0;
        $reversed = array_reverse(str_split($leading));
        foreach ($reversed as $i => $digit) {
            $c = $d[$c][$p[($i + 1) % 8][(int) $digit]];
        }
        $check = $invert[$c];

        return $leading.$check;
    }
}
