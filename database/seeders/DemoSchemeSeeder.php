<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Scheme;
use App\Models\SchemeEligibilityRule;
use App\Models\SchemePaymentMilestone;
use Illuminate\Database\Seeder;

class DemoSchemeSeeder extends Seeder
{
    public function run(): void
    {
        $scheme = Scheme::updateOrCreate(
            ['code' => 'PMKVY-DEMO'],
            [
                'name' => 'PMKVY Demo Scheme',
                'sponsor' => 'nsdc',
                'description' => 'Demonstration scheme for the EPR system.',
                'start_date' => now()->subMonths(3)->toDateString(),
                'end_date' => now()->addYears(1)->toDateString(),
                'status' => 'active',
            ]
        );

        $rules = [
            ['rule_key' => 'age', 'operator' => 'between', 'value' => [18, 35], 'is_required' => true,
                'error_message' => 'Candidate must be between 18 and 35 years.'],
            ['rule_key' => 'category', 'operator' => 'in',
                'value' => ['general', 'sc', 'st', 'obc', 'ews', 'minority'], 'is_required' => true,
                'error_message' => 'Invalid category.'],
        ];

        foreach ($rules as $rule) {
            SchemeEligibilityRule::updateOrCreate(
                ['scheme_id' => $scheme->id, 'rule_key' => $rule['rule_key']],
                $rule + ['scheme_id' => $scheme->id]
            );
        }

        $milestones = [
            ['milestone_key' => 'registration', 'trigger_event' => 'registration',
                'percentage' => 20, 'fixed_amount' => null, 'payable_to' => 'vendor', 'order' => 1],
            ['milestone_key' => 'training_complete', 'trigger_event' => 'training_complete',
                'percentage' => 30, 'fixed_amount' => null, 'payable_to' => 'vendor', 'order' => 2],
            ['milestone_key' => 'certification', 'trigger_event' => 'certification',
                'percentage' => 30, 'fixed_amount' => null, 'payable_to' => 'vendor', 'order' => 3],
            ['milestone_key' => 'placement', 'trigger_event' => 'placement',
                'percentage' => 20, 'fixed_amount' => null, 'payable_to' => 'vendor', 'order' => 4],
        ];

        foreach ($milestones as $milestone) {
            SchemePaymentMilestone::updateOrCreate(
                ['scheme_id' => $scheme->id, 'milestone_key' => $milestone['milestone_key']],
                $milestone + ['scheme_id' => $scheme->id]
            );
        }

        $course = Course::updateOrCreate(
            ['code' => 'IT-ITeS-DEMO'],
            [
                'name' => 'IT-ITeS Customer Service',
                'sector' => 'IT-ITeS',
                'qp_code' => 'SSC/Q2210',
                'nsqf_level' => 4,
                'duration_hours' => 300,
            ]
        );

        $scheme->courses()->syncWithoutDetaching([
            $course->id => [
                'payout_per_candidate' => 18000,
                'max_batch_size' => 30,
            ],
        ]);
    }
}
