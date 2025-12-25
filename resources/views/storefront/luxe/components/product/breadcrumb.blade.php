{{-- =====================================================
   PRODUCT BREADCRUMB – CATEGORY NAV
===================================================== --}}
<nav
    class="lx-breadcrumb"
    aria-label="breadcrumb"
    itemscope
    itemtype="https://schema.org/BreadcrumbList"
>
    <ol class="lx-breadcrumb-list">

        {{-- HOME --}}
        <li
            class="lx-breadcrumb-item"
            itemprop="itemListElement"
            itemscope
            itemtype="https://schema.org/ListItem"
        >
            <a href="/" itemprop="item" class="lx-breadcrumb-home">
                <svg width="14" height="14" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8"/>
                    <path d="M3 10a2 2 0 0 1 .7-1.5l7-6a2 2 0 0 1 2.6 0l7 6A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                </svg>
                <span itemprop="name" class="sr-only">Trang chủ</span>
            </a>
            <meta itemprop="position" content="1">
        </li>

        {{-- CATEGORY TREE --}}
        @foreach($breadcrumbs as $index => $crumb)
            <li
                class="lx-breadcrumb-item"
                itemprop="itemListElement"
                itemscope
                itemtype="https://schema.org/ListItem"
            >
                <span class="lx-breadcrumb-sep">/</span>

                @if(!empty($crumb['url']))
                    <a href="{{ $crumb['url'] }}"
                       itemprop="item"
                       class="lx-breadcrumb-link">
                        <span itemprop="name">{{ $crumb['name'] }}</span>
                    </a>
                @else
                    <span class="lx-breadcrumb-current"
                          itemprop="name">
                        {{ $crumb['name'] }}
                    </span>
                @endif

                <meta itemprop="position" content="{{ $index + 2 }}">
            </li>
        @endforeach

    </ol>
</nav>
