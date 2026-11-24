<?php

use App\Models\Member;
use App\Models\MembershipTransaction;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\MembershipTransactionStatusUpdated;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;

uses(LazilyRefreshDatabase::class);

/** @return list<string> */
function transactionCancellationButtons(TestResponse $response, MembershipTransaction $transaction): array
{
    $document = new DOMDocument;
    $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $buttons = (new DOMXPath($document))->query('//form[@action="'.route('transactions.cancel', $transaction).'"]/button');

    return array_map(fn (DOMNode $button): string => trim($button->textContent), iterator_to_array($buttons));
}

test('cancelling the current approved membership deactivates its member and preserves payment history', function (): void {
    $this->freezeTime();
    Notification::fake([MembershipTransactionStatusUpdated::class]);
    $user = User::factory()->create();
    $member = Member::factory()->active()->create();
    $older = MembershipTransaction::factory()->for($member)->approved()->create(['updated_at' => now()->subDays(20)]);
    $current = MembershipTransaction::factory()->for($member)->approved()->create(['type' => 'Renewal']);
    $payment = Payment::factory()->for($current, 'transaction')->create(['amount_paid' => 1000]);

    $this->actingAs($user)->from(route('transactions.index'))->post(route('transactions.cancel', $current))
        ->assertRedirectToRoute('transactions.index')->assertSessionHas('success');

    $this->assertDatabaseHas('membership_transactions', ['id' => $current->id, 'status' => 'Cancelled']);
    $this->assertDatabaseHas('membership_transactions', ['id' => $older->id, 'status' => 'Approved']);
    $this->assertDatabaseHas('members', ['id' => $member->id, 'status' => 'Inactive']);
    $this->assertDatabaseHas('payments', ['id' => $payment->id, 'membership_transaction_id' => $current->id, 'amount_paid' => 1000]);
    $this->assertDatabaseCount('payments', 1);
    Notification::assertSentOnDemand(MembershipTransactionStatusUpdated::class, function (MembershipTransactionStatusUpdated $notification, array $channels, object $recipient) use ($member, $current): bool {
        $message = $notification->toMail($recipient);
        $plain = view($message->view[1], $message->viewData)->render();

        return $notification->transaction->id === $current->id && $recipient->routes['mail'] === $member->email
            && $channels === ['mail'] && str_contains($plain, 'Your membership has been cancelled.')
            && str_contains($plain, 'Membership status: Inactive') && str_contains($plain, 'Transaction status: Cancelled');
    });
    $this->post(route('transactions.cancel', $current))->assertRedirect()->assertSessionHas('error');
    Notification::assertSentOnDemandTimes(MembershipTransactionStatusUpdated::class, 1);
});

test('membership cancellation reaches the mail channel after saving the cancelled and inactive statuses', function (): void {
    config(['queue.default' => 'sync', 'mail.default' => 'array']);
    $user = User::factory()->create();
    $member = Member::factory()->active()->create();
    $transaction = MembershipTransaction::factory()->for($member)->approved()->create();
    Payment::factory()->for($transaction, 'transaction')->create(['amount_paid' => 1000]);

    $this->actingAs($user)->post(route('transactions.cancel', $transaction))->assertRedirect()->assertSessionHas('success');

    $messages = Mail::mailer()->getSymfonyTransport()->messages();
    expect($messages)->toHaveCount(1);
    $email = $messages->sole()->getOriginalMessage();
    expect($email->getSubject())->toBe('SharpMind | Membership Cancelled - '.$transaction->reference_number);
    expect($email->getTo()[0]->getAddress())->toBe($member->email);
    expect($email->getHtmlBody())->toContain('Membership cancelled', 'Inactive');
    expect($email->getTextBody())->toContain('Your membership has been cancelled.', 'Membership status: Inactive', 'Transaction status: Cancelled');
    $this->assertDatabaseHas('members', ['id' => $member->id, 'status' => 'Inactive']);
    $this->assertDatabaseHas('membership_transactions', ['id' => $transaction->id, 'status' => 'Cancelled']);
});

