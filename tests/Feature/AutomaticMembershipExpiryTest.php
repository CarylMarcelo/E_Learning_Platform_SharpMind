<?php

use App\Models\Member;
use App\Models\MembershipCategory;
use App\Models\MembershipLevel;
use App\Models\MembershipTransaction;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\MembershipTransactionStatusUpdated;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

uses(LazilyRefreshDatabase::class);

test('validity ending automatically expires the current membership and queues one email while preserving payment', function (string $approvedAt): void {
    $this->travelTo(Carbon::parse('2026-11-24 00:15:00', 'UTC'));
    Notification::fake([MembershipTransactionStatusUpdated::class]);
    $level = MembershipLevel::factory()->create(['validity_days' => 30]);
    $member = Member::factory()->active()->for($level, 'level')->create();
    $transaction = MembershipTransaction::factory()->for($member)->approved()->create(['updated_at' => $approvedAt]);
    $payment = Payment::factory()->for($transaction, 'transaction')->create(['amount_paid' => 400]);

    $this->artisan('memberships:expire')->expectsOutput('Expired 1 membership(s) and queued expiration emails.')->assertExitCode(0);

    $this->assertDatabaseHas('members', ['id' => $member->id, 'status' => 'Inactive']);
    $this->assertDatabaseHas('membership_transactions', ['id' => $transaction->id, 'status' => 'Expired']);
    $this->assertDatabaseHas('payments', ['id' => $payment->id, 'membership_transaction_id' => $transaction->id, 'amount_paid' => 400]);
    Notification::assertSentOnDemand(MembershipTransactionStatusUpdated::class, function (MembershipTransactionStatusUpdated $notification, array $channels, object $recipient) use ($transaction, $member): bool {
        $message = $notification->toMail($recipient);
        $text = view($message->view[1], $message->viewData)->render();

        return $notification->transaction->id === $transaction->id && $recipient->routes['mail'] === $member->email
            && $channels === ['mail'] && str_contains($text, 'Transaction status: Expired')
            && str_contains($text, 'Membership status: Inactive');
    });
    $this->artisan('memberships:expire')->expectsOutput('Expired 0 membership(s) and queued expiration emails.')->assertExitCode(0);
    Notification::assertSentOnDemandTimes(MembershipTransactionStatusUpdated::class, 1);
})->with([
    'exact validity boundary' => ['2026-10-25 00:15:00'],
    'one second overdue' => ['2026-10-25 00:14:59'],
    'overdue during scheduler downtime' => ['2026-10-23 15:30:00'],
]);

test('an active membership remains active until its full validity has elapsed', function (): void {
    $this->travelTo(Carbon::parse('2026-11-24 00:15:00', 'UTC'));
    Notification::fake();
    $level = MembershipLevel::factory()->create(['validity_days' => 30]);
    $member = Member::factory()->active()->for($level, 'level')->create();
    $transaction = MembershipTransaction::factory()->for($member)->approved()->create(['created_at' => '2026-10-01 00:00:00', 'updated_at' => '2026-10-25 00:15:01']);

    $this->artisan('memberships:expire')->expectsOutput('Expired 0 membership(s) and queued expiration emails.')->assertExitCode(0);

    $this->assertDatabaseHas('members', ['id' => $member->id, 'status' => 'Active']);
    $this->assertDatabaseHas('membership_transactions', ['id' => $transaction->id, 'status' => 'Approved']);
    Notification::assertNothingSent();
});

test('unapproved transactions and non-active members are not automatically expired', function (string $transactionStatus, string $memberStatus): void {
    $this->freezeTime();
    Notification::fake();
    $level = MembershipLevel::factory()->create(['validity_days' => 30]);
    $member = Member::factory()->for($level, 'level')->create(['status' => $memberStatus]);
    $transaction = MembershipTransaction::factory()->for($member)->create(['status' => $transactionStatus, 'updated_at' => now()->subDays(31)]);

    $this->artisan('memberships:expire')->expectsOutput('Expired 0 membership(s) and queued expiration emails.')->assertExitCode(0);

    $this->assertDatabaseHas('members', ['id' => $member->id, 'status' => $memberStatus]);
    $this->assertDatabaseHas('membership_transactions', ['id' => $transaction->id, 'status' => $transactionStatus]);
    Notification::assertNothingSent();
})->with([
    'pending transaction' => ['Pending', 'Active'],
    'paid transaction' => ['Paid', 'Active'],
    'cancelled transaction' => ['Cancelled', 'Active'],
    'expired transaction' => ['Expired', 'Active'],
    'inactive member' => ['Approved', 'Inactive'],
    'pending member' => ['Approved', 'Pending'],
]);

