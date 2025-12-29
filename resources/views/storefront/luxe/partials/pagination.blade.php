@if ($paginator->hasPages())
<nav class="lx-paging" aria-label="Pagination">

    {{-- Prev --}}
    @if ($paginator->onFirstPage())
        <span class="lx-page disabled" aria-hidden="true">‹</span>
    @else
        <a class="lx-page" href="{{ $paginator->previousPageUrl() }}" rel="prev">‹</a>
    @endif

    @php
        $current = $paginator->currentPage();
        $last    = $paginator->lastPage();

        // Hiển thị tối đa 5 page số
        $start = max(1, $current - 2);
        $end   = min($last, $current + 2);

        // Luôn chừa chỗ cho trang đầu/cuối
        if ($start <= 2) {
            $start = 1;
            $end   = min(5, $last);
        }

        if ($end >= $last - 1) {
            $end   = $last;
            $start = max(1, $last - 4);
        }
    @endphp

    {{-- First page --}}
    @if ($start > 1)
        <a class="lx-page" href="{{ $paginator->url(1) }}">1</a>
        @if ($start > 2)
            <span class="lx-page dots">…</span>
        @endif
    @endif

    {{-- Middle pages --}}
    @for ($page = $start; $page <= $end; $page++)
        @if ($page == $current)
            <span class="lx-page active">{{ $page }}</span>
        @else
            <a class="lx-page" href="{{ $paginator->url($page) }}">{{ $page }}</a>
        @endif
    @endfor

    {{-- Last page --}}
    @if ($end < $last)
        @if ($end < $last - 1)
            <span class="lx-page dots">…</span>
        @endif
        <a class="lx-page" href="{{ $paginator->url($last) }}">{{ $last }}</a>
    @endif

    {{-- Next --}}
    @if ($paginator->hasMorePages())
        <a class="lx-page" href="{{ $paginator->nextPageUrl() }}" rel="next">›</a>
    @else
        <span class="lx-page disabled" aria-hidden="true">›</span>
    @endif

</nav>
@endif
