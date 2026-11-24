<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MembershipTransaction;
use App\Models\Payment;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $totalMembers = Member::count();
        $activeMembers = Member::where('status', 'Active')->count();
        $pendingTransactions = MembershipTransaction::where('status', 'Pending')->count();
        $totalCollected = Payment::sum('amount_paid');
        $recentTransactions = MembershipTransaction::with('member')->latest()->take(5)->get();
        $pendingMembers = Member::where('status', 'Pending')->count();
        $inactiveMembers = Member::where('status', 'Inactive')->count();
        $newMembersThisMonth = Member::where('created_at', '>=', now()->startOfMonth())->count();

        return view('dashboard', compact(
            'totalMembers',
            'activeMembers',
            'pendingTransactions',
            'totalCollected',
            'recentTransactions',
            'pendingMembers',
            'inactiveMembers',
            'newMembersThisMonth'
        ));
    }
}
