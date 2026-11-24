<?php

namespace App\Console\Commands;

use App\Models\MembershipTransaction;
use App\Notifications\MembershipEmailData;
use App\Notifications\MembershipExpiringReminder;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Throwable;

class SendMembershipExpiryReminders extends Command
{
    protected $signature = 'memberships:send-expiry-reminders {--days=7 : Notify members expiring within this many days}';

    protected $description = 'Queue reminder emails for active members whose membership is expiring soon';

    public function handle(): int
    {
        $warningDays = filter_var($this->option('days'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);

        if ($warningDays === false) {
            $this->error('The --days option must be a non-negative integer.');

            return self::INVALID;
        }

        $today = now(config('mail.membership_timezone'))->startOfDay();
        $approvedTransactions = MembershipTransaction::with(['member.level', 'member.category', 'payment'])
            ->where('status', 'Approved')
            ->whereHas('member', fn (Builder $query): Builder => $query->where('status', 'Active'))
            ->orderByDesc('approved_at')->orderByDesc('id')
            ->get()->unique('member_id');
        $sentCount = 0;

        foreach ($approvedTransactions as $transaction) {
            $expiresAt = MembershipEmailData::expiresAt($transaction);
            $expiryDate = $expiresAt->copy()->startOfDay();
            $daysLeft = (int) $today->diffInDays($expiryDate, false);

            if ($expiresAt->lessThanOrEqualTo(now()) || $daysLeft < 0 || $daysLeft > $warningDays) {
                continue;
            }

            $reminderKey = 'membership-expiry-reminder:'.$transaction->id.':'.$expiryDate->toDateString().':'.$today->toDateString();

            if (! Cache::add($reminderKey, true, $today->copy()->endOfDay())) {
                continue;
            }

            try {
                Notification::route('mail', $transaction->member->email)->notify(new MembershipExpiringReminder(
                    $transaction, $expiryDate->format('F j, Y'), $daysLeft
                ));
            } catch (Throwable $exception) {
                Cache::forget($reminderKey);

                throw $exception;
            }

            $sentCount++;
        }

        $this->info("Queued {$sentCount} expiring membership reminder(s).");

        return self::SUCCESS;
    }
}
