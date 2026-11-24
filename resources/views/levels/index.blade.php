@extends('layouts.app')
@section('title', 'Levels')
@section('page', 'levels')
@section('content')
<x-page-heading title="Membership levels"><button class="button button-primary" type="button" data-new-record><x-icon name="plus" />Add level</button></x-page-heading>
<div class="catalog-toolbar">
    <div class="search-field"><x-icon name="search" /><label class="sr-only" for="catalog-search">Search membership levels</label><input id="catalog-search" data-catalog-search placeholder="Find a membership level..."></div><label class="sr-only" for="catalog-sort">Sort membership levels</label><select id="catalog-sort" data-catalog-sort>
        <option value="newest">Newest first</option>
        <option value="name">Name, A to Z</option>
        <option value="fee">Fee, low to high</option>
        <option value="members">Most members</option>
    </select>
</div>
<div class="catalog-grid levels-grid" data-catalog-grid>
    @foreach($levels->sortByDesc('id') as $level)
    <article class="panel level-card {{ $loop->index % 3 === 1 ? 'level-card-featured' : '' }}" data-catalog-item data-name="{{ $level->name }}" data-search="{{ $level->name }} {{ $level->benefits }}" data-created="{{ $level->id }}" data-members="{{ $level->members_count }}" data-fee="{{ $level->fee }}">
        <div class="level-card-top"><span class="level-icon"><x-icon :name="['book', 'cap', 'sparkles'][$loop->index % 3]" /></span><span class="level-number">LEVEL {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
            <div class="action-group"><button type="button" class="icon-button edit-action" data-edit-record data-action="{{ route('levels.update', $level) }}" data-record="{{ $level->id }}" data-name="{{ $level->name }}" data-fee="{{ $level->fee }}" data-validity_days="{{ $level->validity_days }}" data-benefits="{{ $level->benefits }}" aria-label="Edit {{ $level->name }}"><x-icon name="edit" /></button>
                <form action="{{ route('levels.destroy', $level) }}" method="POST" data-confirm="Delete the {{ $level->name }} level? Levels assigned to members or open transactions cannot be deleted.">@csrf @method('DELETE')<button type="submit" class="icon-button delete-action" aria-label="Delete {{ $level->name }}"><x-icon name="trash" /></button></form>
            </div>
        </div>
        <h2>{{ $level->name }}</h2>
        <p class="level-subtitle">A new step toward something great.</p>
        <div class="level-price"><span>₱</span>{{ number_format($level->fee, 2) }}</div>
        <p class="level-validity"><x-icon name="calendar" />Valid for {{ $level->validity_days }} days</p>
        <div class="level-benefits">
            <p class="eyebrow">WHAT’S INCLUDED</p>
            <x-benefits-list :items="$level->benefitItems()" />
        </div>
        <div class="level-card-footer"><span class="catalog-member-count"><x-icon name="members" />{{ $level->members_count }} {{ $level->members_count === 1 ? 'member' : 'members' }}</span><a class="text-link view-action" href="{{ route('members.index', ['level' => $level->id]) }}">View members <x-icon name="arrow-right" /></a></div>
    </article>
    @endforeach
</div>
@if($levels->isEmpty())<div class="panel"><x-empty-state icon="levels" title="Open up new possibilities" description="Build your first membership level with a fee, validity period, and benefits."><button class="button button-soft" type="button" data-new-record><x-icon name="plus" />Create your first level</button></x-empty-state></div>@endif
<div class="panel" data-catalog-empty hidden><x-empty-state icon="search" title="No matching levels" description="Try another name or clear your search to see all membership levels." /></div>
<x-dialog id="level-editor" :title="old('_record') ? 'Edit membership level' : 'A new level of possibility'" :data-reopen="$errors->any() && old('_editor') === 'level' ? 'true' : 'false'">
    <form id="level-form" action="{{ old('_record') ? route('levels.update', old('_record')) : route('levels.store') }}" method="POST" data-create-action="{{ route('levels.store') }}">
        @csrf<input type="hidden" name="_method" value="PUT" @disabled(!old('_record'))><input type="hidden" name="_editor" value="level"><input type="hidden" name="_record" value="{{ old('_record') }}">
        <div class="dialog-body">@if($errors->any() && old('_editor') === 'level')<div class="alert alert-error" role="alert">
                <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>@endif
            <div class="field"><label for="level-name">Level name <span class="required">*</span></label><input id="level-name" name="name" value="{{ old('name') }}" placeholder="e.g. Explorer, Scholar, Luminary" maxlength="255" required></div>
            <div class="form-grid">
                <div class="field"><label for="level-fee">Membership fee (₱) <span class="required">*</span></label><input type="number" id="level-fee" name="fee" value="{{ old('fee') }}" min="0" step="0.01" placeholder="0.00" required></div>
                <div class="field"><label for="level-validity">Validity (days) <span class="required">*</span></label><input type="number" id="level-validity" name="validity_days" value="{{ old('validity_days', 365) }}" min="1" step="1" required></div>
            </div>
            <div class="field"><label for="level-benefits">Membership benefits</label><textarea id="level-benefits" name="benefits" rows="4" placeholder="Add one benefit per line">{{ old('benefits') }}</textarea>
            </div>
        </div>
        <div class="dialog-footer"><button class="button button-secondary" type="button" data-close-dialog>Cancel</button><button class="button button-primary" type="submit"><x-icon name="check" />Save level</button></div>
    </form>
</x-dialog>
@endsection