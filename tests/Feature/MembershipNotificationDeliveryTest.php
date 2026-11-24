<?php

use App\Models\Member;
use App\Models\MembershipCategory;
use App\Models\MembershipLevel;
use App\Models\MembershipTransaction;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\MemberRegistered;
use App\Notifications\MembershipExpiringReminder;
use App\Notifications\MembershipTransactionApproved;
use App\Notifications\MembershipTransactionStatusUpdated;
use App\Notifications\MembershipTransactionSubmitted;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Notification as NotificationContract;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

uses(LazilyRefreshDatabase::class);

test('each member email reaches the mail channel with HTML and plain text', function (Closure $makeNotification): void {
    $this->travelTo('2026-11-24 00:00:00');
    config(['mail.default' => 'array']);
    $level = MembershipLevel::factory()->create(['name' => 'Basic', 'fee' => 500, 'validity_days' => 30, 'benefits' => "Course access\nCommunity forums\nStudy guides"]);
    $member = Member::factory()->active()->for($level, 'level')->create();
    $transaction = MembershipTransaction::factory()->for($member)->approved()->create(['amount' => 400, 'created_at' => now()->subDays(23), 'updated_at' => now()->subDays(23)]);
    Payment::factory()->for($transaction, 'transaction')->create(['amount_paid' => 450]);
    $notification = $makeNotification($member, $transaction);

    Notification::sendNow((new AnonymousNotifiable)->route('mail', $member->email), $notification);

    $messages = Mail::mailer()->getSymfonyTransport()->messages();
    expect($messages)->toHaveCount(1);
    $email = $messages->first()->getOriginalMessage();
    expect($email->getTo()[0]->getAddress())->toBe($member->email);
    expect($email->getSubject())->toStartWith('SharpMind |');
    expect($email->getHtmlBody())->toContain('Sharp', $member->membership_number, 'font-size:16px')->not->toContain('laravel.com', '<script');
    expect($email->getTextBody())->toContain('SharpMind', $member->membership_number);
})->with([
    'registration' => [fn (Member $member, MembershipTransaction $transaction): NotificationContract => new MemberRegistered($member)],
    'transaction received' => [fn (Member $member, MembershipTransaction $transaction): NotificationContract => new MembershipTransactionSubmitted($transaction)],
    'approved' => [fn (Member $member, MembershipTransaction $transaction): NotificationContract => new MembershipTransactionApproved($transaction)],
    'expiring reminder' => [fn (Member $member, MembershipTransaction $transaction): NotificationContract => new MembershipExpiringReminder($transaction, 'December 1, 2026', 7)],
    'cancelled' => [function (Member $member, MembershipTransaction $transaction): NotificationContract {
        $transaction->status = 'Cancelled';

        return new MembershipTransactionStatusUpdated($transaction, 'Cancelled by your administrator.');
    }],
    'expired' => [function (Member $member, MembershipTransaction $transaction): NotificationContract {
        $transaction->status = 'Expired';
        $member->status = 'Inactive';

        return new MembershipTransactionStatusUpdated($transaction, 'Your membership has expired.');
    }],
]);

test('member notifications are queued to the intended recipient after commit with retries', function (): void {
    $this->freezeTime();
    Queue::fake([SendQueuedNotifications::class]);
    $member = Member::factory()->create();
    $transaction = MembershipTransaction::factory()->for($member)->create();
    $notifications = [
        new MemberRegistered($member), new MembershipTransactionSubmitted($transaction),
        new MembershipTransactionApproved($transaction), new MembershipExpiringReminder($transaction, 'December 1, 2026', 7),
        new MembershipTransactionStatusUpdated($transaction, 'Transaction updated.'),
    ];

    foreach ($notifications as $notification) {
        Notification::route('mail', $member->email)->notify($notification);
    }

    Queue::assertPushed(SendQueuedNotifications::class, 5);
    foreach ($notifications as $notification) {
        Queue::assertPushed(SendQueuedNotifications::class, fn (SendQueuedNotifications $job): bool => $notification::class === $job->notification::class
            && $job->notifiables->first()->routes['mail'] === $member->email
            && $job->channels === ['mail'] && $job->afterCommit === true
            && $job->tries === 3 && $job->backoff() === [60, 300]
        );
    }
});

