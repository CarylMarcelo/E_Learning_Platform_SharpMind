<?php

use App\Models\Member;
use App\Models\User;
use App\Notifications\MemberRegistered;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Notification;

uses(LazilyRefreshDatabase::class);

test('registration saves separate address fields and defaults to the Philippine calling code', function (): void {
    $user = User::factory()->create();
    $draft = Member::factory()->raw();
    $payload = Arr::only($draft, ['first_name', 'last_name', 'email', 'membership_category_id', 'membership_level_id']);
    Notification::fake([MemberRegistered::class]);

    $this->actingAs($user)->post(route('members.store'), [
        ...$payload, 'phone' => '0912 345 6789', 'street_name' => ' 12 Mabini Street ',
        'city' => 'Pasig', 'state' => 'Metro Manila', 'zip_code' => '01600',
        'latitude' => 14.5764, 'longitude' => 121.0851,
    ])->assertRedirectToRoute('members.index')->assertSessionHasNoErrors();

    $this->assertDatabaseHas('members', [
        'email' => $draft['email'], 'phone_country' => 'PH', 'phone' => '+639123456789',
        'street_name' => '12 Mabini Street', 'city' => 'Pasig', 'state' => 'Metro Manila', 'zip_code' => '01600',
        'address' => '12 Mabini Street, Pasig, Metro Manila, 01600',
        'latitude' => 14.5764, 'longitude' => 121.0851, 'status' => 'Pending',
    ]);
    Notification::assertSentOnDemand(MemberRegistered::class);
});

test('registration combines the selected country code with national or international phone input', function (string $country, string $phone, string $expected): void {
    $user = User::factory()->create();
    $draft = Member::factory()->raw();
    $payload = Arr::only($draft, ['first_name', 'last_name', 'email', 'membership_category_id', 'membership_level_id']);
    Notification::fake([MemberRegistered::class]);

    $this->actingAs($user)->post(route('members.store'), [...$payload, 'phone_country' => $country, 'phone' => $phone])
        ->assertRedirectToRoute('members.index')->assertSessionHasNoErrors();

    $this->assertDatabaseHas('members', ['email' => $draft['email'], 'phone_country' => $country, 'phone' => $expected]);
    Notification::assertSentOnDemand(MemberRegistered::class);
})->with([
    'British national prefix' => ['GB', '020 7946 0958', '+442079460958'],
    'United States national number' => ['US', '(202) 555-0123', '+12025550123'],
    'Italian significant leading zero' => ['IT', '06 6982', '+39066982'],
    'Philippine international number' => ['PH', '+63 912 345 6789', '+639123456789'],
    'Singapore international access prefix' => ['SG', '0065 6123 4567', '+6561234567'],
]);

test('editing saves address components and keeps a members own phone and email valid', function (): void {
    $user = User::factory()->create();
    $member = Member::factory()->create(['phone_country' => 'GB', 'phone' => '+442079460958']);
    $payload = $member->only(['first_name', 'last_name', 'email', 'membership_category_id', 'membership_level_id', 'status']);

    $this->actingAs($user)->put(route('members.update', $member), [
        ...$payload, 'phone_country' => 'GB', 'phone' => '020 7946 0958', 'street_name' => '10 Downing Street',
        'city' => 'London', 'state' => 'England', 'zip_code' => 'SW1A 2AA',
    ])->assertRedirectToRoute('members.index')->assertSessionHasNoErrors();

    $this->assertDatabaseHas('members', [
        'id' => $member->id, 'phone_country' => 'GB', 'phone' => '+442079460958',
        'street_name' => '10 Downing Street', 'city' => 'London', 'state' => 'England', 'zip_code' => 'SW1A 2AA',
        'address' => '10 Downing Street, London, England, SW1A 2AA',
    ]);
    $this->get(route('members.edit', $member))->assertOk()->assertSee('2079460958')->assertSee('SW1A 2AA');
});

test('legacy members can keep their existing address and own local phone number when edited', function (): void {
    $user = User::factory()->create();
    $member = Member::factory()->create(['phone' => '09123456789', 'address' => '42 Legacy Road, Pasig']);
    $payload = $member->only(['first_name', 'last_name', 'email', 'phone', 'address', 'membership_category_id', 'membership_level_id', 'status']);

    $this->actingAs($user)->get(route('members.edit', $member))->assertOk()->assertSee('42 Legacy Road, Pasig');
    $this->put(route('members.update', $member), $payload)->assertRedirectToRoute('members.index')->assertSessionHasNoErrors();

    $this->assertDatabaseHas('members', ['id' => $member->id, 'phone_country' => 'PH', 'phone' => '+639123456789', 'address' => '42 Legacy Road, Pasig']);
});