test('pending transaction cancellation keeps a non-active member status unchanged', function (string $memberStatus): void {
    Notification::fake([MembershipTransactionStatusUpdated::class]);
    $user = User::factory()->create();
    $member = Member::factory()->create(['status' => $memberStatus]);
    $transaction = MembershipTransaction::factory()->for($member)->create();

    $this->actingAs($user)->post(route('transactions.cancel', $transaction))->assertRedirect()->assertSessionHas('success');

    $this->assertDatabaseHas('members', ['id' => $member->id, 'status' => $memberStatus]);
    $this->assertDatabaseHas('membership_transactions', ['id' => $transaction->id, 'status' => 'Cancelled']);
    Notification::assertSentOnDemand(MembershipTransactionStatusUpdated::class, fn (MembershipTransactionStatusUpdated $notification, array $channels, object $recipient): bool => $recipient->routes['mail'] === $member->email && $notification->transaction->id === $transaction->id
    );
})->with(['Pending', 'Inactive']);

test('an older approved transaction cannot cancel a newer active renewal', function (): void {
    $this->freezeTime();
    Notification::fake();
    $user = User::factory()->create();
    $member = Member::factory()->active()->create();
    $older = MembershipTransaction::factory()->for($member)->approved()->create(['updated_at' => now()->subDays(20)]);
    $renewal = MembershipTransaction::factory()->for($member)->approved()->create(['type' => 'Renewal']);

    $this->actingAs($user)->post(route('transactions.cancel', $older))->assertRedirect()
        ->assertSessionHas('error', 'Only the current active membership can be cancelled.');

    $this->assertDatabaseHas('members', ['id' => $member->id, 'status' => 'Active']);
    $this->assertDatabaseHas('membership_transactions', ['id' => $older->id, 'status' => 'Approved']);
    $this->assertDatabaseHas('membership_transactions', ['id' => $renewal->id, 'status' => 'Approved']);
    Notification::assertNothingSent();
});

test('a pending renewal does not prevent cancellation of the current approved membership', function (): void {
    $this->freezeTime();
    Notification::fake([MembershipTransactionStatusUpdated::class]);
    $user = User::factory()->create();
    $member = Member::factory()->active()->create();
    $approved = MembershipTransaction::factory()->for($member)->approved()->create(['updated_at' => now()->subDays(20)]);
    $pending = MembershipTransaction::factory()->for($member)->create(['type' => 'Renewal']);

    $this->actingAs($user)->post(route('transactions.cancel', $approved))->assertRedirect()->assertSessionHas('success');

    $this->assertDatabaseHas('members', ['id' => $member->id, 'status' => 'Inactive']);
    $this->assertDatabaseHas('membership_transactions', ['id' => $approved->id, 'status' => 'Cancelled']);
    $this->assertDatabaseHas('membership_transactions', ['id' => $pending->id, 'status' => 'Pending']);
    Notification::assertSentOnDemand(MembershipTransactionStatusUpdated::class, fn (MembershipTransactionStatusUpdated $notification): bool => $notification->transaction->id === $approved->id);
});

test('an inactive membership cannot be cancelled again through an approved historical record', function (): void {
    Notification::fake();
    $user = User::factory()->create();
    $member = Member::factory()->inactive()->create();
    $transaction = MembershipTransaction::factory()->for($member)->approved()->create();

    $this->actingAs($user)->post(route('transactions.cancel', $transaction))->assertRedirect()
        ->assertSessionHas('error', 'Only the current active membership can be cancelled.');

    $this->assertDatabaseHas('members', ['id' => $member->id, 'status' => 'Inactive']);
    $this->assertDatabaseHas('membership_transactions', ['id' => $transaction->id, 'status' => 'Approved']);
    Notification::assertNothingSent();
});

