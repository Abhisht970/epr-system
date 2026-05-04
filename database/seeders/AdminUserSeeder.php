<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['mobile' => '9999999999'],
            [
                'name' => 'EPR Admin',
                'email' => 'admin@epr.local',
                'mobile_verified_at' => now(),
                'email_verified_at' => now(),
                'password' => Hash::make('admin@1234'),
                'status' => 'active',
            ]
        );

        if (! $admin->hasRole(User::ROLE_ADMIN)) {
            $admin->assignRole(User::ROLE_ADMIN);
        }
    }
}
