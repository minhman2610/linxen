@if(!empty($breadcrumbs))
<nav class="lx-art-breadcrumb" aria-label="breadcrumb">
    @foreach($breadcrumbs as $index => $crumb)

        @if($index === 0)
            <a href="{{ $crumb['url'] ?? '/' }}"
               class="lx-art-item is-home">
                {{ $crumb['name'] }}
            </a>
        @else
            <span class="lx-art-connector">⟡</span>

            @if(!empty($crumb['url']))
                <a href="{{ $crumb['url'] }}"
                   class="lx-art-item">
                    {{ $crumb['name'] }}
                </a>
            @else
                <span class="lx-art-item is-active">
                    {{ $crumb['name'] }}
                </span>
            @endif
        @endif

    @endforeach
</nav>
@endif
