<?php

namespace App\Notifications;

use App\Models\MembershipTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipExpiringReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [60, 300];

    private array $mailData;

    public function __construct(
        public MembershipTransaction $transaction,
        public string $expiryDate,
        public int $daysLeft
    ) {
        $this->mailData = MembershipEmailData::forTransaction($transaction);
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function shouldSend(object $notifiable, string $channel): bool
    {
        $transaction = MembershipTransaction::with(['member.level', 'member.category', 'payment'])->find($this->transaction->id);

        if (! $transaction || $transaction->status !== 'Approved' || $transaction->member->status !== 'Active') {
            return false;
        }

        $latestTransactionId = $transaction->member->transactions()->where('status', 'Approved')
            ->orderByDesc('approved_at')->orderByDesc('id')->value('id');
        $expiresAt = MembershipEmailData::expiresAt($transaction);
        $daysLeft = (int) now(config('mail.membership_timezone'))->startOfDay()->diffInDays($expiresAt->copy()->startOfDay(), false);

        if ($latestTransactionId !== $transaction->id || $expiresAt->lessThanOrEqualTo(now()) || $daysLeft < 0 || $expiresAt->format('F j, Y') !== $this->expiryDate) {
            return false;
        }

        $this->mailData = MembershipEmailData::forTransaction($transaction);
        $this->expiryDate = $expiresAt->format('F j, Y');
        $this->daysLeft = $daysLeft;

        return true;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $remaining = $this->daysLeft === 0 ? 'today' : 'in '.$this->daysLeft.' '.($this->daysLeft === 1 ? 'day' : 'days');

        return (new MailMessage)
            ->subject('SharpMind | '.$this->mailData['levelName'].' membership expires '.$remaining)
            ->view(['emails.expiration-reminder', 'emails.text'], $this->mailData + [
                'expiryDate' => $this->expiryDate,
                'daysLeft' => $this->daysLeft,
                'heading' => 'Your membership expires '.$remaining.'.',
                'summary' => 'Renew with your membership administrator before your expiration date to keep your membership active.',
            ]);
    }
}