test('current membership cancellation is based on approval time rather than transaction creation order', function (): void {
    $this->freezeTime();
    Notification::fake([MembershipTransactionStatusUpdated::class]);
    $user = User::factory()->create();
    $member = Member::factory()->active()->create();
    $current = MembershipTransaction::factory()->for($member)->approved()->create();
    $higherId = MembershipTransaction::factory()->for($member)->approved()->create(['updated_at' => now()->subDays(10)]);

    $this->actingAs($user)->post(route('transactions.cancel', $current))->assertRedirect()->assertSessionHas('success');

    $this->assertDatabaseHas('membership_transactions', ['id' => $current->id, 'status' => 'Cancelled']);
    $this->assertDatabaseHas('membership_transactions', ['id' => $higherId->id, 'status' => 'Approved']);
    $this->assertDatabaseHas('members', ['id' => $member->id, 'status' => 'Inactive']);
    Notification::assertSentOnDemand(MembershipTransactionStatusUpdated::class, fn (MembershipTransactionStatusUpdated $notification): bool => $notification->transaction->id === $current->id);
});

test('equal approval timestamps use the newer transaction when protecting membership cancellation', function (): void {
    $this->freezeTime();
    Notification::fake();
    $user = User::factory()->create();
    $member = Member::factory()->active()->create();
    $older = MembershipTransaction::factory()->for($member)->approved()->create();
    $newer = MembershipTransaction::factory()->for($member)->approved()->create(['type' => 'Renewal']);

    $this->actingAs($user)->post(route('transactions.cancel', $older))->assertRedirect()->assertSessionHas('error');

    $this->assertDatabaseHas('members', ['id' => $member->id, 'status' => 'Active']);
    $this->assertDatabaseHas('membership_transactions', ['id' => $older->id, 'status' => 'Approved']);
    $this->assertDatabaseHas('membership_transactions', ['id' => $newer->id, 'status' => 'Approved']);
    Notification::assertNothingSent();
});

test('guests cannot cancel memberships', function (): void {
    Notification::fake();
    $member = Member::factory()->active()->create();
    $transaction = MembershipTransaction::factory()->for($member)->approved()->create();

    $this->post(route('transactions.cancel', $transaction))->assertRedirectToRoute('login');

    $this->assertDatabaseHas('members', ['id' => $member->id, 'status' => 'Active']);
    $this->assertDatabaseHas('membership_transactions', ['id' => $transaction->id, 'status' => 'Approved']);
    Notification::assertNothingSent();
});

test('cancellation is available in transaction details and stays out of the transaction list', function (string $status, string $detailLabel): void {
    $user = User::factory()->create();
    $member = Member::factory()->active()->create();
    $transaction = MembershipTransaction::factory()->for($member)->create(['status' => $status]);

    $list = $this->actingAs($user)->get(route('transactions.index'))->assertOk();
    $detail = $this->get(route('transactions.show', $transaction))->assertOk();

    expect(transactionCancellationButtons($list, $transaction))->toBe([]);
    expect(transactionCancellationButtons($detail, $transaction))->toBe([$detailLabel]);
})->with([
    'pending' => ['Pending', 'Cancel transaction'],
    'paid' => ['Paid', 'Cancel transaction'],
    'active membership' => ['Approved', 'Cancel membership'],
]);

test('closed transactions and inactive approved memberships do not expose a cancel action', function (string $status): void {
    $user = User::factory()->create();
    $member = Member::factory()->inactive()->create();
    $transaction = MembershipTransaction::factory()->for($member)->create(['status' => $status]);

    $list = $this->actingAs($user)->get(route('transactions.index'))->assertOk();
    $detail = $this->get(route('transactions.show', $transaction))->assertOk();

    expect(transactionCancellationButtons($list, $transaction))->toBe([]);
    expect(transactionCancellationButtons($detail, $transaction))->toBe([]);
})->with(['Cancelled', 'Expired', 'Approved']);

