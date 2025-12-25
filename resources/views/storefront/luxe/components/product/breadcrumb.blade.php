{{-- =====================================================
   PRODUCT BREADCRUMB – LUXURY EDITORIAL
===================================================== --}}
@if(!empty($breadcrumbs))
    <nav class="lx-luxury-breadcrumb" aria-label="breadcrumb">
        <span class="lx-luxury-text">
            Trang chủ
            @foreach($breadcrumbs as $crumb)
                <span class="lx-luxury-sep">—</span>
                <span class="lx-luxury-item">
                    {{ $crumb['name'] }}
                </span>
            @endforeach
        </span>
    </nav>
@endif
