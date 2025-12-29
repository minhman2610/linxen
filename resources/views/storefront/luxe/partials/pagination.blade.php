@if ($paginator instanceof \Illuminate\Pagination\LengthAwarePaginator && $paginator->hasPages())
<nav class="lx-paging" aria-label="Pagination">

    {{-- Prev --}}
    @if ($paginator->onFirstPage())
        <span class="lx-page disabled" aria-hidden="true">‹</span>
    @else
        @php $prev = $paginator->previousPageUrl(); @endphp
        @if(is_string($prev))
            <a class="lx-page" href="{{ $prev }}" rel="prev">‹</a>
        @endif
    @endif

    @php
        $current = (int) $paginator->currentPage();
        $last    = (int) $paginator->lastPage();

        $start = max(1, $current - 2);
        $end   = min($last, $current + 2);

        if ($start <= 2) {
            $start = 1;
            $end   = min(5, $last);
        }

        if ($end >= $last - 1) {
            $end   = $last;
            $start = max(1, $last - 4);
        }
    @endphp

    {{-- First --}}
    @if ($start > 1)
        <a class="lx-page" href="{{ $paginator->url(1) }}">1</a>
        @if ($start > 2)
            <span class="lx-page dots">…</span>
        @endif
    @endif

    {{-- Middle --}}
    @for ($page = $start; $page <= $end; $page++)
        @if ($page === $current)
            <span class="lx-page active">{{ $page }}</span>
        @else
            @php $url = $paginator->url($page); @endphp
            @if(is_string($url))
                <a class="lx-page" href="{{ $url }}">{{ $page }}</a>
            @endif
        @endif
    @endfor

    {{-- Last --}}
    @if ($end < $last)
        @if ($end < $last - 1)
            <span class="lx-page dots">…</span>
        @endif
        <a class="lx-page" href="{{ $paginator->url($last) }}">{{ $last }}</a>
    @endif

    {{-- Next --}}
    @if ($paginator->hasMorePages())
        @php $next = $paginator->nextPageUrl(); @endphp
        @if(is_string($next))
            <a class="lx-page" href="{{ $next }}" rel="next">›</a>
        @endif
    @else
        <span class="lx-page disabled" aria-hidden="true">›</span>
    @endif

</nav>
@endif
