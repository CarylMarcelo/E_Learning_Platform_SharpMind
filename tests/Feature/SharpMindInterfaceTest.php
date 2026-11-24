<?php

use App\Models\Member;
use App\Models\MembershipCategory;
use App\Models\MembershipLevel;
use App\Models\MembershipTransaction;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\MemberRegistered;
use App\Notifications\MembershipTransactionApproved;
use App\Notifications\MembershipTransactionStatusUpdated;
use App\Notifications\MembershipTransactionSubmitted;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(LazilyRefreshDatabase::class);

test('guests are redirected to login before accessing the workspace', function (string $path): void {
    $this->get($path)->assertRedirectToRoute('login');
})->with(['/dashboard', '/members', '/transactions', '/categories', '/levels']);

test('renders the branded login form and accepts the remember preference', function (): void {
    $user = User::factory()->create();

    $this->get('/login')->assertOk()->assertSee('Welcome back')->assertSee('Sharp');
    $this->post('/login', ['email' => $user->email, 'password' => 'password', 'remember' => '1'])
        ->assertRedirectToRoute('dashboard')
        ->assertCookie(auth()->guard()->getRecallerName());

    $this->assertAuthenticatedAs($user);
});

test('renders every workspace page when the community is empty', function (string $path, string $heading): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get($path)->assertOk()->assertSee($heading)->assertSee('Sharp');
})->with([
    'dashboard' => ['/dashboard', 'Your next chapter starts here'],
    'members' => ['/members', 'Make room for bright minds'],
    'register' => ['/members/create', 'Registration Form'],
    'transactions' => ['/transactions', 'A fresh page for your community'],
    'new transaction' => ['/transactions/create', 'Start a new chapter'],
    'categories' => ['/categories', 'Every community starts somewhere'],
    'levels' => ['/levels', 'Open up new possibilities'],
]);

test('shows live dashboard totals and newly registered members', function (): void {
    $this->freezeTime();
    $user = User::factory()->create();
    $member = Member::factory()->active()->create(['first_name' => 'Avery']);
    Member::factory()->inactive()->create();
    Member::factory()->create();
    $transaction = MembershipTransaction::factory()->for($member)->approved()->create();
    Payment::factory()->for($transaction, 'transaction')->create(['amount_paid' => 1250]);
    MembershipTransaction::factory()->for($member)->create();

    $this->actingAs($user)->get('/dashboard')->assertOk()
        ->assertViewHas('totalMembers', 3)->assertViewHas('activeMembers', 1)
        ->assertViewHas('pendingTransactions', 1)->assertViewHas('totalCollected', 1250)
        ->assertViewHas('newMembersThisMonth', 3)->assertSee('Avery')->assertSee('₱1,250.00');
});

test('filters members by status category level and search while retaining filters on pagination', function (): void {
    $user = User::factory()->create();
    $category = MembershipCategory::factory()->create();
    $level = MembershipLevel::factory()->create();
    Member::factory()->active()->count(11)->for($category, 'category')->for($level, 'level')
        ->create(['first_name' => 'Match']);
    $excluded = Member::factory()->inactive()->for($category, 'category')->for($level, 'level')->create();

    $this->actingAs($user)->get(route('members.index', ['status' => 'Active', 'category' => $category->id, 'level' => $level->id, 'search' => 'Match']))
        ->assertOk()->assertViewHas('members', fn ($members): bool => $members->total() === 11)
        ->assertSee('Match')->assertDontSee($excluded->email)
        ->assertSee('status=Active', false)->assertSee('page=2', false);
});

test('filters transactions using a status and member name', function (): void {
    $user = User::factory()->create();
    $member = Member::factory()->create(['first_name' => 'Jordan']);
    $matching = MembershipTransaction::factory()->for($member)->paid()->create();
    $excluded = MembershipTransaction::factory()->for($member)->create();

    $this->actingAs($user)->get('/transactions?search=Jordan&status=Paid')->assertOk()
        ->assertSee($matching->reference_number)->assertDontSee($excluded->reference_number);
});

