<?php

namespace App\Notifications;

use App\Models\MembershipTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MembershipTransactionSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [60, 300];

    private array $mailData;

    public function __construct(public MembershipTransaction $transaction)
    {
        $this->mailData = MembershipEmailData::forTransaction($transaction);
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('SharpMind | Transaction Received - '.$this->mailData['referenceNumber'])
            ->view(['emails.transaction-received', 'emails.text'], $this->mailData + [
                'heading' => 'Your transaction is in.',
                'summary' => 'Your membership transaction has been recorded. Keep the reference below for payment verification.',
            ]);
    }
}
