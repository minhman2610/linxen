@php
    $main = collect((array) data_get($pdp, 'product_truth.materials.main', []))
        ->map(fn ($item) => trim((string) (data_get($item, 'family_name') ?: data_get($item, 'name'))))
        ->filter()
        ->unique()
        ->values();
    $lining = collect((array) data_get($pdp, 'product_truth.materials.lining', []))
        ->map(fn ($item) => trim((string) (data_get($item, 'family_name') ?: data_get($item, 'name'))))
        ->filter()
        ->unique()
        ->values();
    $facts = collect((array) data_get($pdp, 'product_truth.materials.section.items', []))
        ->map(fn ($item) => [
            'label' => trim((string) data_get($item, 'label')),
            'value' => trim((string) data_get($item, 'value')),
        ])
        ->filter(fn ($item) => data_get($item, 'label') !== '' && data_get($item, 'value') !== '')
        ->unique(fn ($item) => \Illuminate\Support\Str::lower((string) data_get($item, 'label')))
        ->take(6)
        ->values();
    $layer = trim((string) data_get($pdp, 'product_truth.materials.layer_label'));
@endphp

@if($main->isNotEmpty() || $lining->isNotEmpty() || $facts->isNotEmpty() || $layer !== '')
<div class="lxs-material-feel" data-lxs-reveal>
    <div class="lxs-shell">
        <div class="lxs-section-heading lxs-section-heading--split">
            <div>
                <p class="lxs-kicker">Material Feel</p>
                <h2>Chất liệu được nói bằng ngôn ngữ dễ hiểu.</h2>
            </div>
            <p>Tập trung vào cảm giác mặc, cấu tạo lớp và những thông tin thực sự hữu ích khi lựa chọn.</p>
        </div>

        <div class="lxs-material-feel__grid">
            @if($main->isNotEmpty())
                <article class="lxs-material-card lxs-material-card--primary">
                    <span class="lxs-material-card__icon" aria-hidden="true">
                        <svg viewBox="0 0 64 64"><path d="M8 18 32 8l24 10-24 12L8 18Z"/><path d="m8 30 24 13 24-13M8 42l24 14 24-14"/></svg>
                    </span>
                    <small>Vải chính</small>
                    <h3>{{ $main->implode(' · ') }}</h3>
                    <p>Family vật liệu được rút gọn để bạn dễ hình dung cấu tạo chính của sản phẩm.</p>
                </article>
            @endif

            @if($layer !== '' || $lining->isNotEmpty())
                <article class="lxs-material-card">
                    <span class="lxs-material-card__icon" aria-hidden="true">
                        <svg viewBox="0 0 64 64"><path d="M18 9h28l8 46H10L18 9Z"/><path d="M21 19h22M17 33h30"/></svg>
                    </span>
                    <small>Cấu tạo</small>
                    <h3>{{ $layer !== '' ? $layer : 'Có lớp lót' }}</h3>
                    @if($lining->isNotEmpty())
                        <p>Lớp trong: {{ $lining->implode(' · ') }}</p>
                    @endif
                </article>
            @endif

            @foreach($facts->take(4) as $fact)
                <article class="lxs-material-card lxs-material-card--fact">
                    <small>{{ data_get($fact, 'label') }}</small>
                    <h3>{{ data_get($fact, 'value') }}</h3>
                </article>
            @endforeach
        </div>
    </div>
</div>
@endif
