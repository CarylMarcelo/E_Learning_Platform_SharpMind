<?php

namespace App\Notifications;

use App\Models\MembershipTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipTransactionApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [60, 300];

    private array $mailData;

    public function __construct(public MembershipTransaction $transaction)
    {
        $this->mailData = MembershipEmailData::forTransaction($transaction) + [
            'expiryDate' => MembershipEmailData::expiresAt($transaction)->format('F j, Y'),
        ];
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('SharpMind | Membership Approved & Active - '.$this->mailData['referenceNumber'])
            ->view(['emails.membership-approved', 'emails.text'], $this->mailData + [
                'heading' => 'You’re in. Let’s grow.',
                'summary' => 'Your payment has been verified and your membership is now Active. Your membership details and benefits are below.',
            ]);
    }
}
