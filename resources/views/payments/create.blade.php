@extends('layouts.app')
@section('title', 'Record payment')
@section('page', 'transactions')
@section('content')
<a class="breadcrumb-link" href="{{ route('transactions.show', $transaction) }}"><x-icon name="chevron-left" />Back to transaction</a><x-page-heading title="One step closer" description="Record a payment and move this membership forward." />
<form class="panel form-panel transaction-form" method="POST" action="{{ route('payments.store', $transaction) }}">@csrf
    <section class="form-section">
        <div class="form-section-heading">
            <h2>Record membership payment</h2>

        </div>
        <div class="summary-box payment-context">
            <div class="summary-row"><span>Transaction</span><strong>{{ $transaction->reference_number }}</strong></div>
            <div class="summary-row"><span>Member</span><span>{{ $transaction->member->first_name }} {{ $transaction->member->last_name }}</span></div>
            <div class="summary-row"><span>Amount due</span><strong>₱{{ number_format($transaction->amount, 2) }}</strong></div>
        </div>
        <div class="form-grid">
            <div class="field"><label for="amount_paid">Amount paid (₱) <span class="required">*</span></label><input type="number" id="amount_paid" name="amount_paid" value="{{ old('amount_paid', $transaction->amount) }}" min="{{ $transaction->amount }}" step="0.01" required>
                <p class="field-help">Must cover the full amount due.</p>
            </div>
            <div class="field"><label for="payment_method">Payment method <span class="required">*</span></label><select id="payment_method" name="payment_method" required>@foreach(['Cash', 'GCash', 'Bank Transfer', 'Credit/Debit Card'] as $method)<option @selected(old('payment_method')===$method)>{{ $method }}</option>@endforeach</select></div>
        </div>
    </section>
    <div class="form-actions"><a class="button button-secondary" href="{{ route('transactions.show', $transaction) }}">Cancel</a><button class="button button-primary" type="submit"><x-icon name="check-circle" />Confirm payment</button></div>
</form>

@endsection