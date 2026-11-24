<?php

use App\Models\Member;
use App\Models\MembershipLevel;
use App\Models\MembershipTransaction;
use App\Notifications\MembershipExpiringReminder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

uses(LazilyRefreshDatabase::class);

test('expiry reminders include the correct date and recipient at the Manila day boundary', function (string $approvedAt, int $daysLeft, string $expiryDate): void {
    $this->travelTo(Carbon::parse('2026-11-23 16:30:00', 'UTC'));
    Notification::fake([MembershipExpiringReminder::class]);
    $level = MembershipLevel::factory()->create(['validity_days' => 30]);
    $member = Member::factory()->active()->for($level, 'level')->create();
    $transaction = MembershipTransaction::factory()->for($member)->approved()->create(['created_at' => $approvedAt, 'updated_at' => $approvedAt]);

    $this->artisan('memberships:send-expiry-reminders')->expectsOutput('Queued 1 expiring membership reminder(s).')->assertExitCode(0);

    Notification::assertSentOnDemand(MembershipExpiringReminder::class, fn (MembershipExpiringReminder $notification, array $channels, object $recipient): bool => $notification->transaction->id === $transaction->id && $notification->daysLeft === $daysLeft
        && $notification->expiryDate === $expiryDate && $recipient->routes['mail'] === $member->email
        && $channels === ['mail']
    );
})->with([
    'seven days' => ['2026-10-31 16:30:00', 7, 'December 1, 2026'],
    'one day' => ['2026-10-25 16:30:00', 1, 'November 25, 2026'],
    'expires later today' => ['2026-10-24 17:30:00', 0, 'November 24, 2026'],
]);

test('expired memberships and memberships outside the warning window receive no reminder', function (string $approvedAt): void {
    $this->travelTo(Carbon::parse('2026-11-23 16:30:00', 'UTC'));
    Notification::fake();
    $level = MembershipLevel::factory()->create(['validity_days' => 30]);
    $member = Member::factory()->active()->for($level, 'level')->create();
    MembershipTransaction::factory()->for($member)->approved()->create(['created_at' => $approvedAt, 'updated_at' => $approvedAt]);

    $this->artisan('memberships:send-expiry-reminders')->expectsOutput('Queued 0 expiring membership reminder(s).')->assertExitCode(0);

    Notification::assertNothingSent();
})->with([
    'expired yesterday' => ['2026-10-23 16:30:00'],
    'expired earlier today' => ['2026-10-24 16:00:00'],
    'expires exactly now' => ['2026-10-24 16:30:00'],
    'eight days away' => ['2026-11-01 16:30:00'],
]);

test('inactive members do not receive reminders', function (): void {
    $this->freezeTime();
    Notification::fake();
    $level = MembershipLevel::factory()->create(['validity_days' => 30]);
    $member = Member::factory()->inactive()->for($level, 'level')->create();
    MembershipTransaction::factory()->for($member)->approved()->create(['updated_at' => now()->subDays(25)]);

    $this->artisan('memberships:send-expiry-reminders')->expectsOutput('Queued 0 expiring membership reminder(s).')->assertExitCode(0);

    Notification::assertNothingSent();
});

test('unapproved transactions do not generate membership reminders', function (): void {
    $this->freezeTime();
    Notification::fake();
    $level = MembershipLevel::factory()->create(['validity_days' => 30]);
    $member = Member::factory()->active()->for($level, 'level')->create();
    MembershipTransaction::factory()->for($member)->paid()->create(['updated_at' => now()->subDays(25)]);

    $this->artisan('memberships:send-expiry-reminders')->expectsOutput('Queued 0 expiring membership reminder(s).')->assertExitCode(0);

    Notification::assertNothingSent();
});

test('a renewed membership does not get a reminder for its older approval', function (): void {
    $this->freezeTime();
    Notification::fake();
    $level = MembershipLevel::factory()->create(['validity_days' => 30]);
    $member = Member::factory()->active()->for($level, 'level')->create();
    MembershipTransaction::factory()->for($member)->approved()->create(['updated_at' => now()->subDays(25)]);
    MembershipTransaction::factory()->for($member)->approved()->create(['type' => 'Renewal']);

    $this->artisan('memberships:send-expiry-reminders')->expectsOutput('Queued 0 expiring membership reminder(s).')->assertExitCode(0);

    Notification::assertNothingSent();
});

test('a pending renewal does not suppress the current active membership reminder', function (): void {
    $this->freezeTime();
    Notification::fake([MembershipExpiringReminder::class]);
    $level = MembershipLevel::factory()->create(['validity_days' => 30]);
    $member = Member::factory()->active()->for($level, 'level')->create();
    $approved = MembershipTransaction::factory()->for($member)->approved()->create(['updated_at' => now()->subDays(25)]);
    MembershipTransaction::factory()->for($member)->create(['type' => 'Renewal']);

    $this->artisan('memberships:send-expiry-reminders')->expectsOutput('Queued 1 expiring membership reminder(s).')->assertExitCode(0);

    Notification::assertSentOnDemand(MembershipExpiringReminder::class, fn (MembershipExpiringReminder $notification): bool => $notification->transaction->id === $approved->id);
});

