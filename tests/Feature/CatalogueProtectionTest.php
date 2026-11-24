<?php

use App\Models\Member;
use App\Models\MembershipCategory;
use App\Models\MembershipLevel;
use App\Models\MembershipTransaction;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('assigned catalogue deletion is blocked without removing members transactions or payments', function (string $kind): void {
    $user = User::factory()->create();
    $member = Member::factory()->active()->create();
    $transaction = MembershipTransaction::factory()->for($member)->approved()->create();
    $payment = Payment::factory()->for($transaction, 'transaction')->create();
    $record = $kind === 'categories' ? $member->category : $member->level;

    $this->actingAs($user)->delete(route($kind.'.destroy', $record))->assertRedirectToRoute($kind.'.index')->assertSessionHas('error');

    $this->assertModelExists($record);
    $this->assertModelExists($member);
    $this->assertModelExists($transaction);
    $this->assertModelExists($payment);
})->with(['categories', 'levels']);

test('catalogues referenced by open purchases remain available even when no member is assigned', function (string $kind, string $status): void {
    $user = User::factory()->create();
    $member = Member::factory()->create();
    $record = $kind === 'categories' ? MembershipCategory::factory()->create() : MembershipLevel::factory()->create();
    $foreignKey = $kind === 'categories' ? 'membership_category_id' : 'membership_level_id';
    $transaction = MembershipTransaction::factory()->for($member)->create([$foreignKey => $record->id, 'status' => $status]);

    $this->actingAs($user)->delete(route($kind.'.destroy', $record))->assertRedirectToRoute($kind.'.index')->assertSessionHas('error');

    $this->assertModelExists($record);
    $this->assertDatabaseHas('membership_transactions', ['id' => $transaction->id, $foreignKey => $record->id, 'status' => $status]);
})->with([
    ['categories', 'Pending'], ['categories', 'Paid'], ['levels', 'Pending'], ['levels', 'Paid'],
]);

test('unused catalogue records can be deleted while closed purchases retain their original descriptions', function (string $kind): void {
    $user = User::factory()->create();
    $member = Member::factory()->create();
    $record = $kind === 'categories' ? MembershipCategory::factory()->create(['name' => 'Original category']) : MembershipLevel::factory()->create(['name' => 'Original level']);
    $foreignKey = $kind === 'categories' ? 'membership_category_id' : 'membership_level_id';
    $snapshotField = $kind === 'categories' ? 'category_name' : 'level_name';
    $transaction = MembershipTransaction::factory()->for($member)->create([$foreignKey => $record->id, 'status' => 'Cancelled']);
    $payment = Payment::factory()->for($transaction, 'transaction')->create();

    $this->actingAs($user)->delete(route($kind.'.destroy', $record))->assertRedirectToRoute($kind.'.index')->assertSessionHas('success');

    $this->assertModelMissing($record);
    $this->assertModelExists($member);
    $this->assertModelExists($payment);
    $this->assertDatabaseHas('membership_transactions', ['id' => $transaction->id, $foreignKey => null, $snapshotField => $record->name]);
    $this->get(route('transactions.show', $transaction))->assertOk()->assertSee($record->name)->assertSee($payment->payment_reference);
})->with(['categories', 'levels']);

test('foreign keys protect assigned catalogues even when deletion bypasses the controller', function (string $kind): void {
    $member = Member::factory()->create();
    $record = $kind === 'categories' ? $member->category : $member->level;

    expect(fn (): ?bool => $record->delete())->toThrow(QueryException::class);

    $this->assertModelExists($record);
    $this->assertModelExists($member);
})->with(['categories', 'levels']);

test('guests cannot change catalogues', function (string $kind): void {
    $record = $kind === 'categories' ? MembershipCategory::factory()->create() : MembershipLevel::factory()->create();

    $this->delete(route($kind.'.destroy', $record))->assertRedirectToRoute('login');

    $this->assertModelExists($record);
})->with(['categories', 'levels']);

test('catalogue forms support validated create read update and deletion for unused records', function (string $kind, array $payload): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->post(route($kind.'.store'), $payload)->assertRedirectToRoute($kind.'.index')->assertSessionHasNoErrors();

    $record = $kind === 'categories' ? MembershipCategory::where('name', $payload['name'])->firstOrFail() : MembershipLevel::where('name', $payload['name'])->firstOrFail();
    $this->get(route($kind.'.index'))->assertOk()->assertSee($payload['name']);
    $this->put(route($kind.'.update', $record), [...$payload, 'name' => 'Updated catalogue'])->assertRedirectToRoute($kind.'.index')->assertSessionHasNoErrors();
    expect($record->fresh()->name)->toBe('Updated catalogue');
    $this->delete(route($kind.'.destroy', $record))->assertRedirectToRoute($kind.'.index')->assertSessionHas('success');
    $this->assertModelMissing($record);
})->with([
    'category' => ['categories', ['name' => 'New category', 'description' => 'Test category', 'discount_percent' => 20]],
    'level' => ['levels', ['name' => 'New level', 'fee' => 500, 'validity_days' => 30, 'benefits' => "Course access\nStudy guides"]],
]);

test('level fees reject unsupported precision and values larger than the database can store', function (string $fee): void {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('levels.store'), ['name' => 'Invalid fee level', 'fee' => $fee, 'validity_days' => 30])
        ->assertRedirect()->assertInvalid(['fee']);

    $this->assertDatabaseMissing('membership_levels', ['name' => 'Invalid fee level']);
})->with(['too many centavo digits' => ['500.001'], 'database amount limit' => ['100000000.00']]);
