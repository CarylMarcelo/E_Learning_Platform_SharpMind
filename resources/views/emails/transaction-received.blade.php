@extends('emails.layout')
@section('title', 'Transaction received')
@section('status')
<span style="display:inline-block;padding:7px 12px;border-radius:20px;background-color:#eaf2ff;color:#2463b2;font-size:14px;font-weight:bold;line-height:1.4;">Awaiting payment</span>
@endsection
@section('content')
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:20px 0;background-color:#f2eafa;border-radius:12px;"><tr><td style="padding:18px 20px;">
    <p style="margin:0;color:#642c9b;font-size:14px;font-weight:bold;line-height:1.6;">AMOUNT DUE</p>
    <p style="margin:5px 0 0;color:#642c9b;font-size:30px;font-weight:bold;line-height:1.3;">₱{{ number_format($transactionAmount, 2) }}</p>
</td></tr></table>
@include('emails.details', ['rows' => ['Transaction reference' => $referenceNumber, 'Membership number' => $membershipNumber, 'Transaction type' => $transactionType, 'Membership level' => $levelName, 'Transaction status' => $transactionStatus]])
<p style="margin:0;padding:16px;border-left:4px solid #2463b2;border-radius:8px;background-color:#eaf2ff;color:#21152d;font-size:15px;line-height:1.7;"><strong>Next step: payment verification</strong><br>Share your transaction reference with your membership administrator when arranging payment. Your membership becomes active after approval.</p>
@endsection
