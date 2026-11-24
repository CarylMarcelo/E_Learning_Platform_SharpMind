@props(['paginator', 'label' => 'records'])
<div class="table-footer">
    <span>Showing <strong>{{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }}</strong> of <strong>{{ number_format($paginator->total()) }}</strong> {{ $label }}</span>
    @if($paginator->hasPages())
    <nav class="pagination" aria-label="Pagination">
        @if($paginator->onFirstPage())<span class="disabled" aria-disabled="true"><x-icon name="chevron-left" /></span>
        @else<a href="{{ $paginator->previousPageUrl() }}" aria-label="Previous page"><x-icon name="chevron-left" /></a>@endif
        @foreach($paginator->getUrlRange(max(1, $paginator->currentPage() - 1), min($paginator->lastPage(), $paginator->currentPage() + 1)) as $number => $url)
        @if($number === $paginator->currentPage())<span class="current" aria-current="page">{{ $number }}</span>
        @else<a href="{{ $url }}" aria-label="Page {{ $number }}">{{ $number }}</a>@endif
        @endforeach
        @if($paginator->hasMorePages())<a href="{{ $paginator->nextPageUrl() }}" aria-label="Next page"><x-icon name="chevron-right" /></a>
        @else<span class="disabled" aria-disabled="true"><x-icon name="chevron-right" /></span>@endif
    </nav>
    @endif
</div>