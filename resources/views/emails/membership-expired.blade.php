@extends('emails.layout')
@section('title', 'Membership expired')
@section('status')
<span style="display:inline-block;padding:7px 12px;border-radius:20px;background-color:#fdebf0;color:#be233c;font-size:14px;font-weight:bold;line-height:1.4;">Membership expired</span>
@endsection
@section('content')
@include('emails.details', ['rows' => ['Membership number' => $membershipNumber, 'Membership level' => $levelName, 'Transaction reference' => $referenceNumber, 'Transaction status' => $transactionStatus, 'Membership status' => $accountStatus]])
<p style="margin:0 0 18px;color:#21152d;font-size:15px;line-height:1.7;">{{ $reason }}</p>
<p style="margin:0;padding:16px;border-left:4px solid #854dd1;border-radius:8px;background-color:#f2eafa;color:#21152d;font-size:15px;line-height:1.7;"><strong style="color:#642c9b;">Ready to renew?</strong><br>Share your membership number with your administrator to arrange a renewal. Your membership can become active again after payment and approval.</p>
@endsection
