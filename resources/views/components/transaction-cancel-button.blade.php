@props(['transaction'])
@if($transaction->canBeCancelled())
    @php
        $endsMembership = $transaction->status === 'Approved';
        $label = $endsMembership ? 'Cancel membership' : 'Cancel transaction';
        $confirmation = $endsMembership
            ? 'Cancel '.$transaction->reference_number.' and end '.$transaction->member->first_name.' '.$transaction->member->last_name.'’s active membership? The member will become Inactive and receive a cancellation email. Recorded payments are kept in the payment history.'
            : 'Cancel transaction '.$transaction->reference_number.'? A cancellation email will be queued for the member. Any existing active membership and recorded payments are kept.';
    @endphp
    <form class="transaction-cancel-form" action="{{ route('transactions.cancel', $transaction) }}" method="POST" data-confirm="{{ $confirmation }}" data-confirm-label="{{ $label }}">
        @csrf
        <button class="button button-danger" type="submit" aria-label="{{ $label }} {{ $transaction->reference_number }}">{{ $label }}</button>
    </form>
@endif
