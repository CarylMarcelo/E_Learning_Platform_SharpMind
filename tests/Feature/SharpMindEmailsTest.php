<?php

use App\Models\Member;
use App\Models\MembershipCategory;
use App\Models\MembershipLevel;
use App\Models\MembershipTransaction;
use App\Notifications\MemberRegistered;
use App\Notifications\MembershipExpiringReminder;
use App\Notifications\MembershipTransactionApproved;
use App\Notifications\MembershipTransactionStatusUpdated;
use App\Notifications\MembershipTransactionSubmitted;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Notification;

function sharpMindEmailMember(): Member
{
    return Member::factory()->make([
        'membership_category_id' => 1, 'membership_level_id' => 1,
        'first_name' => '<script>alert(1)</script>', 'last_name' => 'Santos',
    ])->setRelation('category', MembershipCategory::factory()->make(['discount_percent' => 20]))
        ->setRelation('level', MembershipLevel::factory()->make());
}

test('renders each notification as branded responsive HTML and readable plain text', function (Closure $createNotification, string $expected): void {
    $this->freezeTime();
    $member = sharpMindEmailMember();
    $transaction = MembershipTransaction::factory()->make(['member_id' => 1, 'updated_at' => now()])->setRelation('member', $member);
    $message = $createNotification($member, $transaction)->toMail(new AnonymousNotifiable);

    $html = view($message->view[0], $message->viewData)->render();
    $plain = view($message->view[1], $message->viewData)->render();

    expect($html)->toContain('Sharp', $expected, 'max-width:600px', 'width="100%"', e($member->first_name))
        ->not->toContain($member->first_name, '<script', '<link', '<style');
    expect($plain)->toContain('SharpMind', $member->membership_number);
    expect($message->subject)->toStartWith('SharpMind |');
})->with([
    'registration' => [fn (Member $member, MembershipTransaction $transaction): Notification => new MemberRegistered($member), 'Your next chapter starts here.'],
    'transaction' => [fn (Member $member, MembershipTransaction $transaction): Notification => new MembershipTransactionSubmitted($transaction), 'Your transaction is in.'],
    'approved' => [fn (Member $member, MembershipTransaction $transaction): Notification => new MembershipTransactionApproved($transaction), 'Let’s grow.'],
    'reminder' => [fn (Member $member, MembershipTransaction $transaction): Notification => new MembershipExpiringReminder($transaction, 'November 30, 2026', 7), '7 days'],
    'expires today' => [fn (Member $member, MembershipTransaction $transaction): Notification => new MembershipExpiringReminder($transaction, 'November 23, 2026', 0), 'Expires today'],
    'expired' => [function (Member $member, MembershipTransaction $transaction): Notification {
        $member->status = 'Inactive';
        $transaction->status = 'Expired';

        return new MembershipTransactionStatusUpdated($transaction, 'Membership expired.');
    }, 'pick up where you left off.'],
    'cancelled' => [function (Member $member, MembershipTransaction $transaction): Notification {
        $transaction->status = 'Cancelled';

        return new MembershipTransactionStatusUpdated($transaction, 'Cancelled by the administrator.');
    }, 'Cancelled by the administrator.'],
]);
