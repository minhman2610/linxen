@if ($paginator->hasPages())
<nav class="lx-paging">

    {{-- Prev --}}
    @if ($paginator->onFirstPage())
        <span class="lx-page disabled">‹</span>
    @else
        <a class="lx-page" href="{{ $paginator->previousPageUrl() }}">‹</a>
    @endif

    {{-- Pages --}}
    @foreach ($elements as $element)
        @if (is_string($element))
            <span class="lx-page dots">{{ $element }}</span>
        @endif

        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span class="lx-page active">{{ $page }}</span>
                @else
                    <a class="lx-page" href="{{ $url }}">{{ $page }}</a>
                @endif
            @endforeach
        @endif
    @endforeach

    {{-- Next --}}
    @if ($paginator->hasMorePages())
        <a class="lx-page" href="{{ $paginator->nextPageUrl() }}">›</a>
    @else
        <span class="lx-page disabled">›</span>
    @endif

</nav>
@endif
