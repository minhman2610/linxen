{{-- =====================================================
   PRODUCT CATEGORY PATH – CONNECTED
===================================================== --}}
<nav class="lx-cat-path" aria-label="breadcrumb">

    {{-- HOME --}}
    <a href="/" class="lx-cat-node">
        <span class="lx-cat-icon">🏠</span>
        <span class="lx-cat-text">Trang chủ</span>
    </a>

    @foreach($breadcrumbs as $crumb)
        @if(!empty($crumb['url']))
            <a href="{{ $crumb['url'] }}" class="lx-cat-node">
                <span class="lx-cat-icon">🏷️</span>
                <span class="lx-cat-text">{{ $crumb['name'] }}</span>
            </a>
        @else
            <span class="lx-cat-node active">
                <span class="lx-cat-icon">👗</span>
                <span class="lx-cat-text">{{ $crumb['name'] }}</span>
            </span>
        @endif
    @endforeach

</nav>
