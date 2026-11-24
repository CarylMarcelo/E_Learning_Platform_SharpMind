{{-- This is the plain-text MIME part; preserve names and punctuation without HTML entities. --}}
SharpMind · Learn. Grow. Belong.

{!! $heading !!}

Hello {!! $memberName !!},

{!! $summary !!}

Membership number: {!! $membershipNumber !!}
Membership level: {!! $levelName !!}
Category: {!! $categoryName !!}
Membership status: {!! $accountStatus !!}
@if(isset($referenceNumber))
Transaction reference: {!! $referenceNumber !!}
Transaction type: {!! $transactionType !!}
Transaction amount: ₱{{ number_format($transactionAmount, 2) }}
Transaction status: {!! $transactionStatus !!}
@else
PAYMENT BREAKDOWN
Base membership fee: ₱{{ number_format($baseFee, 2) }}
@if($discountPercent > 0)
{!! $categoryName !!} discount ({{ $discountPercent }}%): −₱{{ number_format($baseFee - $membershipFee, 2) }}
@endif
Total payable: ₱{{ number_format($membershipFee, 2) }}
@endif
@if(isset($amountPaid))
Amount paid: ₱{{ number_format($amountPaid, 2) }}
Payment reference: {!! $paymentReference !!}
Payment method: {!! $paymentMethod !!}
@endif
@if(isset($expiryDate))
Expiration date: {!! $expiryDate !!}
@endif
@if(isset($daysLeft))
Time remaining: {{ $daysLeft === 0 ? 'Expires today' : $daysLeft.' '.($daysLeft === 1 ? 'day' : 'days') }}
@endif
@if(isset($reason))
{!! $reason !!}
@endif
@if(isset($expiryDate) && !isset($daysLeft))

BENEFITS
@foreach($benefits as $benefit)
- {!! $benefit !!}
@endforeach
@endif

Need help or want to renew? Contact your membership administrator and share your membership number.

The SharpMind team
© {{ date('Y') }} SharpMind.
