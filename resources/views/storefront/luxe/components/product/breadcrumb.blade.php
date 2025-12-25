{{-- =====================================================
   PRODUCT CATEGORY TABS (BREADCRUMB)
===================================================== --}}
<nav class="lx-category-tabs" aria-label="breadcrumb">

    {{-- HOME TAB --}}
    <a href="/" class="lx-cat-tab">
        <span class="lx-cat-icon">🏠</span>
        <span class="lx-cat-text">Trang chủ</span>
    </a>

    @foreach($breadcrumbs as $crumb)
        @if(!empty($crumb['url']))
            <a href="{{ $crumb['url'] }}" class="lx-cat-tab">
                <span class="lx-cat-icon">🏷️</span>
                <span class="lx-cat-text">{{ $crumb['name'] }}</span>
            </a>
        @else
            <span class="lx-cat-tab active">
                <span class="lx-cat-icon">👗</span>
                <span class="lx-cat-text">{{ $crumb['name'] }}</span>
            </span>
        @endif
    @endforeach

</nav>
