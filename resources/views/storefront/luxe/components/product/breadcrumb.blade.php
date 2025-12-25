{{-- =====================================================
   PRODUCT EDITORIAL CONTEXT (ART BREADCRUMB)
===================================================== --}}
@if(!empty($breadcrumbs))
    <div class="lx-editorial-context">

        {{-- MARKER LINE --}}
        <span class="lx-context-line"></span>

        {{-- CONTEXT TEXT --}}
        <span class="lx-context-text">
            {{ collect($breadcrumbs)->pluck('name')->join(' · ') }}
        </span>

    </div>
@endif
