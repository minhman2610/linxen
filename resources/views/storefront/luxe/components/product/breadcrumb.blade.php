{{-- =====================================================
   PRODUCT BREADCRUMB – LUXURY ART PATH
===================================================== --}}
@if(!empty($breadcrumbs))
    <nav class="lx-art-breadcrumb" aria-label="breadcrumb">

        {{-- HOME --}}
        <span class="lx-art-item is-home">
            <span class="lx-art-label">TRANG CHỦ</span>
        </span>

        @foreach($breadcrumbs as $crumb)
            {{-- CONNECTOR ICON --}}
            <span class="lx-art-connector">
                ⟡
            </span>

            {{-- ITEM --}}
            <span class="lx-art-item {{ empty($crumb['url']) ? 'is-active' : '' }}">
                <span class="lx-art-label">
                    {{ Str::upper($crumb['name']) }}
                </span>
            </span>
        @endforeach

    </nav>
@endif