test('repeated runs queue one reminder per membership per Manila day and allow the next day', function (): void {
    $this->travelTo(Carbon::parse('2026-11-24 00:00:00', 'UTC'));
    Notification::fake([MembershipExpiringReminder::class]);
    $level = MembershipLevel::factory()->create(['validity_days' => 30]);
    $member = Member::factory()->active()->for($level, 'level')->create();
    MembershipTransaction::factory()->for($member)->approved()->create(['updated_at' => now()->subDays(25)]);

    $this->artisan('memberships:send-expiry-reminders')->expectsOutput('Queued 1 expiring membership reminder(s).')->assertExitCode(0);
    $this->artisan('memberships:send-expiry-reminders')->expectsOutput('Queued 0 expiring membership reminder(s).')->assertExitCode(0);
    Notification::assertSentOnDemandTimes(MembershipExpiringReminder::class, 1);
    $this->travel(1)->days();
    $this->artisan('memberships:send-expiry-reminders')->expectsOutput('Queued 1 expiring membership reminder(s).')->assertExitCode(0);
    Notification::assertSentOnDemandTimes(MembershipExpiringReminder::class, 2);
});

test('invalid reminder windows are rejected without sending notifications', function (string $days): void {
    Notification::fake();

    $this->artisan('memberships:send-expiry-reminders', ['--days' => $days])
        ->expectsOutput('The --days option must be a non-negative integer.')->assertExitCode(2);

    Notification::assertNothingSent();
})->with(['negative' => ['-1'], 'not numeric' => ['hello'], 'fractional' => ['1.5']]);

test('a reminder can be retried after queue dispatch fails', function (): void {
    $this->freezeTime();
    config(['queue.default' => 'missing-test-connection', 'mail.default' => 'array']);
    $level = MembershipLevel::factory()->create(['validity_days' => 30]);
    $member = Member::factory()->active()->for($level, 'level')->create();
    MembershipTransaction::factory()->for($member)->approved()->create(['updated_at' => now()->subDays(25)]);

    expect(fn (): int => Artisan::call('memberships:send-expiry-reminders'))->toThrow(InvalidArgumentException::class);
    Queue::fake([SendQueuedNotifications::class]);
    $this->artisan('memberships:send-expiry-reminders')->expectsOutput('Queued 1 expiring membership reminder(s).')->assertExitCode(0);

    Queue::assertPushed(SendQueuedNotifications::class, 1);
});

test('queued reminders are skipped if the membership changes before delivery', function (Closure $changeMembership): void {
    $this->travelTo('2026-11-24 00:00:00');
    config(['mail.default' => 'array']);
    $level = MembershipLevel::factory()->create(['validity_days' => 30]);
    $member = Member::factory()->active()->for($level, 'level')->create();
    $transaction = MembershipTransaction::factory()->for($member)->approved()->create(['updated_at' => now()->subDays(23)]);
    $reminder = new MembershipExpiringReminder($transaction, 'December 1, 2026', 7);
    $changeMembership($member, $transaction);

    Notification::sendNow((new AnonymousNotifiable)->route('mail', $member->email), $reminder);

    expect(Mail::mailer()->getSymfonyTransport()->messages())->toHaveCount(0);
})->with([
    'renewed' => [fn (Member $member, MembershipTransaction $transaction): MembershipTransaction => MembershipTransaction::factory()->for($member)->approved()->create(['type' => 'Renewal'])],
    'inactive' => [fn (Member $member, MembershipTransaction $transaction): bool => $member->update(['status' => 'Inactive'])],
    'transaction closed' => [fn (Member $member, MembershipTransaction $transaction): bool => $transaction->update(['status' => 'Expired'])],
    'approved expiry corrected' => [fn (Member $member, MembershipTransaction $transaction): bool => $transaction->update(['expires_at' => now()->addDays(37)])],
]);

test('a delayed reminder updates its days remaining before the mail channel sends it', function (): void {
    $this->travelTo('2026-11-24 00:00:00');
    config(['mail.default' => 'array']);
    $level = MembershipLevel::factory()->create(['validity_days' => 30]);
    $member = Member::factory()->active()->for($level, 'level')->create();
    $transaction = MembershipTransaction::factory()->for($member)->approved()->create(['updated_at' => now()->subDays(23)]);
    $reminder = new MembershipExpiringReminder($transaction, 'December 1, 2026', 7);
    $this->travel(1)->days();

    Notification::sendNow((new AnonymousNotifiable)->route('mail', $member->email), $reminder);

    $email = Mail::mailer()->getSymfonyTransport()->messages()->sole()->getOriginalMessage();
    expect($email->getSubject())->toContain('6 days')->not->toContain('7 days');
    expect($email->getTextBody())->toContain('Time remaining: 6 days', 'Expiration date: December 1, 2026');
});

test('a reminder queued earlier on the expiration day is skipped after validity ends', function (): void {
    $this->travelTo(Carbon::parse('2026-11-24 00:00:00', 'UTC'));
    config(['mail.default' => 'array']);
    $level = MembershipLevel::factory()->create(['validity_days' => 30]);
    $member = Member::factory()->active()->for($level, 'level')->create();
    $transaction = MembershipTransaction::factory()->for($member)->approved()->create(['updated_at' => '2026-10-25 01:00:00']);
    $reminder = new MembershipExpiringReminder($transaction, 'November 24, 2026', 0);
    $this->travel(2)->hours();

    Notification::sendNow((new AnonymousNotifiable)->route('mail', $member->email), $reminder);

    expect(Mail::mailer()->getSymfonyTransport()->messages())->toHaveCount(0);
});
