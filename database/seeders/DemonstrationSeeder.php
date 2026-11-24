<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\MembershipCategory;
use App\Models\MembershipLevel;
use App\Models\MembershipTransaction;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Seeder;
use LogicException;

class DemonstrationSeeder extends Seeder
{
    public function run(): void
    {
      //  if (! app()->environment(['local', 'testing']) || ! config('demo.enabled')) {
     //      throw new LogicException('Demonstration data requires a disposable local/testing database and DEMO_MODE=true.');
      //  }

        $this->call(MembershipCatalogueSeeder::class);
        User::firstOrCreate(['email' => 'admin@sharpmind.test'], ['name' => 'Demo Admin', 'password' => 'SharpMindDemo2026!']);
        $year = now(config('mail.membership_timezone'))->format('Y');
        $people = [
            ['Avery', 'Santos', 'Student', 'Basic', 'Active'],
            ['Jordan', 'Reyes', 'Regular', 'Silver', 'Active'],
            ['Morgan', 'Lim', 'Senior Citizen', 'Gold', 'Pending'],
            ['Taylor', 'Cruz', 'Corporate', 'Platinum', 'Inactive'],
            ['Casey', 'Villanueva', 'Student', 'Basic', 'Pending'],
        ];
        $members = [];

        foreach ($people as $index => [$firstName, $lastName, $categoryName, $levelName, $status]) {
            $category = MembershipCategory::where('name', $categoryName)->firstOrFail();
            $level = MembershipLevel::where('name', $levelName)->firstOrFail();
            $members[] = Member::firstOrCreate(['membership_number' => 'MEM-'.$year.'-DEMO'.($index + 1)], [
                'first_name' => $firstName, 'last_name' => $lastName,
                'email' => strtolower($firstName).'.'.strtolower($lastName).'@example.test',
                'phone_country' => 'PH', 'phone' => '+63917000000'.($index + 1),
                'street_name' => 'San Antonio', 'city' => 'Pasig', 'state' => 'Metro Manila', 'zip_code' => '1600',
                'address' => 'San Antonio, Pasig, Metro Manila, 1600', 'latitude' => 14.5764, 'longitude' => 121.0851,
                'membership_category_id' => $category->id, 'membership_level_id' => $level->id, 'status' => $status,
            ]);
        }

        foreach ([
            [0, 'Application', 'Approved', 25],
            [1, 'Application', 'Approved', 10],
            [2, 'Application', 'Paid', 0],
            [3, 'Application', 'Expired', 400],
            [4, 'Application', 'Pending', 0],
            [3, 'Renewal', 'Cancelled', 1],
            [0, 'Renewal', 'Pending', 0],
        ] as $index => [$memberIndex, $type, $status, $daysAgo]) {
            $member = $members[$memberIndex];
            $approvedAt = in_array($status, ['Approved', 'Expired'], true) ? now()->subDays($daysAgo) : null;
            $transaction = MembershipTransaction::firstOrCreate(['reference_number' => 'TRX-'.$year.'-DEMO'.($index + 1)], [
                ...MembershipTransaction::purchaseSnapshot($member, $member->level),
                'member_id' => $member->id, 'type' => $type, 'status' => $status, 'amount' => $member->getEffectiveFee(),
                'approved_at' => $approvedAt, 'expires_at' => $approvedAt?->copy()->addDays($member->level->validity_days),
                'created_at' => now()->subDays($daysAgo)->subMinutes($index + 5),
                'updated_at' => $approvedAt ?? now()->subMinutes($index + 1),
            ]);

            if (in_array($status, ['Paid', 'Approved', 'Expired'], true)) {
                Payment::firstOrCreate(['membership_transaction_id' => $transaction->id], [
                    'payment_reference' => 'PAY-'.$year.'-DEMO'.($index + 1), 'amount_paid' => $transaction->amount,
                    'payment_method' => $index % 2 === 0 ? 'GCash' : 'Cash', 'payment_date' => $transaction->created_at,
                ]);
            }
        }
    }
}
