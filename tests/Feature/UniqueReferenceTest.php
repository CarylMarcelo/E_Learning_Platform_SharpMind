<?php

use App\Models\Member;
use App\Models\MembershipTransaction;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\MemberRegistered;
use App\Notifications\MembershipTransactionSubmitted;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

uses(LazilyRefreshDatabase::class);

afterEach(function (): void {
    Str::createRandomStringsNormally();
});

test('registration retries a duplicate generated membership number', function (): void {
    $this->travelTo('2026-11-24 00:00:00');
    Notification::fake([MemberRegistered::class]);
    $user = User::factory()->create();
    $existing = Member::factory()->create(['membership_number' => 'MEM-2026-CLASH']);
    $sequence = ['CLASH', 'FRESH'];
    Str::createRandomStringsUsing(function (int $length) use (&$sequence): string {
        return $length === 5 ? array_shift($sequence) : str_repeat('x', $length);
    });

    $this->actingAs($user)->post(route('members.store'), [
        'first_name' => 'Avery', 'last_name' => 'Reference', 'email' => 'avery.reference@example.test', 'phone' => '09123456789',
        'membership_category_id' => $existing->membership_category_id, 'membership_level_id' => $existing->membership_level_id,
    ])->assertRedirectToRoute('members.index')->assertSessionHasNoErrors();

    $this->assertDatabaseHas('members', ['email' => 'avery.reference@example.test', 'membership_number' => 'MEM-2026-FRESH']);
    $this->assertDatabaseCount('members', 2);
    Notification::assertSentOnDemandTimes(MemberRegistered::class, 1);
});

test('exhausted membership number collisions return a validation error without saving or emailing', function (): void {
    $this->travelTo('2026-11-24 00:00:00');
    Notification::fake();
    $user = User::factory()->create();
    $existing = Member::factory()->create(['membership_number' => 'MEM-2026-CLASH']);
    Str::createRandomStringsUsing(fn (int $length): string => $length === 5 ? 'CLASH' : str_repeat('x', $length));

    $this->actingAs($user)->post(route('members.store'), [
        'first_name' => 'Avery', 'last_name' => 'Reference', 'email' => 'avery.reference@example.test', 'phone' => '09123456789',
        'membership_category_id' => $existing->membership_category_id, 'membership_level_id' => $existing->membership_level_id,
    ])->assertRedirect()->assertInvalid(['membership_number' => 'A unique membership number could not be generated. Please try again.']);

    $this->assertDatabaseCount('members', 1);
    Notification::assertNothingSent();
});

test('transaction creation retries a duplicate reference without changing the active level', function (): void {
    $this->travelTo('2026-11-24 00:00:00');
    Notification::fake([MembershipTransactionSubmitted::class]);
    $user = User::factory()->create();
    $member = Member::factory()->active()->create();
    MembershipTransaction::factory()->for($member)->create(['reference_number' => 'TRX-2026-CLASH']);
    $sequence = ['CLASH', 'FRESH'];
    Str::createRandomStringsUsing(function (int $length) use (&$sequence): string {
        return $length === 5 ? array_shift($sequence) : str_repeat('x', $length);
    });

    $this->actingAs($user)->post(route('transactions.store'), ['member_id' => $member->id, 'membership_level_id' => $member->membership_level_id, 'type' => 'Renewal'])
        ->assertRedirectToRoute('transactions.index')->assertSessionHasNoErrors();

    $this->assertDatabaseHas('membership_transactions', ['reference_number' => 'TRX-2026-FRESH', 'member_id' => $member->id]);
    $this->assertDatabaseCount('membership_transactions', 2);
    Notification::assertSentOnDemandTimes(MembershipTransactionSubmitted::class, 1);
});

test('payment recording retries a duplicate payment reference without recording two payments', function (): void {
    $this->travelTo('2026-11-24 00:00:00');
    $user = User::factory()->create();
    $member = Member::factory()->create();
    $previous = MembershipTransaction::factory()->for($member)->paid()->create();
    Payment::factory()->for($previous, 'transaction')->create(['payment_reference' => 'PAY-2026-CLASH']);
    $target = MembershipTransaction::factory()->for($member)->create(['amount' => 1000]);
    $sequence = ['CLASH', 'FRESH'];
    Str::createRandomStringsUsing(function (int $length) use (&$sequence): string {
        return $length === 5 ? array_shift($sequence) : str_repeat('x', $length);
    });

    $this->actingAs($user)->post(route('payments.store', $target), ['amount_paid' => 1000, 'payment_method' => 'Cash'])
        ->assertRedirectToRoute('transactions.index')->assertSessionHasNoErrors();

    $this->assertDatabaseHas('payments', ['membership_transaction_id' => $target->id, 'payment_reference' => 'PAY-2026-FRESH', 'amount_paid' => 1000]);
    $this->assertDatabaseCount('payments', 2);
    $this->assertDatabaseHas('membership_transactions', ['id' => $target->id, 'status' => 'Paid']);
});
