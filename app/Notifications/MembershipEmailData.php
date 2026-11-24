<?php

namespace App\Notifications;

use App\Models\Member;
use App\Models\MembershipTransaction;
use Carbon\Carbon;

class MembershipEmailData
{
    /**
     * @return array{memberName: string, membershipNumber: string, categoryName: string, levelName: string, accountStatus: string, baseFee: float, discountPercent: float, membershipFee: float, validityDays: int, benefits: list<string>}
     */
    public static function forMember(Member $member): array
    {
        $level = $member->level;
        $category = $member->category;

        return [
            'memberName' => trim($member->first_name.' '.$member->last_name),
            'membershipNumber' => (string) $member->membership_number,
            'categoryName' => $category->name ?? 'Not assigned',
            'levelName' => $level->name ?? 'Standard',
            'accountStatus' => (string) $member->status,
            'baseFee' => (float) ($level->fee ?? 0),
            'discountPercent' => (float) ($category->discount_percent ?? 0),
            'membershipFee' => $member->getEffectiveFee(),
            'validityDays' => (int) ($level->validity_days ?? 365),
            'benefits' => $level?->benefitItems() ?? ['Standard membership access'],
        ];
    }

    /**
     * @return array{memberName: string, membershipNumber: string, categoryName: string, levelName: string, accountStatus: string, baseFee: float, discountPercent: float, membershipFee: float, validityDays: int, benefits: list<string>, referenceNumber: string, transactionType: string, transactionStatus: string, transactionAmount: float, amountPaid: ?float, paymentReference: ?string, paymentMethod: ?string, paymentDate: ?string}
     */
    public static function forTransaction(MembershipTransaction $transaction): array
    {
        $payment = $transaction->relationLoaded('payment') || $transaction->exists ? $transaction->payment : null;

        return array_replace(self::forMember($transaction->member), [
            'categoryName' => $transaction->category_name ?? $transaction->member->category?->name ?? 'Not assigned',
            'levelName' => $transaction->level_name ?? $transaction->member->level?->name ?? 'Standard',
            'baseFee' => (float) ($transaction->base_fee ?? $transaction->member->level?->fee ?? 0),
            'discountPercent' => (float) ($transaction->discount_percent ?? $transaction->member->category?->discount_percent ?? 0),
            'membershipFee' => (float) $transaction->amount,
            'validityDays' => (int) ($transaction->validity_days ?? $transaction->member->level?->validity_days ?? 365),
            'benefits' => $transaction->benefitItems(),
            'referenceNumber' => (string) $transaction->reference_number,
            'transactionType' => (string) $transaction->type,
            'transactionStatus' => (string) $transaction->status,
            'transactionAmount' => (float) $transaction->amount,
            'amountPaid' => $payment ? (float) $payment->amount_paid : null,
            'paymentReference' => $payment?->payment_reference,
            'paymentMethod' => $payment?->payment_method,
            'paymentDate' => $payment?->payment_date
                ? Carbon::parse($payment->payment_date)->timezone(config('mail.membership_timezone'))->format('F j, Y · g:i A')
                : null,
        ]);
    }

    public static function expiresAt(MembershipTransaction $transaction): Carbon
    {
        return ($transaction->expires_at ?? ($transaction->approved_at ?? $transaction->updated_at ?? now())->copy()
            ->addDays((int) ($transaction->validity_days ?? $transaction->member->level->validity_days ?? 365)))
            ->copy()->timezone(config('mail.membership_timezone'));
    }
}
