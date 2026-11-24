<?php

namespace Database\Seeders;

use App\Models\MembershipCategory;
use App\Models\MembershipLevel;
use Illuminate\Database\Seeder;

class MembershipCatalogueSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Student', 'description' => 'Learners currently enrolled in a school, college, or university.', 'discount_percent' => 20],
            ['name' => 'Regular', 'description' => 'Individual adult learners enrolling at standard membership rates.', 'discount_percent' => 0],
            ['name' => 'Senior Citizen', 'description' => 'Learners aged 60 and above with a valid senior citizen ID.', 'discount_percent' => 20],
            ['name' => 'Corporate', 'description' => 'Company-sponsored employees and teams learning together.', 'discount_percent' => 0],
        ] as $category) {
            MembershipCategory::firstOrCreate(['name' => $category['name']], $category);
        }

        foreach ([
            ['name' => 'Basic', 'fee' => 500, 'validity_days' => 30, 'benefits' => "Access to introductory courses\nCommunity forums\nDownloadable study guides"],
            ['name' => 'Silver', 'fee' => 1200, 'validity_days' => 90, 'benefits' => "Full access to core courses\nQuiz assessments\nPriority instructor Q&A support"],
            ['name' => 'Gold', 'fee' => 2500, 'validity_days' => 180, 'benefits' => "Unlimited access to all courses\nVerified digital certificates\nLive monthly webinars"],
            ['name' => 'Platinum', 'fee' => 5000, 'validity_days' => 365, 'benefits' => "All-inclusive access to courses\nCertificates\n1-on-1 mentoring sessions\nExclusive workshops"],
        ] as $level) {
            MembershipLevel::firstOrCreate(['name' => $level['name']], $level);
        }
    }
}
