<?php

use App\Models\Member;
use App\Models\MembershipCategory;
use App\Models\MembershipLevel;
use App\Models\MembershipTransaction;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\MembershipExpiringReminder;
use App\Notifications\MembershipTransactionApproved;
use App\Notifications\MembershipTransactionSubmitted;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(LazilyRefreshDatabase::class);

test('a pending or paid renewal preserves the current membership until approval and then extends its remaining validity', function (): void {
    $this->travelTo('2026-11-24 00:00:00');
    Notification::fake();
    $user = User::factory()->create();
    $category = MembershipCategory::factory()->create(['name' => 'Student', 'discount_percent' => 20]);
    $platinum = MembershipLevel::factory()->create(['name' => 'Platinum', 'fee' => 5000, 'validity_days' => 365]);
    $silver = MembershipLevel::factory()->create(['name' => 'Silver', 'fee' => 1200, 'validity_days' => 90]);
    $member = Member::factory()->active()->for($category, 'category')->for($platinum, 'level')->create();
    $original = MembershipTransaction::factory()->for($member)->approved()->create(['amount' => 4000, 'updated_at' => '2026-11-04 00:00:00']);
    $this->actingAs($user);

    $this->post(route('transactions.store'), ['member_id' => $member->id, 'membership_level_id' => $silver->id, 'type' => 'Renewal'])
        ->assertRedirectToRoute('transactions.index')->assertSessionHasNoErrors();
    $renewal = MembershipTransaction::latest('id')->firstOrFail();

    $this->assertDatabaseHas('members', ['id' => $member->id, 'membership_level_id' => $platinum->id, 'status' => 'Active']);
    $this->assertDatabaseHas('membership_transactions', ['id' => $renewal->id, 'membership_level_id' => $silver->id, 'level_name' => 'Silver', 'category_name' => 'Student', 'base_fee' => 1200, 'discount_percent' => 20, 'amount' => 960, 'validity_days' => 90, 'status' => 'Pending']);
    $this->get(route('transactions.show', $original))->assertOk()->assertSee('Platinum')->assertDontSee('Silver');
    $this->post(route('payments.store', $renewal), ['amount_paid' => 960, 'payment_method' => 'GCash'])->assertRedirectToRoute('transactions.index');
    $this->assertDatabaseHas('members', ['id' => $member->id, 'membership_level_id' => $platinum->id]);
    $this->post(route('transactions.approve', $renewal))->assertRedirect()->assertSessionHas('success');
    $this->assertDatabaseHas('members', ['id' => $member->id, 'membership_level_id' => $silver->id, 'status' => 'Active']);
    expect($renewal->fresh()->expires_at->format('Y-m-d H:i:s'))->toBe('2028-02-02 00:00:00');
    expect($original->fresh()->expires_at->format('Y-m-d H:i:s'))->toBe('2027-11-04 00:00:00');
    $this->get(route('transactions.show', $original))->assertOk()->assertSee('Platinum')->assertDontSee('Silver');
    $this->get(route('members.show', $member))->assertOk()->assertSee('Platinum')->assertSee('Silver');
    Notification::assertSentOnDemand(MembershipTransactionSubmitted::class);
    Notification::assertSentOnDemandTimes(MembershipTransactionApproved::class, 1);
});

test('catalogue edits cannot change the purchased fee benefits validity reminder or receipt', function (): void {
    $this->travelTo('2026-11-24 00:00:00');
    Notification::fake();
    $user = User::factory()->create();
    $category = MembershipCategory::factory()->create(['name' => 'Student', 'discount_percent' => 20]);
    $level = MembershipLevel::factory()->create(['name' => 'Basic', 'fee' => 500, 'validity_days' => 30, 'benefits' => "Course access\nStudy guides"]);
    $member = Member::factory()->for($category, 'category')->for($level, 'level')->create();
    $this->actingAs($user)->post(route('transactions.store'), ['member_id' => $member->id, 'membership_level_id' => $level->id, 'type' => 'Application'])->assertRedirect();
    $transaction = MembershipTransaction::firstOrFail();
    $level->update(['name' => 'Changed level', 'fee' => 5000, 'validity_days' => 1, 'benefits' => 'Changed benefits']);
    $category->update(['name' => 'Changed category', 'discount_percent' => 0]);
    $this->travel(5)->days();

    $this->post(route('payments.store', $transaction), ['amount_paid' => 400, 'payment_method' => 'Cash'])->assertRedirect();
    $this->post(route('transactions.approve', $transaction))->assertRedirect()->assertSessionHas('success');

    $this->assertDatabaseHas('membership_transactions', ['id' => $transaction->id, 'level_name' => 'Basic', 'category_name' => 'Student', 'amount' => 400, 'base_fee' => 500, 'discount_percent' => 20, 'validity_days' => 30]);
    expect($transaction->fresh()->expires_at->format('Y-m-d H:i:s'))->toBe('2026-12-29 00:00:00');
    expect($transaction->fresh()->benefitItems())->toBe(['Course access', 'Study guides']);
    $this->get(route('transactions.show', $transaction))->assertOk()->assertSee('Basic')->assertSee('Student')->assertSee('30 days')->assertDontSee('Changed level');
    Notification::assertSentOnDemand(MembershipTransactionApproved::class, fn (MembershipTransactionApproved $notification, array $channels, object $recipient): bool => $notification->toMail($recipient)->viewData['levelName'] === 'Basic' && $notification->toMail($recipient)->viewData['validityDays'] === 30);
    $this->travel(23)->days();
    $this->artisan('memberships:expire')->expectsOutput('Expired 0 membership(s) and queued expiration emails.')->assertExitCode(0);
    $this->artisan('memberships:send-expiry-reminders')->expectsOutput('Queued 1 expiring membership reminder(s).')->assertExitCode(0);
    Notification::assertSentOnDemand(MembershipExpiringReminder::class, fn (MembershipExpiringReminder $notification): bool => $notification->daysLeft === 7 && $notification->expiryDate === 'December 29, 2026');
});

