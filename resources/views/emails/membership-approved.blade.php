@extends('emails.layout')
@section('title', 'Membership active')
@section('preheader', 'Your payment is verified and your SharpMind membership is active. See your validity date and benefits.')
@section('status')
<span style="display:inline-block;padding:7px 12px;border-radius:20px;background-color:#e9f7ef;color:#18794e;font-size:14px;font-weight:bold;line-height:1.4;">Membership active</span>
@endsection
@section('content')
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin:20px 0;background-color:#e9f7ef;border-radius:12px;"><tr><td style="padding:18px 20px;">
    <p style="margin:0;color:#18794e;font-size:14px;font-weight:bold;line-height:1.6;">AMOUNT PAID</p>
    <p style="margin:5px 0 0;color:#18794e;font-size:30px;font-weight:bold;line-height:1.3;">₱{{ number_format($amountPaid ?? $transactionAmount, 2) }}</p>
</td></tr></table>
@include('emails.details', ['rows' => ['Membership number' => $membershipNumber, 'Membership level' => $levelName, 'Valid until' => $expiryDate, 'Transaction reference' => $referenceNumber] + ($paymentReference ? ['Payment reference' => $paymentReference, 'Payment method' => $paymentMethod] : [])])
<h2 style="margin:24px 0 10px;color:#642c9b;font-size:18px;line-height:1.5;">Your membership benefits</h2>
<ul style="list-style:none;margin:0;padding:0;">
    @foreach($benefits as $benefit)
    <li style="margin:8px 0;padding:12px 14px;border:1px solid #e4d6f1;border-radius:10px;background-color:#faf7fe;color:#21152d;font-size:15px;line-height:1.7;overflow-wrap:anywhere;"><span style="color:#642c9b;font-weight:bold;">✓</span> {{ $benefit }}</li>
    @endforeach
</ul>
@endsection