test('rolled back membership changes do not send an email', function (): void {
    config(['queue.default' => 'sync', 'mail.default' => 'array']);
    $member = Member::factory()->create(['first_name' => 'Avery']);

    expect(function () use ($member): void {
        DB::transaction(function () use ($member): void {
            $member->update(['first_name' => 'Rolled back']);
            Notification::route('mail', $member->email)->notify(new MemberRegistered($member));
            throw new RuntimeException('Registration rolled back.');
        });
    })->toThrow(RuntimeException::class, 'Registration rolled back.');

    $this->assertDatabaseHas('members', ['id' => $member->id, 'first_name' => 'Avery']);
    expect(Mail::mailer()->getSymfonyTransport()->messages())->toHaveCount(0);
});

test('committed registration sends through the queue only after the transaction commits', function (): void {
    config(['queue.default' => 'sync', 'mail.default' => 'array']);
    $member = Member::factory()->create(['first_name' => 'Avery']);

    DB::transaction(function () use ($member): void {
        Notification::route('mail', $member->email)->notify(new MemberRegistered($member));
        expect(Mail::mailer()->getSymfonyTransport()->messages())->toHaveCount(0);
    });

    $messages = Mail::mailer()->getSymfonyTransport()->messages();
    expect($messages)->toHaveCount(1);
    expect($messages->sole()->getOriginalMessage()->getTextBody())->toContain('Hello Avery', $member->membership_number);
});

test('queued transaction emails retain the event details when records change before delivery', function (): void {
    $this->freezeTime();
    config(['mail.default' => 'array']);
    $level = MembershipLevel::factory()->create(['name' => 'Basic', 'fee' => 500]);
    $member = Member::factory()->for($level, 'level')->create(['first_name' => 'Avery', 'last_name' => 'Santos']);
    $transaction = MembershipTransaction::factory()->for($member)->create(['amount' => 400]);
    $notification = new MembershipTransactionSubmitted($transaction);
    $member->update(['status' => 'Active']);
    $transaction->update(['status' => 'Approved', 'amount' => 900]);
    $level->update(['name' => 'Platinum']);
    $restored = unserialize(serialize($notification));

    Notification::sendNow((new AnonymousNotifiable)->route('mail', $member->email), $restored);

    $plain = Mail::mailer()->getSymfonyTransport()->messages()->sole()->getOriginalMessage()->getTextBody();
    expect($plain)->toContain('Transaction status: Pending', 'Membership level: Basic', 'Transaction amount: ₱400.00', 'Membership status: Pending')
        ->not->toContain('Transaction status: Approved', 'Membership level: Platinum', '₱900.00');
});

test('approval email shows the recorded payment and benefits individually without rendering member HTML', function (): void {
    $this->travelTo('2026-11-23 17:00:00');
    config(['mail.default' => 'array']);
    $markup = '<img src=x onerror=alert(1)>';
    $level = MembershipLevel::factory()->create(['name' => $markup, 'validity_days' => 30, 'benefits' => "Course access\n".$markup]);
    $category = MembershipCategory::factory()->create(['name' => $markup]);
    $member = Member::factory()->active()->for($level, 'level')->for($category, 'category')->create(['first_name' => "O'Reilly", 'last_name' => $markup]);
    $transaction = MembershipTransaction::factory()->for($member)->approved()->create(['amount' => 400]);
    Payment::factory()->for($transaction, 'transaction')->create(['amount_paid' => 450, 'payment_method' => $markup]);

    Notification::sendNow((new AnonymousNotifiable)->route('mail', $member->email), new MembershipTransactionApproved($transaction));

    $email = Mail::mailer()->getSymfonyTransport()->messages()->sole()->getOriginalMessage();
    expect($email->getHtmlBody())->toContain(e($markup), '₱450.00', 'December 24, 2026')->not->toContain($markup);
    expect($email->getTextBody())->toContain("Hello O'Reilly", 'Amount paid: ₱450.00', "- Course access\n- ".$markup);
    $document = new DOMDocument;
    $document->loadHTML($email->getHtmlBody(), LIBXML_NOERROR | LIBXML_NOWARNING);
    expect($document->getElementsByTagName('li')->length)->toBe(2);
});

