@extends('emails.layout')
@section('title', ($membershipCancelled ?? false) ? 'Membership cancelled' : 'Transaction status update')
@section('status')
<span style="display:inline-block;padding:7px 12px;border-radius:20px;background-color:#fdebf0;color:#be233c;font-size:14px;font-weight:bold;line-height:1.4;">{{ ($membershipCancelled ?? false) ? 'Membership cancelled' : 'Transaction '.strtolower($transactionStatus) }}</span>
@endsection
@section('content')
@include('emails.details', ['rows' => ['Transaction reference' => $referenceNumber, 'Membership number' => $membershipNumber, 'Transaction status' => $transactionStatus, 'Membership status' => $accountStatus]])
<p style="margin:0;padding:16px;border-left:4px solid #be233c;border-radius:8px;background-color:#fdebf0;color:#21152d;font-size:15px;line-height:1.7;">{{ $reason }}</p>
@endsection
