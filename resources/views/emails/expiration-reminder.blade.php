@extends('emails.layout')
@section('title', 'Membership renewal reminder')
@section('status')
<span style="display:inline-block;padding:7px 12px;border-radius:20px;background-color:#fff4dd;color:#805000;font-size:14px;font-weight:bold;line-height:1.4;">{{ $daysLeft === 0 ? 'Expires today' : 'Renewal reminder' }}</span>
@endsection
@section('content')
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:20px 0;background-color:#fff4dd;border-radius:12px;"><tr><td style="padding:18px 20px;">
    <p style="margin:0;color:#805000;font-size:14px;font-weight:bold;line-height:1.6;">{{ $daysLeft === 0 ? 'EXPIRES TODAY' : 'EXPIRATION DATE' }}</p>
    <p style="margin:6px 0 0;color:#21152d;font-size:26px;font-weight:bold;line-height:1.4;">{{ $expiryDate }}</p>
    <p style="margin:6px 0 0;color:#805000;font-size:16px;line-height:1.6;">{{ $daysLeft === 0 ? 'Renew today to keep your membership active.' : $daysLeft.' '.($daysLeft === 1 ? 'day' : 'days').' remaining' }}</p>
</td></tr></table>
@include('emails.details', ['rows' => ['Membership number' => $membershipNumber, 'Membership level' => $levelName, 'Transaction reference' => $referenceNumber]])
<p style="margin:0;padding:16px;border-left:4px solid #854dd1;border-radius:8px;background-color:#f2eafa;color:#21152d;font-size:15px;line-height:1.7;"><strong style="color:#642c9b;">How to renew</strong><br>Contact your membership administrator and share your membership number. They can create a renewal transaction and help with payment and approval.</p>
@endsection
