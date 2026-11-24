<?php

namespace App\Http\Controllers;

use App\Models\MembershipTransaction;
use App\Models\Payment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function create(MembershipTransaction $transaction): View|RedirectResponse
    {
        if ($transaction->status !== 'Pending' || $transaction->payment()->exists()) {
            return redirect()->route('transactions.show', $transaction)->with('error', 'Only pending transactions without a payment can receive payment.');
        }

        return view('payments.create', compact('transaction'));
    }

    public function store(Request $request, MembershipTransaction $transaction): RedirectResponse
    {
        return DB::transaction(function () use ($request, $transaction): RedirectResponse {
            $transaction = MembershipTransaction::query()->lockForUpdate()->findOrFail($transaction->id);

            if ($transaction->status !== 'Pending' || $transaction->payment()->exists()) {
                return redirect()->route('transactions.show', $transaction)->with('error', 'Only pending transactions without a payment can receive payment.');
            }

            $validated = $request->validate([
                'amount_paid' => 'required|numeric|decimal:0,2|max:99999999.99|min:'.$transaction->amount,
                'payment_method' => 'required|string|max:255',
            ]);

            Payment::createWithUniqueReference([
                'membership_transaction_id' => $transaction->id,
                'amount_paid' => $validated['amount_paid'],
                'payment_method' => $validated['payment_method'],
            ]);
            $transaction->update(['status' => 'Paid']);

            return redirect()->route('transactions.index')->with('success', 'Payment recorded successfully!');
        }, 3);
    }
}
