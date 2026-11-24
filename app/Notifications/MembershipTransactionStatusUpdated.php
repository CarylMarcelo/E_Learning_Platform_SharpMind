<?php

namespace App\Notifications;

use App\Models\MembershipTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipTransactionStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [60, 300];

    private array $mailData;

    private bool $membershipCancelled = false;

    public function __construct(public MembershipTransaction $transaction, public string $reason, bool $membershipCancelled = false)
    {
        $this->mailData = MembershipEmailData::forTransaction($transaction);
        $this->membershipCancelled = $membershipCancelled;
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $status = $this->mailData['transactionStatus'];

        return (new MailMessage)
            ->subject(($this->membershipCancelled ? 'SharpMind | Membership Cancelled - ' : 'SharpMind | Membership Transaction '.$status.' - ').$this->mailData['referenceNumber'])
            ->view([$status === 'Expired' ? 'emails.membership-expired' : 'emails.transaction-status', 'emails.text'], $this->mailData + [
                'reason' => $this->reason,
                'membershipCancelled' => $this->membershipCancelled,
                'heading' => $this->membershipCancelled
                    ? 'Your membership has been cancelled.'
                    : ($status === 'Expired' ? 'Let’s pick up where you left off.' : 'An update on your transaction.'),
                'summary' => $this->membershipCancelled
                    ? 'Your SharpMind membership is now Inactive. Your transaction and payment records are available from your membership administrator.'
                    : ($status === 'Expired'
                        ? 'Your membership has expired. Contact your administrator to arrange a renewal.'
                        : 'Your membership transaction has been marked as '.$status.'.'),
            ]);
    }
}
