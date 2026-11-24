<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMemberRequest;
use App\Http\Requests\UpdateMemberRequest;
use App\Models\Member;
use App\Models\MembershipCategory;
use App\Models\MembershipLevel;
use App\Notifications\MemberRegistered;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class MemberController extends Controller
{
    public function index(Request $request): View
    {
        $query = Member::with(['category', 'level']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('membership_number', 'like', "%{$search}%");
            });
        }

        if ($categoryId = $request->input('category')) {
            $query->where('membership_category_id', $categoryId);
        }

        if ($levelId = $request->input('level')) {
            $query->where('membership_level_id', $levelId);
        }

        if (in_array($request->input('status'), ['Active', 'Pending', 'Inactive'], true)) {
            $query->where('status', $request->input('status'));
        }

        $members = $query->latest()->paginate(10)->withQueryString();
        $categories = MembershipCategory::all();
        $levels = MembershipLevel::all();
        $statusCounts = Member::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('members.index', compact('members', 'categories', 'levels', 'statusCounts'));
    }

    public function create(): View
    {
        $categories = MembershipCategory::all();
        $levels = MembershipLevel::all();

        $phoneCountries = config('phone.countries');

        return view('members.create', compact('categories', 'levels', 'phoneCountries'));
    }

    public function store(StoreMemberRequest $request): RedirectResponse
    {
        $member = DB::transaction(function () use ($request) {
            $validated = $request->validated();
            $validated['status'] = 'Pending';

            $newMember = Member::createWithUniqueReference($validated);
            $newMember->load(['category', 'level']);

            Notification::route('mail', $newMember->email)
                ->notify(new MemberRegistered($newMember));

            return $newMember;
        });

        return redirect()->route('members.index')->with(
            'success',
            "Member registered ({$member->membership_number}) and welcome email queued to {$member->email}!"
        );
    }

    public function show(Member $member): View
    {
        $member->load(['category', 'level', 'latestApprovedTransaction', 'transactions' => fn ($query) => $query->with('payment')->latest()->orderByDesc('id')]);

        return view('members.show', compact('member'));
    }

    public function edit(Member $member): View
    {
        $categories = MembershipCategory::all();
        $levels = MembershipLevel::all();

        $phoneCountries = config('phone.countries');

        return view('members.edit', compact('member', 'categories', 'levels', 'phoneCountries'));
    }

    public function update(UpdateMemberRequest $request, Member $member): RedirectResponse
    {
        $member->update($request->validated());

        return redirect()->route('members.index')->with('success', 'Member updated successfully!');
    }

    public function destroy(Member $member): RedirectResponse
    {
        $member->delete();

        return redirect()->route('members.index')->with('success', 'Member deleted successfully!');
    }
}