test('an unpaid or unapproved renewal does not extend the current membership validity', function (string $renewalStatus): void {
    $this->freezeTime();
    Notification::fake([MembershipTransactionStatusUpdated::class]);
    $level = MembershipLevel::factory()->create(['validity_days' => 30]);
    $member = Member::factory()->active()->for($level, 'level')->create();
    $current = MembershipTransaction::factory()->for($member)->approved()->create(['updated_at' => now()->subDays(31)]);
    $renewal = MembershipTransaction::factory()->for($member)->create(['type' => 'Renewal', 'status' => $renewalStatus]);

    $this->artisan('memberships:expire')->expectsOutput('Expired 1 membership(s) and queued expiration emails.')->assertExitCode(0);

    $this->assertDatabaseHas('members', ['id' => $member->id, 'status' => 'Inactive']);
    $this->assertDatabaseHas('membership_transactions', ['id' => $current->id, 'status' => 'Expired']);
    $this->assertDatabaseHas('membership_transactions', ['id' => $renewal->id, 'status' => $renewalStatus]);
    Notification::assertSentOnDemand(MembershipTransactionStatusUpdated::class, fn (MembershipTransactionStatusUpdated $notification): bool => $notification->transaction->id === $current->id);
})->with(['Pending', 'Paid']);

test('automatic expiry selects the latest approval time rather than the highest transaction id', function (): void {
    $this->freezeTime();
    Notification::fake([MembershipTransactionStatusUpdated::class]);
    $level = MembershipLevel::factory()->create(['validity_days' => 30]);
    $member = Member::factory()->active()->for($level, 'level')->create();
    $current = MembershipTransaction::factory()->for($member)->approved()->create(['updated_at' => now()->subDays(31)]);
    $historical = MembershipTransaction::factory()->for($member)->approved()->create(['updated_at' => now()->subDays(40)]);

    $this->artisan('memberships:expire')->expectsOutput('Expired 1 membership(s) and queued expiration emails.')->assertExitCode(0);

    $this->assertDatabaseHas('members', ['id' => $member->id, 'status' => 'Inactive']);
    $this->assertDatabaseHas('membership_transactions', ['id' => $current->id, 'status' => 'Expired']);
    $this->assertDatabaseHas('membership_transactions', ['id' => $historical->id, 'status' => 'Approved']);
    Notification::assertSentOnDemand(MembershipTransactionStatusUpdated::class, fn (MembershipTransactionStatusUpdated $notification): bool => $notification->transaction->id === $current->id);
});

test('expiry processes every due member across chunks without skipping rows after deactivation', function (): void {
    $this->freezeTime();
    Notification::fake([MembershipTransactionStatusUpdated::class]);
    $level = MembershipLevel::factory()->create(['validity_days' => 30]);
    $category = MembershipCategory::factory()->create();
    $members = Member::factory()->active()->for($level, 'level')->for($category, 'category')->count(101)->create();

    foreach ($members as $member) {
        MembershipTransaction::factory()->for($member)->approved()->create(['updated_at' => now()->subDays(31)]);
    }

    $this->artisan('memberships:expire')->expectsOutput('Expired 101 membership(s) and queued expiration emails.')->assertExitCode(0);

    expect(Member::where('status', 'Active')->count())->toBe(0);
    expect(MembershipTransaction::where('status', 'Expired')->count())->toBe(101);
    Notification::assertSentOnDemandTimes(MembershipTransactionStatusUpdated::class, 101);
});

test('automatic expiration delivers the branded expiry email through the mail channel once', function (): void {
    $this->freezeTime();
    config(['queue.default' => 'sync', 'mail.default' => 'array']);
    $level = MembershipLevel::factory()->create(['validity_days' => 30]);
    $member = Member::factory()->active()->for($level, 'level')->create();
    $transaction = MembershipTransaction::factory()->for($member)->approved()->create(['updated_at' => now()->subDays(31)]);

    $this->artisan('memberships:expire')->assertExitCode(0);
    $this->artisan('memberships:expire')->assertExitCode(0);

    $messages = Mail::mailer()->getSymfonyTransport()->messages();
    expect($messages)->toHaveCount(1);
    $email = $messages->sole()->getOriginalMessage();
    expect($email->getSubject())->toBe('SharpMind | Membership Transaction Expired - '.$transaction->reference_number);
    expect($email->getTo()[0]->getAddress())->toBe($member->email);
    expect($email->getHtmlBody())->toContain('Membership expired', 'Inactive');
    expect($email->getTextBody())->toContain('Transaction status: Expired', 'Membership status: Inactive');
    $this->assertDatabaseHas('members', ['id' => $member->id, 'status' => 'Inactive']);
    $this->assertDatabaseHas('membership_transactions', ['id' => $transaction->id, 'status' => 'Expired']);
});

test('the expiry check is scheduled to run every minute', function (string $time): void {
    $this->travelTo(Carbon::parse($time, 'UTC'));
    $event = collect(app(Schedule::class)->events())->first(fn (Event $event): bool => str_contains($event->command ?? '', 'memberships:expire'));

    expect($event)->not->toBeNull();
    expect($event->isDue(app()))->toBeTrue();
})->with(['2026-11-24 00:00:00', '2026-11-24 00:01:00', '2026-11-24 15:59:00']);

test('approved transaction details retain cancellation and no longer offer manual expiration', function (): void {
    $user = User::factory()->create();
    $member = Member::factory()->active()->create();
    $transaction = MembershipTransaction::factory()->for($member)->approved()->create();

    $this->actingAs($user)->get(route('transactions.show', $transaction))->assertOk()
        ->assertSee('Cancel membership')->assertDontSee('Mark expired')->assertDontSee('/expire', false);
});
