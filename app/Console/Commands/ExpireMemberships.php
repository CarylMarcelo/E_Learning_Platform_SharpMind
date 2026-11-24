<?php

namespace App\Console\Commands;

use App\Models\Member;
use App\Models\MembershipTransaction;
use App\Notifications\MembershipEmailData;
use App\Notifications\MembershipTransactionStatusUpdated;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class ExpireMemberships extends Command
{
    protected $signature = 'memberships:expire';

    protected $description = 'Expire current memberships whose validity has ended and queue expiration emails';

    public function handle(): int
    {
        $expiredCount = 0;
        $members = Member::query()->where('status', 'Active')
            ->with(['level', 'latestApprovedTransaction'])->lazyById(100);

        foreach ($members as $member) {
            $transaction = $member->latestApprovedTransaction;

            if (! $transaction) {
                continue;
            }

            $transaction->setRelation('member', $member);

            if (MembershipEmailData::expiresAt($transaction)->isFuture()) {
                continue;
            }

            if ($this->expireCurrentMembership($transaction->id)) {
                $expiredCount++;
            }
        }

        $this->info("Expired {$expiredCount} membership(s) and queued expiration emails.");

        return self::SUCCESS;
    }

    private function expireCurrentMembership(int $transactionId): bool
    {
        return DB::transaction(function () use ($transactionId): bool {
            $transaction = MembershipTransaction::query()->lockForUpdate()->find($transactionId);

            if (! $transaction || $transaction->status !== 'Approved') {
                return false;
            }

            $member = $transaction->member()->lockForUpdate()->firstOrFail();
            $member->load(['level', 'category', 'latestApprovedTransaction']);
            $transaction->setRelation('member', $member);

            if (! $transaction->isCurrentActiveMembership()) {
                return false;
            }

            $expiresAt = MembershipEmailData::expiresAt($transaction);

            if ($expiresAt->isFuture()) {
                return false;
            }

            $transaction->update(['status' => 'Expired']);
            $member->update(['status' => 'Inactive']);
            $transaction->load('payment');

            Notification::route('mail', $member->email)->notify(new MembershipTransactionStatusUpdated(
                $transaction,
                'Your membership validity ended on '.$expiresAt->format('F j, Y').' and your account is now Inactive. Please submit a renewal transaction to reactivate.'
            ));

            return true;
        }, 3);
    }
}