test('an older approved transaction does not expose cancellation while a newer renewal is active', function (): void {
    $this->freezeTime();
    $user = User::factory()->create();
    $member = Member::factory()->active()->create();
    $older = MembershipTransaction::factory()->for($member)->approved()->create(['updated_at' => now()->subDays(20)]);
    $newer = MembershipTransaction::factory()->for($member)->approved()->create(['type' => 'Renewal']);

    $list = $this->actingAs($user)->get(route('transactions.index'))->assertOk();
    $detail = $this->get(route('transactions.show', $older))->assertOk();

    expect(transactionCancellationButtons($list, $older))->toBe([]);
    expect(transactionCancellationButtons($list, $newer))->toBe([]);
    expect(transactionCancellationButtons($detail, $older))->toBe([]);
});

test('paid or closed transactions cannot accept another payment', function (string $status): void {
    Notification::fake();
    $user = User::factory()->create();
    $member = Member::factory()->inactive()->create();
    $transaction = MembershipTransaction::factory()->for($member)->create(['status' => $status, 'amount' => 1000]);
    $payment = Payment::factory()->for($transaction, 'transaction')->create(['amount_paid' => 1000]);
    $this->actingAs($user);

    $this->get(route('payments.create', $transaction))->assertRedirectToRoute('transactions.show', $transaction)
        ->assertSessionHas('error', 'Only pending transactions without a payment can receive payment.');
    $this->post(route('payments.store', $transaction), ['amount_paid' => 1000, 'payment_method' => 'Cash'])
        ->assertRedirectToRoute('transactions.show', $transaction)->assertSessionHas('error');

    $this->assertDatabaseCount('payments', 1);
    $this->assertDatabaseHas('payments', ['id' => $payment->id, 'amount_paid' => 1000]);
    $this->assertDatabaseHas('membership_transactions', ['id' => $transaction->id, 'status' => $status]);
    $this->assertDatabaseHas('members', ['id' => $member->id, 'status' => 'Inactive']);
    Notification::assertNothingSent();
})->with(['Paid', 'Approved', 'Cancelled', 'Expired']);

test('a pending transaction with an existing payment cannot record a duplicate payment', function (): void {
    $user = User::factory()->create();
    $transaction = MembershipTransaction::factory()->create(['amount' => 1000]);
    $payment = Payment::factory()->for($transaction, 'transaction')->create(['amount_paid' => 1000]);

    $this->actingAs($user)->post(route('payments.store', $transaction), ['amount_paid' => 1000, 'payment_method' => 'Cash'])
        ->assertRedirectToRoute('transactions.show', $transaction)->assertSessionHas('error');

    $this->assertDatabaseCount('payments', 1);
    $this->assertDatabaseHas('payments', ['id' => $payment->id, 'amount_paid' => 1000]);
    $this->assertDatabaseHas('membership_transactions', ['id' => $transaction->id, 'status' => 'Pending']);
});

test('an invalid payment rolls back without changing the transaction', function (): void {
    Notification::fake();
    $user = User::factory()->create();
    $transaction = MembershipTransaction::factory()->create(['amount' => 1000]);

    $this->actingAs($user)->post(route('payments.store', $transaction), ['amount_paid' => 999, 'payment_method' => 'Cash'])
        ->assertRedirect()->assertSessionHasErrors(['amount_paid']);

    $this->assertDatabaseCount('payments', 0);
    $this->assertDatabaseHas('membership_transactions', ['id' => $transaction->id, 'status' => 'Pending']);
    Notification::assertNothingSent();
});

test('a cancelled transaction cannot be approved to reactivate its membership', function (): void {
    Notification::fake();
    $user = User::factory()->create();
    $member = Member::factory()->inactive()->create();
    $transaction = MembershipTransaction::factory()->for($member)->create(['status' => 'Cancelled']);

    $this->actingAs($user)->post(route('transactions.approve', $transaction))->assertRedirect()
        ->assertSessionHas('error', 'Only paid transactions can be approved.');

    $this->assertDatabaseHas('membership_transactions', ['id' => $transaction->id, 'status' => 'Cancelled']);
    $this->assertDatabaseHas('members', ['id' => $member->id, 'status' => 'Inactive']);
    Notification::assertNothingSent();
});
