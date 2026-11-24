<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MembershipLevel;
use App\Models\MembershipTransaction;
use App\Models\Payment;
use App\Notifications\MembershipTransactionApproved;
use App\Notifications\MembershipTransactionStatusUpdated;
use App\Notifications\MembershipTransactionSubmitted;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $query = MembershipTransaction::with(['member.level', 'payment']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                    ->orWhereHas('member', function ($mq) use ($search) {
                        $mq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $transactions = $query->latest()->paginate(10)->withQueryString();
        $statusCounts = MembershipTransaction::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $totalCollected = Payment::sum('amount_paid');
        $pendingAmount = MembershipTransaction::where('status', 'Pending')->sum('amount');

        return view('transactions.index', compact('transactions', 'statusCounts', 'totalCollected', 'pendingAmount'));
    }

    public function create(): View
    {
        $members = Member::with(['category', 'level'])->get();
        $levels = MembershipLevel::all();

        return view('transactions.create', compact('members', 'levels'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'membership_level_id' => 'required|exists:membership_levels,id',
            'type' => 'required|in:Application,Renewal',
        ]);

        $transaction = DB::transaction(function () use ($validated): MembershipTransaction {
            $member = Member::query()->lockForUpdate()->findOrFail($validated['member_id']);
            $level = MembershipLevel::query()->lockForUpdate()->findOrFail($validated['membership_level_id']);
            $category = $member->category()->lockForUpdate()->firstOrFail();
            $member->setRelation('category', $category);
            $purchase = MembershipTransaction::purchaseSnapshot($member, $level, $category);
            $transaction = MembershipTransaction::createWithUniqueReference([
                ...$purchase,
                'member_id' => $member->id,
                'type' => $validated['type'],
                'amount' => round($purchase['base_fee'] * (1 - $purchase['discount_percent'] / 100), 2),
                'status' => 'Pending',
            ]);
            $transaction->setRelation('member', $member);

            Notification::route('mail', $member->email)->notify(new MembershipTransactionSubmitted($transaction));

            return $transaction;
        }, 3);

        return redirect()->route('transactions.index')->with(
            'success',
            "Transaction {$transaction->reference_number} created and email notification queued to {$transaction->member->email}!"
        );
    }

    public function show(MembershipTransaction $transaction): View
    {
        $transaction->load(['member.category', 'member.level', 'member.latestApprovedTransaction', 'payment']);

        return view('transactions.show', compact('transaction'));
    }

    public function approve(MembershipTransaction $transaction): RedirectResponse
    {
        return DB::transaction(function () use ($transaction): RedirectResponse {
            $transaction = MembershipTransaction::query()->lockForUpdate()->findOrFail($transaction->id);

            if ($transaction->status !== 'Paid') {
                return back()->with('error', 'Only paid transactions can be approved.');
            }

            $payment = $transaction->payment;

            if (! $payment || (float) $payment->amount_paid < (float) $transaction->amount) {
                return back()->with('error', 'A recorded payment covering the full transaction amount is required before approval.');
            }

            if (! $transaction->membership_level_id || ! $transaction->membership_category_id) {
                return back()->with('error', 'The purchased category or level is unavailable. Cancel this transaction and create a new one.');
            }

            $member = $transaction->member()->lockForUpdate()->firstOrFail();
            $member->load('latestApprovedTransaction');
            $approvedAt = now();
            $validFrom = $approvedAt->copy();
            $current = $member->latestApprovedTransaction;

            if ($transaction->type === 'Renewal' && $member->status === 'Active' && $current?->expires_at?->isFuture()) {
                $validFrom = $current->expires_at->copy();
            }

            $transaction->update([
                'status' => 'Approved',
                'approved_at' => $approvedAt,
                'expires_at' => $validFrom->addDays($transaction->validity_days),
            ]);
            $member->update([
                'status' => 'Active',
                'membership_level_id' => $transaction->membership_level_id,
                'membership_category_id' => $transaction->membership_category_id,
            ]);
            $transaction->setRelation('member', $member);
            Notification::route('mail', $member->email)->notify(new MembershipTransactionApproved($transaction));

            return back()->with('success', "Transaction approved, member activated, and confirmation email queued to {$member->email}!");
        }, 3);
    }

    public function cancel(MembershipTransaction $transaction): RedirectResponse
    {
        return DB::transaction(function () use ($transaction): RedirectResponse {
            $transaction = MembershipTransaction::query()->lockForUpdate()->findOrFail($transaction->id);

            if (! in_array($transaction->status, ['Pending', 'Paid', 'Approved'], true)) {
                return back()->with('error', 'Only pending, paid, or approved transactions can be cancelled.');
            }

            $member = $transaction->member()->lockForUpdate()->firstOrFail();
            $member->load(['category', 'level', 'latestApprovedTransaction']);
            $transaction->setRelation('member', $member);

            if (! $transaction->canBeCancelled()) {
                return back()->with('error', 'Only the current active membership can be cancelled.');
            }

            $membershipCancelled = $transaction->status === 'Approved';
            $transaction->update(['status' => 'Cancelled']);

            if ($membershipCancelled) {
                $member->update(['status' => 'Inactive']);
            }

            $reason = $membershipCancelled
                ? 'Your membership was cancelled by the administrator and is now Inactive. Contact your membership administrator if you would like to renew.'
                : 'This transaction was cancelled by the administrator. Contact your membership administrator if you need help with a new transaction.';
            Notification::route('mail', $member->email)->notify(new MembershipTransactionStatusUpdated(
                $transaction, $reason, $membershipCancelled
            ));

            return back()->with('success', $membershipCancelled
                ? "Membership cancelled, member set to Inactive, and cancellation email queued to {$member->email}."
                : "Transaction marked as Cancelled and email queued to {$member->email}."
            );
        }, 3);
    }
}