test('clearing all address fields also clears the formatted address', function (): void {
    $user = User::factory()->create();
    $member = Member::factory()->create(['phone' => '09123456789', 'street_name' => '12 Mabini Street', 'city' => 'Pasig', 'state' => 'Metro Manila', 'zip_code' => '1600', 'address' => '12 Mabini Street, Pasig, Metro Manila, 1600']);
    $payload = $member->only(['first_name', 'last_name', 'email', 'phone', 'membership_category_id', 'membership_level_id', 'status']);

    $this->actingAs($user)->put(route('members.update', $member), [...$payload, 'street_name' => '', 'city' => '', 'state' => '', 'zip_code' => ''])
        ->assertRedirectToRoute('members.index')->assertSessionHasNoErrors();

    $this->assertDatabaseHas('members', ['id' => $member->id, 'street_name' => null, 'city' => null, 'state' => null, 'zip_code' => null, 'address' => null]);
});

test('registration rejects an international phone number already stored in local Philippine format', function (): void {
    $user = User::factory()->create();
    Member::factory()->create(['phone' => '09123456789']);
    $draft = Member::factory()->raw();
    $payload = Arr::only($draft, ['first_name', 'last_name', 'email', 'membership_category_id', 'membership_level_id']);
    Notification::fake([MemberRegistered::class]);

    $this->actingAs($user)->from(route('members.create'))->post(route('members.store'), [...$payload, 'phone_country' => 'PH', 'phone' => '+63 912 345 6789'])
        ->assertRedirectToRoute('members.create')->assertInvalid(['phone' => 'This phone number is already registered to another member.']);

    $this->assertDatabaseCount('members', 1);
    Notification::assertNothingSent();
});

test('registration validates country phone and structured address input before saving', function (array $invalid, string $field, string $message): void {
    $user = User::factory()->create();
    $draft = Member::factory()->raw();
    $payload = Arr::only($draft, ['first_name', 'last_name', 'email', 'phone', 'membership_category_id', 'membership_level_id']);
    Notification::fake([MemberRegistered::class]);

    $this->actingAs($user)->from(route('members.create'))->post(route('members.store'), [...$payload, ...$invalid])
        ->assertRedirectToRoute('members.create')->assertInvalid([$field => $message]);

    $this->assertDatabaseCount('members', 0);
    Notification::assertNothingSent();
})->with([
    'unsupported country' => [['phone_country' => 'XX'], 'phone_country', 'Choose a country from the calling-code list.'],
    'country array' => [['phone_country' => ['PH']], 'phone_country', 'The phone country field must be a string.'],
    'phone text' => [['phone' => 'not a number'], 'phone', 'Enter a valid phone number for the selected country.'],
    'country code mismatch' => [['phone_country' => 'PH', 'phone' => '+12025550123'], 'phone', 'The phone number must match the selected country calling code.'],
    'long locality' => [['street_name' => str_repeat('a', 256)], 'street_name', 'The barangay, village, or district field must not be greater than 255 characters.'],
    'city array' => [['city' => ['Pasig']], 'city', 'The city field must be a string.'],
    'long postal code' => [['zip_code' => str_repeat('1', 21)], 'zip_code', 'The ZIP code field must not be greater than 20 characters.'],
]);

test('invalid registration retains separate address fields and the chosen phone country', function (): void {
    $user = User::factory()->create();
    $draft = Member::factory()->raw();
    $payload = Arr::only($draft, ['first_name', 'last_name', 'email', 'membership_category_id', 'membership_level_id']);
    Notification::fake([MemberRegistered::class]);

    $this->actingAs($user)->from(route('members.create'))->post(route('members.store'), [
        ...$payload, 'phone_country' => 'GB', 'phone' => '020 7946 0958',
        'street_name' => '10 Downing Street', 'city' => 'London', 'state' => 'England', 'zip_code' => str_repeat('A', 21),
    ])->assertInvalid(['zip_code'])->assertSessionHasInput('street_name', '10 Downing Street')->assertSessionHasInput('phone_country', 'GB');

    $this->get(route('members.create'))->assertOk()->assertSee('10 Downing Street')->assertSee('020 7946 0958')->assertSee('London');
    $this->assertDatabaseCount('members', 0);
    Notification::assertNothingSent();
});

test('member address text is escaped when rendered in forms and profiles', function (): void {
    $user = User::factory()->create();
    $draft = Member::factory()->raw();
    $payload = Arr::only($draft, ['first_name', 'last_name', 'email', 'phone', 'membership_category_id', 'membership_level_id']);
    $street = '<img src=x onerror=alert(1)>';
    Notification::fake([MemberRegistered::class]);

    $this->actingAs($user)->post(route('members.store'), [...$payload, 'street_name' => $street, 'city' => 'Pasig'])
        ->assertRedirectToRoute('members.index')->assertSessionHasNoErrors();

    $member = Member::where('email', $draft['email'])->firstOrFail();
    $this->get(route('members.edit', $member))->assertOk()->assertSee($street)->assertDontSee($street, false);
    $this->get(route('members.show', $member))->assertOk()->assertSee($street)->assertDontSee($street, false);
    Notification::assertSentOnDemand(MemberRegistered::class);
});
