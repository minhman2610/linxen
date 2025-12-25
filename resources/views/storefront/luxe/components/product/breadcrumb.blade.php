@if(!empty($breadcrumbs))
<nav class="lx-art-breadcrumb" aria-label="breadcrumb">

    {{-- HOME --}}
    <a href="/"
       class="lx-art-item is-home"
       title="Trang chủ">
        <span class="lx-art-icon">⌂</span>
        <span class="lx-art-label">Trang chủ</span>
    </a>

    {{-- OTHER CRUMBS --}}
    @foreach($breadcrumbs as $crumb)

        <span class="lx-art-connector" aria-hidden="true">⟡</span>

        @if(!empty($crumb['url']))
            <a href="{{ $crumb['url'] }}"
               class="lx-art-item"
               title="{{ $crumb['name'] }}">
                <span class="lx-art-label">
                    {{ Str::upper($crumb['name']) }}
                </span>
            </a>
        @else
            <span class="lx-art-item is-active">
                <span class="lx-art-label">
                    {{ Str::upper($crumb['name']) }}
                </span>
            </span>
        @endif

    @endforeach

</nav>
@endif
