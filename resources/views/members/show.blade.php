@extends('layouts.app')
@section('title', 'Member profile')
@section('page', 'members')
@section('content')
<a class="breadcrumb-link no-print" href="{{ route('members.index') }}"><x-icon name="chevron-left" />Back to members</a>
<x-page-heading title="Member profile" description="Every mind has a story. Here’s theirs."><button class="button button-secondary no-print" data-print type="button"><x-icon name="print" />Print record</button><a class="button button-primary edit-action no-print" href="{{ route('members.edit', $member) }}"><x-icon name="edit" />Edit member</a></x-page-heading>
<div class="detail-grid member-profile-grid">
    <section class="panel panel-padding">
        <div class="member-profile-header"><span class="avatar profile-avatar">{{ str($member->first_name)->substr(0, 1)->upper() }}{{ str($member->last_name)->substr(0, 1)->upper() }}</span>
            <div>
                <h2>{{ $member->first_name }} {{ $member->last_name }}</h2>
                <p class="reference">{{ $member->membership_number }}</p>
            </div><x-status-badge :status="$member->status" />
        </div>
        <dl class="detail-list">
            <div class="wide">
                <dt>Email address</dt>
                <dd>{{ $member->email }}</dd>
            </div>
            <div>
                <dt>Phone number</dt>
                <dd>{{ $member->phone ?: 'Not provided' }}</dd>
            </div>
            <div>
                <dt>Member since</dt>
                <dd>{{ $member->created_at->format('M d, Y') }}</dd>
            </div>
            <div class="wide">
                <dt>Address</dt>
                <dd>{{ $member->address ?: 'Not provided' }}</dd>
            </div>
        </dl>
    </section>
    <section class="panel panel-padding">
        <div class="between">
            <h2>Their learning journey</h2><span class="metric-icon"><x-icon name="cap" /></span>
        </div>
        <dl class="detail-list">
            <div>
                <dt>Membership category</dt>
                <dd>{{ $member->category->name ?? '—' }}</dd>
            </div>
            <div>
                <dt>Membership level</dt>
                <dd><x-level-badge :level="$member->level" /></dd>
            </div>
            <div>
                <dt>Validity period</dt>
                <dd>{{ $member->level->validity_days ?? 0 }} days</dd>
            </div>
            <div>
                <dt>Category discount</dt>
                <dd>{{ number_format($member->category->discount_percent ?? 0, 2) }}%</dd>
            </div>
            @if($member->latestApprovedTransaction?->expires_at)<div class="wide"><dt>Membership expiry</dt><dd>{{ $member->latestApprovedTransaction->expires_at->timezone(config('mail.membership_timezone'))->format('M d, Y · h:i A') }}</dd></div>@endif
        </dl>
        <div class="summary-box">
            <div class="summary-row"><span>Effective membership fee</span><strong>₱{{ number_format($member->getEffectiveFee(), 2) }}</strong></div>
            <x-benefits-list :items="$member->level?->benefitItems() ?? []" />
        </div>
    </section>
</div>
<section class="panel member-history">
    <div class="panel-heading">
        <div>
            <h2>Transaction history</h2>
            <p>Every step of their membership journey.</p>
        </div><a class="button button-soft button-small no-print" href="{{ route('transactions.create', ['member' => $member->id]) }}"><x-icon name="plus" />New transaction</a>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col">Reference</th>
                    <th scope="col">Type</th><th scope="col">Purchased level</th>
                    <th scope="col">Amount</th>
                    <th scope="col">Payment reference</th>
                    <th scope="col">Status</th>
                    <th scope="col">Date</th>
                    <th scope="col" class="no-print">Receipt</th>
                </tr>
            </thead>
            <tbody>@forelse($member->transactions as $transaction)<tr>
                    <td class="reference">{{ $transaction->reference_number }}</td>
                    <td>{{ $transaction->type }}</td><td>{{ $transaction->level_name ?? 'Unrecorded level' }}</td>
                    <td class="amount">₱{{ number_format($transaction->amount, 2) }}</td>
                    <td class="text-muted">{{ $transaction->payment->payment_reference ?? 'Awaiting payment' }}</td>
                    <td><x-status-badge :status="$transaction->status" /></td>
                    <td>{{ $transaction->created_at->format('M d, Y') }}</td>
                    <td class="no-print"><a class="text-link view-action" href="{{ route('transactions.show', $transaction) }}">View <x-icon name="arrow-right" /></a></td>
                </tr>@empty<tr>
                    <td colspan="8"><x-empty-state icon="transactions" title="A fresh start" description="This member’s transactions will appear here." /></td>
                </tr>@endforelse</tbody>
        </table>
    </div>
</section>
@endsection
