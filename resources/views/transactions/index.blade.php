@extends('layouts.app')
@section('title', 'Transactions')
@section('page', 'transactions')
@section('content')
<x-page-heading class="transactions-heading" title="Transactions"><a class="button button-primary" href="{{ route('transactions.create') }}"><x-icon name="plus" />New transaction</a></x-page-heading>
<section class="metrics transactions-metrics" aria-label="Transaction statistics">
    <div class="panel metric"><div class="metric-top">Total transactions<span class="metric-icon"><x-icon name="transactions" /></span></div><div class="metric-value">{{ number_format($statusCounts->sum()) }}</div></div>
    <div class="panel metric metric-featured"><div class="metric-top">Total collected<span class="metric-icon green"><x-icon name="wallet" /></span></div><div class="metric-value">₱{{ number_format($totalCollected, 2) }}</div></div>
    <div class="panel metric"><div class="metric-top">Awaiting payment<span class="metric-icon blue"><x-icon name="clock" /></span></div><div class="metric-value">₱{{ number_format($pendingAmount, 2) }}</div></div>
    <div class="panel metric"><div class="metric-top">Ready for approval<span class="metric-icon blue"><x-icon name="check-circle" /></span></div><div class="metric-value">{{ number_format($statusCounts['Paid'] ?? 0) }}</div></div>
</section>
<section class="panel transactions-panel" aria-label="Transaction ledger">
    <form class="filters" method="GET" action="{{ route('transactions.index') }}">
        <div class="search-field"><x-icon name="search" /><label class="sr-only" for="transaction-search">Search transactions</label><input id="transaction-search" name="search" value="{{ request('search') }}" placeholder="Search by reference number or member name..."></div>
        <label class="sr-only" for="transaction-status">Transaction status</label>
        <select id="transaction-status" name="status" data-auto-submit>
            <option value="">All statuses</option>
            @foreach(['Pending', 'Paid', 'Approved', 'Expired', 'Cancelled'] as $status)
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
            @endforeach
        </select>
        @if(request()->anyFilled(['search', 'status']))<a class="text-link" href="{{ route('transactions.index') }}">Reset</a>@endif
    </form>
    <div class="table-wrap"><table class="data-table" id="transactions-table"><thead><tr><th scope="col">Reference</th><th scope="col">Member</th><th scope="col">Type</th><th scope="col">Date</th><th scope="col">Amount</th><th scope="col">Status</th><th scope="col">Actions</th></tr></thead><tbody>
        @forelse($transactions as $transaction)<tr><td><a class="reference" href="{{ route('transactions.show', $transaction) }}">{{ $transaction->reference_number }}</a></td><td><a class="person" href="{{ route('members.show', $transaction->member) }}"><span class="avatar {{ ['','avatar-peach','avatar-green','avatar-blue','avatar-rose'][$loop->index % 5] }}">{{ str($transaction->member->first_name)->substr(0, 1)->upper() }}{{ str($transaction->member->last_name)->substr(0, 1)->upper() }}</span><div><div class="person-name">{{ $transaction->member->first_name }} {{ $transaction->member->last_name }}</div><div class="person-detail">{{ $transaction->level_name ?? 'Unrecorded level' }}</div></div></a></td><td>{{ $transaction->type }}</td><td class="text-muted">{{ $transaction->created_at->format('M d, Y') }}</td><td class="amount">₱{{ number_format($transaction->amount, 2) }}</td><td><x-status-badge :status="$transaction->status" /></td><td><div class="action-group"><a class="icon-button view-action" href="{{ route('transactions.show', $transaction) }}" aria-label="View {{ $transaction->reference_number }}"><x-icon name="eye" /></a>@if($transaction->status === 'Pending')<a class="button button-soft button-small" href="{{ route('payments.create', $transaction) }}">Record payment</a>@elseif($transaction->status === 'Paid')<form action="{{ route('transactions.approve', $transaction) }}" method="POST">@csrf<button type="submit" class="button button-soft button-small"><x-icon name="check" />Approve</button></form>@endif</div></td></tr>
        @empty<tr><td colspan="7"><x-empty-state icon="transactions" :title="request()->anyFilled(['search', 'status']) ? 'No matching transactions' : 'A fresh page for your community'" :description="request()->anyFilled(['search', 'status']) ? 'Try another reference number or member name, or reset your filters.' : 'Create an application or renewal to begin your transaction history.'">@if(request()->anyFilled(['search', 'status']))<a class="button button-soft button-small" href="{{ route('transactions.index') }}">Reset filters</a>@else<a class="button button-soft button-small" href="{{ route('transactions.create') }}"><x-icon name="plus" />Create transaction</a>@endif</x-empty-state></td></tr>@endforelse
    </tbody></table></div><x-pagination :paginator="$transactions" label="transactions" />
</section>
@endsection
