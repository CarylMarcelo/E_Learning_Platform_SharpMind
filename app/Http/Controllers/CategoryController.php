<?php

namespace App\Http\Controllers;

use App\Models\MembershipCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = MembershipCategory::withCount('members')->get();

        return view('categories.index', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:membership_categories,name',
            'description' => 'nullable|string',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        $validated['discount_percent'] = $validated['discount_percent'] ?? 0;
        MembershipCategory::create($validated);

        return redirect()->route('categories.index')->with('success', 'Category added successfully!');
    }

    public function update(Request $request, MembershipCategory $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:membership_categories,name,'.$category->id,
            'description' => 'nullable|string',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        $validated['discount_percent'] = $validated['discount_percent'] ?? 0;
        $category->update($validated);

        return redirect()->route('categories.index')->with('success', 'Category updated successfully!');
    }

    public function destroy(MembershipCategory $category): RedirectResponse
    {
        return DB::transaction(function () use ($category): RedirectResponse {
            $category = MembershipCategory::query()->lockForUpdate()->findOrFail($category->id);

            if ($category->members()->exists() || $category->transactions()->whereIn('status', ['Pending', 'Paid'])->exists()) {
                return redirect()->route('categories.index')->with('error', 'This category is assigned to members or open transactions. Reassign members and finish or cancel open transactions before deleting it.');
            }

            $category->delete();

            return redirect()->route('categories.index')->with('success', 'Category deleted successfully!');
        }, 3);
    }
}
