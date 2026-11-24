<?php

namespace App\Http\Controllers;

use App\Models\MembershipLevel;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LevelController extends Controller
{
    public function index(): View
    {
        $levels = MembershipLevel::withCount('members')->get();

        return view('levels.index', compact('levels'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:membership_levels,name',
            'fee' => 'required|numeric|decimal:0,2|min:0|max:99999999.99',
            'validity_days' => 'required|integer|min:1',
            'benefits' => 'nullable|string',
        ]);

        MembershipLevel::create($request->only(['name', 'fee', 'validity_days', 'benefits']));

        return redirect()->route('levels.index')->with('success', 'Membership level added successfully!');
    }

    public function update(Request $request, MembershipLevel $level): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:membership_levels,name,'.$level->id,
            'fee' => 'required|numeric|decimal:0,2|min:0|max:99999999.99',
            'validity_days' => 'required|integer|min:1',
            'benefits' => 'nullable|string',
        ]);

        $level->update($request->only(['name', 'fee', 'validity_days', 'benefits']));

        return redirect()->route('levels.index')->with('success', 'Membership level updated successfully!');
    }

    public function destroy(MembershipLevel $level): RedirectResponse
    {
        return DB::transaction(function () use ($level): RedirectResponse {
            $level = MembershipLevel::query()->lockForUpdate()->findOrFail($level->id);

            if ($level->members()->exists() || $level->transactions()->whereIn('status', ['Pending', 'Paid'])->exists()) {
                return redirect()->route('levels.index')->with('error', 'This level is assigned to members or open transactions. Reassign members and finish or cancel open transactions before deleting it.');
            }

            $level->delete();

            return redirect()->route('levels.index')->with('success', 'Membership level deleted successfully!');
        }, 3);
    }
}
