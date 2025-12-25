{{-- =====================================================
   PRODUCT BREADCRUMB – FLORAL RIBBON
===================================================== --}}
@if(!empty($breadcrumbs))
    <div class="lx-floral-breadcrumb">

        {{-- HOA TRANG TRÍ --}}
        <span class="lx-floral-decor">
            ❀ ❀ ❀
        </span>

        {{-- TEXT --}}
        <span class="lx-floral-text">
            {{ collect($breadcrumbs)->pluck('name')->join('  ·  ') }}
        </span>

    </div>
@endif
