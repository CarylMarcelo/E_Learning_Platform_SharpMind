@extends('layouts.app')
@section('title', 'Dashboard')
@section('page', 'dashboard')
@section('content')
<x-page-heading title="Dashboard"><span class="date-chip"><x-icon name="calendar" />{{ now()->format('l, M d, Y') }}</span></x-page-heading>
<section class="welcome-banner" aria-labelledby="welcome-heading">
    <div class="welcome-copy"><span class="eyebrow">YOUR COMMUNITY. ENDLESS POSSIBILITIES.</span>
        <h2 id="welcome-heading">Welcome back, {{ str(auth()->user()->name)->before(' ') }}! <span class="welcome-spark">✧</span></h2>
        <p>Great things start with a sharp mind.</p>
        <div class="cluster welcome-actions"><a class="button button-white" href="{{ route('members.create') }}"><x-icon name="plus" /> Register a member</a><a class="button button-ghost view-action" href="{{ route('transactions.index') }}">View transactions <x-icon name="arrow-right" /></a></div>
    </div>
    <x-learning-art id="dashboard" />
</section>
<div class="section-label">
    <h2>Community at a glance</h2><span>Always up to date <span class="live-dot"></span></span>
</div>
<section class="metrics" aria-label="Community statistics">
    <a class="panel metric" href="{{ route('members.index') }}">
        <div class="metric-top">Total members <span class="metric-icon"><x-icon name="members" /></span></div>
        <div class="metric-value">{{ number_format($totalMembers) }}</div>
        <div class="metric-foot"><x-icon name="user-plus" /><strong>{{ $newMembersThisMonth }} new</strong> this month</div>
    </a>
    <a class="panel metric metric-featured" href="{{ route('members.index', ['status' => 'Active']) }}">
        <div class="metric-top">Active members <span class="metric-icon green"><x-icon name="user-check" /></span></div>
        <div class="metric-value">{{ number_format($activeMembers) }}</div>
        <div class="metric-foot"><x-icon name="check-circle" /><strong>{{ $totalMembers > 0 ? round($activeMembers / $totalMembers * 100) : 0 }}%</strong> of your community</div>
    </a>
    <a class="panel metric" href="{{ route('transactions.index', ['status' => 'Pending']) }}">
        <div class="metric-top">Pending transactions <span class="metric-icon blue"><x-icon name="clock" /></span></div>
        <div class="metric-value">{{ number_format($pendingTransactions) }}</div>
        <div class="metric-foot"><x-icon name="info" />Awaiting payment</div>
    </a>
    <a class="panel metric" href="{{ route('transactions.index') }}">
        <div class="metric-top">Total collected <span class="metric-icon blue"><x-icon name="wallet" /></span></div>
        <div class="metric-value">₱{{ number_format($totalCollected, 2) }}</div>
        <div class="metric-foot"><x-icon name="shield" />Recorded membership payments</div>
    </a>
</section>
<div class="dashboard-columns">
    <div class="stack dashboard-main">
        <section class="panel recent-transactions" aria-labelledby="recent-transactions-heading">
            <div class="panel-heading">
                <div>
                    <h2 id="recent-transactions-heading">Recent transactions</h2>
                </div><a class="text-link view-action" href="{{ route('transactions.index') }}">View all <x-icon name="arrow-right" /></a>
            </div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">Member</th>
                            <th scope="col">Type</th>
                            <th scope="col">Amount</th>
                            <th scope="col">Status</th>
                            <th scope="col"><span class="sr-only">Details</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentTransactions as $transaction)
                        <tr>
                            <td><a class="person" href="{{ route('members.show', $transaction->member) }}"><span class="avatar {{ ['','avatar-peach','avatar-green','avatar-blue','avatar-rose'][$loop->index % 5] }}">{{ str($transaction->member->first_name)->substr(0, 1)->upper() }}{{ str($transaction->member->last_name)->substr(0, 1)->upper() }}</span>
                                    <div>
                                        <div class="person-name">{{ $transaction->member->first_name }} {{ $transaction->member->last_name }}</div>
                                        <div class="person-detail">{{ $transaction->reference_number }}</div>
                                    </div>
                                </a></td>
                            <td>{{ $transaction->type }}</td>
                            <td class="amount">₱{{ number_format($transaction->amount, 2) }}</td>
                            <td><x-status-badge :status="$transaction->status" /></td>
                            <td><a class="transaction-arrow view-action" href="{{ route('transactions.show', $transaction) }}" aria-label="View transaction {{ $transaction->reference_number }}"><x-icon name="chevron-right" /></a></td>
                        </tr>
                        @empty<tr>
                            <td colspan="5"><x-empty-state icon="transactions" title="Your next chapter starts here" description="New membership transactions will appear here. Create your first one to get started."><a class="button button-soft button-small" href="{{ route('transactions.create') }}"><x-icon name="plus" />New transaction</a></x-empty-state></td>
                        </tr>@endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
    <div class="stack dashboard-aside">
        <section class="panel community-panel" aria-labelledby="membership-heading">
            <div class="panel-heading">
                <h2 id="membership-heading">Membership overview</h2><x-icon class="text-purple" name="members" />
            </div>
            <div class="membership-donut" role="img" aria-label="{{ $activeMembers }} active, {{ $pendingMembers }} pending, {{ $inactiveMembers }} inactive members" data-active="{{ $activeMembers }}" data-pending="{{ $pendingMembers }}" data-total="{{ $totalMembers }}">
                <div class="donut-center"><strong>{{ number_format($totalMembers) }}</strong><span>Total members</span></div>
            </div>
            <div class="membership-legend">
                <div><span class="legend-label"><i class="legend-dot active"></i>Active</span><strong>{{ $activeMembers }}</strong></div>
                <div><span class="legend-label"><i class="legend-dot pending"></i>Pending</span><strong>{{ $pendingMembers }}</strong></div>
                <div><span class="legend-label"><i class="legend-dot inactive"></i>Inactive</span><strong>{{ $inactiveMembers }}</strong></div>
            </div>
        </section>
    </div>
</div>
@endsection