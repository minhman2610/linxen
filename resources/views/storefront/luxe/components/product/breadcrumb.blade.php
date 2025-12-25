{{-- =====================================================
   PRODUCT BREADCRUMB – COUTURE FRAMES
===================================================== --}}
@if(!empty($breadcrumbs))
    <nav class="lx-couture-breadcrumb" aria-label="breadcrumb">

        {{-- HOME --}}
        <span class="lx-couture-item home">
            <span class="lx-couture-label">Trang chủ</span>
        </span>

        @foreach($breadcrumbs as $crumb)
            {{-- CONNECTOR --}}
            <span class="lx-couture-connector">
                ✦
            </span>

            {{-- ITEM --}}
            <span class="lx-couture-item {{ empty($crumb['url']) ? 'active' : '' }}">
                <span class="lx-couture-label">
                    {{ $crumb['name'] }}
                </span>
            </span>
        @endforeach

    </nav>
@endif
