@if ($paginator->hasPages())
    @php
        $L = $lang ?? app()->getLocale();
        $prev = __('site.pagination.prev', [], $L);
        $next = __('site.pagination.next', [], $L);
        $info = __('site.pagination.page', [], $L);
        $of   = __('site.pagination.of', [], $L);
    @endphp
    <nav class="pager" aria-label="{{ $info }}">
        @if ($paginator->onFirstPage())
            <span class="disabled">{{ $prev }}</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev">{{ $prev }}</a>
        @endif

        <span class="pager-info">{{ $info }} {{ $paginator->currentPage() }} {{ $of }} {{ $paginator->lastPage() }}</span>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next">{{ $next }}</a>
        @else
            <span class="disabled">{{ $next }}</span>
        @endif
    </nav>
@endif
