@if(!empty($breadcrumbs))
    <nav class="lx-couture-breadcrumb" aria-label="breadcrumb">

        <span class="lx-couture-item home">
            <span class="lx-couture-label">Trang chủ</span>
        </span>

        @foreach($breadcrumbs as $crumb)
            <span class="lx-couture-connector">✦</span>

            <span class="lx-couture-item {{ empty($crumb['url']) ? 'active' : '' }}">
                <span class="lx-couture-label">
                    {{ $crumb['name'] }}
                </span>
            </span>
        @endforeach

    </nav>
@endif
