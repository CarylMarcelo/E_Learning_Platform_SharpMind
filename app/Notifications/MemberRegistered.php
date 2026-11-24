<?php

namespace App\Notifications;

use App\Models\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MemberRegistered extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [60, 300];

    private array $mailData;

    public function __construct(public Member $member)
    {
        $this->mailData = MembershipEmailData::forMember($member);
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('SharpMind | Membership Registration Received - '.$this->mailData['membershipNumber'])
            ->view(['emails.registration-received', 'emails.text'], $this->mailData + [
                'heading' => 'Your next chapter starts here.',
                'summary' => 'Your registration has been received. Complete your membership transaction and payment with your administrator to activate your membership.',
            ]);
    }
}