test('renders populated catalogues profile forms receipts and payment screens', function (): void {
    $user = User::factory()->create();
    $member = Member::factory()->create();
    $transaction = MembershipTransaction::factory()->for($member)->paid()->create();
    Payment::factory()->for($transaction, 'transaction')->create();

    $this->actingAs($user);
    $this->get('/categories')->assertOk()->assertSee($member->category->name)->assertSee('1 member');
    $this->get('/levels')->assertOk()->assertSee($member->level->name)->assertSee('1 member');
    $this->get(route('members.show', $member))->assertOk()->assertSee($member->email)->assertSee($transaction->reference_number);
    $this->get(route('members.edit', $member))->assertOk()->assertSee($member->email);
    $this->get('/transactions/create')->assertOk()->assertSee($member->membership_number);
    $this->get(route('transactions.show', $transaction))->assertOk()->assertSee('PAYMENT RECEIPT')->assertSee($transaction->payment->payment_reference);
    $unpaidTransaction = MembershipTransaction::factory()->for($member)->create();
    $this->get(route('payments.create', $unpaidTransaction))->assertOk()->assertSee($unpaidTransaction->reference_number);
});

test('catalogue forms preserve invalid input for the correct editor', function (string $path, string $editor, array $payload, array $errors): void {
    $user = User::factory()->create();

    $this->actingAs($user)->from($path)->post($path, ['_editor' => $editor, ...$payload])
        ->assertRedirect($path)->assertSessionHasErrors($errors)->assertSessionHasInput('_editor', $editor);
    $this->get($path)->assertOk()->assertSee('data-reopen="true"', false)->assertSee('Invalid example');
})->with([
    'category' => ['/categories', 'category', ['name' => 'Invalid example', 'discount_percent' => 101], ['discount_percent']],
    'level' => ['/levels', 'level', ['name' => 'Invalid example', 'fee' => -1, 'validity_days' => 0], ['fee', 'validity_days']],
]);

test('escapes member and catalogue content in the workspace', function (): void {
    $user = User::factory()->create();
    $category = MembershipCategory::factory()->create(['name' => '<script>alert(1)</script>']);
    $member = Member::factory()->for($category, 'category')->create(['first_name' => '<img src=x onerror=alert(1)>']);

    $this->actingAs($user)->get('/members')->assertOk()->assertSee($member->first_name)->assertDontSee($member->first_name, false);
    $this->get('/categories')->assertOk()->assertSee($category->name)->assertDontSee($category->name, false);
});

test('completes registration payment approval and automatic expiration with the existing notifications', function (): void {
    $this->freezeTime();
    Notification::fake();
    $user = User::factory()->create();
    $category = MembershipCategory::factory()->create(['discount_percent' => 20]);
    $level = MembershipLevel::factory()->create(['fee' => 1000, 'validity_days' => 30]);
    $this->actingAs($user);

    $this->post('/members', [
        'first_name' => 'Avery', 'last_name' => 'Santos', 'email' => 'avery@example.test',
        'phone' => '09123456789', 'membership_category_id' => $category->id, 'membership_level_id' => $level->id,
    ])->assertRedirectToRoute('members.index');
    $member = Member::firstOrFail();
    $this->assertDatabaseHas('members', ['id' => $member->id, 'status' => 'Pending']);

    $this->post('/transactions', ['member_id' => $member->id, 'membership_level_id' => $level->id, 'type' => 'Application'])
        ->assertRedirectToRoute('transactions.index');
    $transaction = MembershipTransaction::firstOrFail();
    $this->assertDatabaseHas('membership_transactions', ['id' => $transaction->id, 'amount' => 800, 'status' => 'Pending']);

    $this->post(route('payments.store', $transaction), ['amount_paid' => 800, 'payment_method' => 'GCash'])
        ->assertRedirectToRoute('transactions.index');
    $this->assertDatabaseHas('payments', ['membership_transaction_id' => $transaction->id, 'amount_paid' => 800]);
    $this->post(route('transactions.approve', $transaction))->assertRedirect();
    $this->assertDatabaseHas('members', ['id' => $member->id, 'status' => 'Active']);
    $this->assertDatabaseHas('membership_transactions', ['id' => $transaction->id, 'status' => 'Approved']);
    $this->travel(30)->days();
    $this->artisan('memberships:expire')->expectsOutput('Expired 1 membership(s) and queued expiration emails.')->assertExitCode(0);
    $this->assertDatabaseHas('members', ['id' => $member->id, 'status' => 'Inactive']);
    $this->assertDatabaseHas('membership_transactions', ['id' => $transaction->id, 'status' => 'Expired']);
    Notification::assertSentOnDemand(MemberRegistered::class);
    Notification::assertSentOnDemand(MembershipTransactionSubmitted::class);
    Notification::assertSentOnDemand(MembershipTransactionApproved::class);
    Notification::assertSentOnDemand(MembershipTransactionStatusUpdated::class);
});
