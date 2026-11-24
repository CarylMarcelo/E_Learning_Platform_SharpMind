@extends('layouts.app')
@section('title', 'Members Lists')
@section('page', 'members')
@section('content')
<x-page-heading title="Members Lists"><a class="button button-primary" href="{{ route('members.create') }}"><x-icon name="plus" />Register member</a></x-page-heading>
<section class="metrics members-metrics" aria-label="Member statistics">
    @foreach([['Total members', $statusCounts->sum(), 'members', ''], ['Active members', $statusCounts['Active'] ?? 0, 'user-check', 'green'], ['Pending members', $statusCounts['Pending'] ?? 0, 'clock', 'blue'], ['Inactive members', $statusCounts['Inactive'] ?? 0, 'refresh', 'red']] as [$label, $count, $icon, $tone])
    <div class="panel metric">
        <div class="metric-top">{{ $label }}<span class="metric-icon {{ $tone }}"><x-icon :name="$icon" /></span></div>
        <div class="metric-value">{{ number_format($count) }}</div>
    </div>
    @endforeach
</section>
<section class="panel members-panel" aria-label="Member directory">
    <form class="filters" action="{{ route('members.index') }}" method="GET">
        <input type="hidden" name="status" value="{{ request('status') }}">
        <div class="search-field"><x-icon name="search" /><label class="sr-only" for="member-search">Search members</label><input id="member-search" name="search" value="{{ request('search') }}" placeholder="Search by name, email, or membership ID..."></div>
        <label class="sr-only" for="category-filter">Category</label><select id="category-filter" name="category" data-auto-submit>
            <option value="">All categories</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(request('category')==$category->id)>{{ $category->name }}</option>@endforeach
        </select>
        <label class="sr-only" for="level-filter">Membership level</label><select id="level-filter" name="level" data-auto-submit>
            <option value="">All levels</option>@foreach($levels as $level)<option value="{{ $level->id }}" @selected(request('level')==$level->id)>{{ $level->name }}</option>@endforeach
        </select><button class="button button-secondary button-small" type="submit"><x-icon name="filter" />Apply</button>
        @if(request()->anyFilled(['search', 'category', 'level', 'status']))<a class="text-link" href="{{ route('members.index') }}">Reset</a>@endif
    </form>
    <div class="table-wrap">
        <table class="data-table" id="members-table">
            <thead>
                <tr>
                    <th scope="col">Member</th>
                    <th scope="col">Membership ID</th>
                    <th scope="col">Category</th>
                    <th scope="col">Level</th>
                    <th scope="col">Status</th>
                    <th scope="col">Joined</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($members as $member)
                <tr>
                    <td><a class="person" href="{{ route('members.show', $member) }}"><span class="avatar {{ ['','avatar-peach','avatar-green','avatar-blue','avatar-rose'][$loop->index % 5] }}">{{ str($member->first_name)->substr(0, 1)->upper() }}{{ str($member->last_name)->substr(0, 1)->upper() }}</span>
                            <div>
                                <div class="person-name">{{ $member->first_name }} {{ $member->last_name }}</div>
                                <div class="person-detail">{{ $member->email }}</div>
                            </div>
                        </a></td>
                    <td><a class="reference" href="{{ route('members.show', $member) }}">{{ $member->membership_number }}</a></td>
                    <td>{{ $member->category->name ?? '—' }}</td>
                    <td><x-level-badge :level="$member->level" /></td>
                    <td><x-status-badge :status="$member->status" /></td>
                    <td class="text-muted">{{ $member->created_at->format('M d, Y') }}</td>
                    <td>
                        <div class="action-group"><a class="icon-button view-action" href="{{ route('members.show', $member) }}" aria-label="View {{ $member->first_name }} {{ $member->last_name }}"><x-icon name="eye" /></a><a class="icon-button edit-action" href="{{ route('members.edit', $member) }}" aria-label="Edit {{ $member->first_name }} {{ $member->last_name }}"><x-icon name="edit" /></a>
                            <form method="POST" action="{{ route('members.destroy', $member) }}" data-confirm="Delete {{ $member->first_name }} {{ $member->last_name }}? Their membership, transactions, and payments will be permanently removed.">@csrf @method('DELETE')<button class="icon-button delete-action" aria-label="Delete {{ $member->first_name }} {{ $member->last_name }}" type="submit"><x-icon name="trash" /></button></form>
                        </div>
                    </td>
                </tr>
                @empty<tr>
                    <td colspan="7"><x-empty-state icon="members" :title="request()->anyFilled(['search', 'category', 'level', 'status']) ? 'No matching members' : 'Make room for bright minds'" :description="request()->anyFilled(['search', 'category', 'level', 'status']) ? 'Try a different search or reset your filters to see all members.' : 'Register your first member and start building a community of possibilities.'">@if(request()->anyFilled(['search', 'category', 'level', 'status']))<a class="button button-soft button-small" href="{{ route('members.index') }}">Reset filters</a>@else<a class="button button-soft button-small" href="{{ route('members.create') }}"><x-icon name="plus" />Register member</a>@endif</x-empty-state></td>
                </tr>@endforelse
            </tbody>
        </table>
    </div><x-pagination :paginator="$members" label="members" />
</section>
@endsection
