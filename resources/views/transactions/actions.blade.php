<div class="action-group no-print transaction-detail-actions">
@if($transaction->status === 'Pending')<a class="button button-primary" href="{{ route('payments.create', $transaction) }}"><x-icon name="wallet" />Record payment</a>@endif
@if($transaction->status === 'Paid')<form action="{{ route('transactions.approve', $transaction) }}" method="POST">@csrf<button class="button button-primary" type="submit"><x-icon name="check-circle" />Approve membership</button></form>@endif
<x-transaction-cancel-button :transaction="$transaction" />
</div>
