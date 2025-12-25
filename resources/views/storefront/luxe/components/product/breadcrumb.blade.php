{{-- =====================================================
   PRODUCT CONTEXT LINE (BREADCRUMB – EDITORIAL)
===================================================== --}}
<nav
    class="lx-product-context"
    aria-label="breadcrumb"
    itemscope
    itemtype="https://schema.org/BreadcrumbList"
>
    <span class="lx-context-home"
          itemprop="itemListElement"
          itemscope
          itemtype="https://schema.org/ListItem">
        <a href="/" itemprop="item">
            <span itemprop="name">Trang chủ</span>
        </a>
        <meta itemprop="position" content="1">
    </span>

    @if(!empty($breadcrumbs))
        <span class="lx-context-sep">·</span>

        {{-- CHỈ LẤY CATEGORY CUỐI (PRIMARY) --}}
        @php $last = last($breadcrumbs); @endphp

        <span class="lx-context-category"
              itemprop="itemListElement"
              itemscope
              itemtype="https://schema.org/ListItem">
            @if(!empty($last['url']))
                <a href="{{ $last['url'] }}" itemprop="item">
                    <span itemprop="name">{{ $last['name'] }}</span>
                </a>
            @else
                <span itemprop="name">{{ $last['name'] }}</span>
            @endif
            <meta itemprop="position" content="2">
        </span>
    @endif
</nav>