test('paid status without a sufficient recorded payment cannot activate a membership', function (bool $hasPayment): void {
    Notification::fake();
    $user = User::factory()->create();
    $member = Member::factory()->create();
    $transaction = MembershipTransaction::factory()->for($member)->paid()->create(['amount' => 1000]);

    if ($hasPayment) {
        Payment::factory()->for($transaction, 'transaction')->create(['amount_paid' => 999]);
    }

    $this->actingAs($user)->post(route('transactions.approve', $transaction))->assertRedirect()
        ->assertSessionHas('error', 'A recorded payment covering the full transaction amount is required before approval.');

    $this->assertDatabaseHas('membership_transactions', ['id' => $transaction->id, 'status' => 'Paid', 'approved_at' => null, 'expires_at' => null]);
    $this->assertDatabaseHas('members', ['id' => $member->id, 'status' => 'Pending']);
    Notification::assertNothingSent();
})->with(['missing payment' => [false], 'underpayment' => [true]]);

test('approving the same transaction twice cannot extend validity or send duplicate approval emails', function (): void {
    $this->travelTo('2026-11-24 00:00:00');
    Notification::fake([MembershipTransactionApproved::class]);
    $user = User::factory()->create();
    $level = MembershipLevel::factory()->create(['validity_days' => 30]);
    $member = Member::factory()->for($level, 'level')->create();
    $transaction = MembershipTransaction::factory()->for($member)->paid()->create();
    Payment::factory()->for($transaction, 'transaction')->create(['amount_paid' => 1000]);
    $this->actingAs($user)->post(route('transactions.approve', $transaction))->assertRedirect()->assertSessionHas('success');
    $this->travel(1)->days();

    $this->post(route('transactions.approve', $transaction))->assertRedirect()->assertSessionHas('error', 'Only paid transactions can be approved.');

    expect($transaction->fresh()->expires_at->format('Y-m-d H:i:s'))->toBe('2026-12-24 00:00:00');
    Notification::assertSentOnDemandTimes(MembershipTransactionApproved::class, 1);
});

test('changing an old transaction timestamp cannot make it the current membership', function (): void {
    $this->freezeTime();
    Notification::fake();
    $user = User::factory()->create();
    $member = Member::factory()->active()->create();
    $old = MembershipTransaction::factory()->for($member)->approved()->create(['updated_at' => now()->subDays(20)]);
    $current = MembershipTransaction::factory()->for($member)->approved()->create(['updated_at' => now()->subDays(10)]);
    $old->touch();

    $this->actingAs($user)->post(route('transactions.cancel', $old))->assertRedirect()->assertSessionHas('error');

    expect($member->fresh()->latestApprovedTransaction->id)->toBe($current->id);
    $this->assertDatabaseHas('members', ['id' => $member->id, 'status' => 'Active']);
    Notification::assertNothingSent();
});

test('payments reject amounts that cannot be represented exactly in the database', function (string $amount): void {
    Notification::fake();
    $user = User::factory()->create();
    $transaction = MembershipTransaction::factory()->create(['amount' => 1000]);

    $this->actingAs($user)->post(route('payments.store', $transaction), ['amount_paid' => $amount, 'payment_method' => 'Cash'])
        ->assertRedirect()->assertInvalid(['amount_paid']);

    $this->assertDatabaseHas('membership_transactions', ['id' => $transaction->id, 'status' => 'Pending']);
    $this->assertDatabaseCount('payments', 0);
    Notification::assertNothingSent();
})->with(['too many centavo digits' => ['1000.001'], 'database amount limit' => ['100000000.00']]);
