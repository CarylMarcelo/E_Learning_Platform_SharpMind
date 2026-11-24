@extends('emails.layout')
@section('title', 'Registration received')
@section('preheader', 'Your registration is received. Review your membership details and the next steps.')
@section('status')
<span style="display:inline-block;padding:7px 12px;border-radius:20px;background-color:#eaf2ff;color:#2463b2;font-size:14px;font-weight:bold;line-height:1.4;">Registration received</span>
@endsection
@section('content')
@include('emails.details', ['rows' => ['Membership number' => $membershipNumber, 'Membership level' => $levelName, 'Category' => $categoryName, 'Membership status' => $accountStatus]])
<h2 style="margin:24px 0 10px;color:#642c9b;font-size:18px;line-height:1.5;">Payment breakdown</h2>
@include('emails.details', ['rows' => ['Base membership fee' => '₱'.number_format($baseFee, 2)] + ($discountPercent > 0 ? [$categoryName.' discount ('.$discountPercent.'%)' => '−₱'.number_format($baseFee - $membershipFee, 2)] : []) + ['Total payable' => '₱'.number_format($membershipFee, 2)]])
<p style="margin:0;padding:16px;border-left:4px solid #854dd1;border-radius:8px;background-color:#f2eafa;color:#21152d;font-size:15px;line-height:1.7;"><strong style="color:#642c9b;">What happens next?</strong><br>Your administrator will record your membership transaction and payment. We’ll email you when your membership is approved and active.</p>
@endsection