test('registration email contains the fee breakdown', function (): void {
    config(['mail.default' => 'array']);
    $category = MembershipCategory::factory()->create(['name' => 'Student', 'discount_percent' => 20]);
    $level = MembershipLevel::factory()->create(['fee' => 500]);
    $member = Member::factory()->for($category, 'category')->for($level, 'level')->create();

    Notification::sendNow((new AnonymousNotifiable)->route('mail', $member->email), new MemberRegistered($member));

    $plain = Mail::mailer()->getSymfonyTransport()->messages()->sole()->getOriginalMessage()->getTextBody();
    expect($plain)->toContain('Base membership fee: ₱500.00', 'Student discount (20%): −₱100.00', 'Total payable: ₱400.00');
});

test('cancelling a transaction queues one status email to its member and rejects a repeat', function (string $status): void {
    Notification::fake([MembershipTransactionStatusUpdated::class]);
    $user = User::factory()->create();
    $member = Member::factory()->active()->create();
    $transaction = MembershipTransaction::factory()->for($member)->create(['status' => $status]);

    $this->actingAs($user)->post(route('transactions.cancel', $transaction))->assertRedirect()->assertSessionHas('success');

    $this->assertDatabaseHas('membership_transactions', ['id' => $transaction->id, 'status' => 'Cancelled']);
    $this->assertDatabaseHas('members', ['id' => $member->id, 'status' => 'Active']);
    Notification::assertSentOnDemand(MembershipTransactionStatusUpdated::class, fn ($notification, array $channels, object $recipient): bool => $recipient->routes['mail'] === $member->email && $notification->transaction->status === 'Cancelled'
    );
    $this->post(route('transactions.cancel', $transaction))->assertRedirect()->assertSessionHas('error', 'Only pending, paid, or approved transactions can be cancelled.');
    Notification::assertSentOnDemandTimes(MembershipTransactionStatusUpdated::class, 1);
})->with(['Pending', 'Paid']);

test('the removed manual expiry endpoint cannot change a transaction or send an email', function (string $status): void {
    Notification::fake();
    $user = User::factory()->create();
    $member = Member::factory()->active()->create();
    $transaction = MembershipTransaction::factory()->for($member)->create(['status' => $status]);

    $this->actingAs($user)->post("/transactions/{$transaction->id}/expire")->assertNotFound();

    $this->assertDatabaseHas('membership_transactions', ['id' => $transaction->id, 'status' => $status]);
    $this->assertDatabaseHas('members', ['id' => $member->id, 'status' => 'Active']);
    Notification::assertNothingSent();
})->with(['Pending', 'Paid', 'Cancelled', 'Expired']);

test('an older approved transaction cannot deactivate a renewed membership or send an expiration email', function (): void {
    $this->freezeTime();
    Notification::fake();
    $user = User::factory()->create();
    $level = MembershipLevel::factory()->create(['validity_days' => 30]);
    $member = Member::factory()->active()->for($level, 'level')->create();
    $older = MembershipTransaction::factory()->for($member)->approved()->create(['updated_at' => now()->subDays(31)]);
    MembershipTransaction::factory()->for($member)->approved()->create(['type' => 'Renewal']);

    $this->artisan('memberships:expire')->expectsOutput('Expired 0 membership(s) and queued expiration emails.')->assertExitCode(0);

    $this->assertDatabaseHas('members', ['id' => $member->id, 'status' => 'Active']);
    $this->assertDatabaseHas('membership_transactions', ['id' => $older->id, 'status' => 'Approved']);
    Notification::assertNothingSent();
});

test('an expired transaction cannot be cancelled and does not send another update', function (): void {
    Notification::fake();
    $user = User::factory()->create();
    $member = Member::factory()->inactive()->create();
    $transaction = MembershipTransaction::factory()->for($member)->create(['status' => 'Expired']);

    $this->actingAs($user)->post(route('transactions.cancel', $transaction))->assertRedirect()->assertSessionHas('error', 'Only pending, paid, or approved transactions can be cancelled.');

    $this->assertDatabaseHas('membership_transactions', ['id' => $transaction->id, 'status' => 'Expired']);
    $this->assertDatabaseHas('members', ['id' => $member->id, 'status' => 'Inactive']);
    Notification::assertNothingSent();
});
