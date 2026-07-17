@php
    $facts = collect((array) data_get($pdp, 'product_truth.design.items', []))
        ->concat((array) data_get($pdp, 'product_truth.highlights', []))
        ->map(fn ($item) => [
            'label' => trim((string) data_get($item, 'label')),
            'value' => trim((string) data_get($item, 'value')),
        ])
        ->filter(fn ($item) => data_get($item, 'label') !== '' && data_get($item, 'value') !== '')
        ->unique(fn ($item) => \Illuminate\Support\Str::lower((string) data_get($item, 'label').'|'.data_get($item, 'value')))
        ->take(3)
        ->values();
    $media = collect((array) data_get($pdp, 'commerce.default_color.media', []))->filter(fn ($item) => data_get($item, 'url'))->values();
@endphp

@if($facts->isNotEmpty() && $media->isNotEmpty())
<div class="lxs-shell lxs-benefits" data-lxs-reveal>
    <div class="lxs-section-heading lxs-section-heading--split">
        <div>
            <p class="lxs-kicker">Ba góc nhìn</p>
            <h2>Mỗi chi tiết đều có lý do để xuất hiện.</h2>
        </div>
        <p>Ảnh và thông tin được đặt cạnh nhau để bạn hiểu sản phẩm bằng cả thị giác lẫn dữ liệu.</p>
    </div>

    <div class="lxs-benefits__grid">
        @foreach($facts as $index => $fact)
            @php $image = (array) ($media->get($index + 1) ?: $media->get($index) ?: $media->first()); @endphp
            <article class="lxs-benefit-card lxs-benefit-card--{{ $index + 1 }}">
                <img src="{{ data_get($image, 'url') }}" alt="" loading="lazy" decoding="async">
                <div>
                    <small>{{ data_get($fact, 'label') }}</small>
                    <h3>{{ data_get($fact, 'value') }}</h3>
                </div>
            </article>
        @endforeach
    </div>
</div>
@endif
