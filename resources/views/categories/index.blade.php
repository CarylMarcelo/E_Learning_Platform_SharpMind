@extends('layouts.app')
@section('title', 'Categories')
@section('page', 'categories')
@section('content')
<x-page-heading title="Categories"><button class="button button-primary" type="button" data-new-record><x-icon name="plus" />Add category</button></x-page-heading>
<div class="catalog-toolbar">
    <div class="search-field"><x-icon name="search" /><label class="sr-only" for="catalog-search">Search categories</label><input id="catalog-search" data-catalog-search placeholder="Find a category..."></div><label class="sr-only" for="catalog-sort">Sort categories</label><select id="catalog-sort" data-catalog-sort>
        <option value="newest">Newest first</option>
        <option value="name">Name, A to Z</option>
        <option value="members">Most members</option>
    </select>
</div>
<div class="catalog-grid categories-grid" data-catalog-grid>
    @foreach($categories->sortByDesc('id') as $category)
    <article class="panel category-card" data-catalog-item data-name="{{ $category->name }}" data-search="{{ $category->name }} {{ $category->description }}" data-created="{{ $category->id }}" data-members="{{ $category->members_count }}">
        <div class="between"><span class="category-icon category-tone-{{ $loop->index % 4 }}"><x-icon :name="['cap', 'book', 'members', 'sparkles'][$loop->index % 4]" /></span><span class="category-label">CATEGORY {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span></div>
        <h2>{{ $category->name }}</h2>
        <p class="category-description">{{ $category->description ?: 'A community of curious minds, learning and growing together.' }}</p>
        @if($category->discount_percent > 0)
        <div class="category-discount"><x-icon name="sparkles" /><span>{{ number_format($category->discount_percent, 2) }}% membership discount</span></div>
        @endif
        <div class="catalog-card-footer"><span class="catalog-member-count"><x-icon name="members" />{{ $category->members_count }} {{ $category->members_count === 1 ? 'member' : 'members' }}</span>
            <div class="action-group"><button type="button" class="icon-button edit-action" data-edit-record data-action="{{ route('categories.update', $category) }}" data-record="{{ $category->id }}" data-name="{{ $category->name }}" data-description="{{ $category->description }}" data-discount_percent="{{ $category->discount_percent }}" aria-label="Edit {{ $category->name }}"><x-icon name="edit" /></button>
                <form method="POST" action="{{ route('categories.destroy', $category) }}" data-confirm="Delete the {{ $category->name }} category? Categories assigned to members or open transactions cannot be deleted.">@csrf @method('DELETE')<button class="icon-button delete-action" type="submit" aria-label="Delete {{ $category->name }}"><x-icon name="trash" /></button></form>
            </div>
        </div>
    </article>
    @endforeach
</div>
@if($categories->isEmpty())<div class="panel"><x-empty-state icon="categories" title="Every community starts somewhere" description="Create your first category to give your members a place to belong."><button class="button button-soft" type="button" data-new-record><x-icon name="plus" />Add your first category</button></x-empty-state></div>@endif
<div class="panel" data-catalog-empty hidden><x-empty-state icon="search" title="No matching categories" description="Try another name or clear your search to explore every category." /></div>
<x-dialog id="category-editor" :title="old('_record') ? 'Edit category' : 'A new place to belong'" :data-reopen="$errors->any() && old('_editor') === 'category' ? 'true' : 'false'">
    <form id="category-form" action="{{ old('_record') ? route('categories.update', old('_record')) : route('categories.store') }}" method="POST" data-create-action="{{ route('categories.store') }}">
        @csrf<input type="hidden" name="_method" value="PUT" @disabled(!old('_record'))><input type="hidden" name="_editor" value="category"><input type="hidden" name="_record" value="{{ old('_record') }}">
        <div class="dialog-body">@if($errors->any() && old('_editor') === 'category')<div class="alert alert-error" role="alert">
                <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>@endif
            <div class="field"><label for="category-name">Category name <span class="required">*</span></label><input id="category-name" name="name" value="{{ old('name') }}" placeholder="e.g. Students, Professionals, Alumni" maxlength="255" required></div>
            <div class="field"><label for="category-description">Description</label><textarea id="category-description" name="description" rows="3" placeholder="Who is this category for?">{{ old('description') }}</textarea></div>
            <div class="field"><label for="category-discount">Membership discount (%)</label><input type="number" id="category-discount" name="discount_percent" value="{{ old('discount_percent', 0) }}" min="0" max="100" step="0.01" required>
                <p class="field-help">A percentage off the selected membership level’s fee.</p>
            </div>
        </div>
        <div class="dialog-footer"><button class="button button-secondary" type="button" data-close-dialog>Cancel</button><button class="button button-primary" type="submit"><x-icon name="check" />Save category</button></div>
    </form>
</x-dialog>
@endsection