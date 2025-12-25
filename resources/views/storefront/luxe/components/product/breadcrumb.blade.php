{{-- =====================================================
   PRODUCT BREADCRUMB – FLORAL (HOME + CATEGORY)
===================================================== --}}
@if(!empty($breadcrumbs))
    <div class="lx-floral-breadcrumb">

        {{-- HOA TRANG TRÍ --}}
        <span class="lx-floral-decor">❀ ❀ ❀</span>

        {{-- TEXT (ONE LINE) --}}
        <span class="lx-floral-text">
            Trang chủ · {{ collect($breadcrumbs)->pluck('name')->join(' · ') }}
        </span>

    </div>
@endif
