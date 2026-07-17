@php
    $facts = collect((array) data_get($pdp, 'product_truth.highlights', []))
        ->concat((array) data_get($pdp, 'product_truth.design.items', []))
        ->map(fn ($item) => [
            'label' => trim((string) data_get($item, 'label')),
            'value' => trim((string) data_get($item, 'value')),
        ])
        ->filter(fn ($item) => data_get($item, 'label') !== '' && data_get($item, 'value') !== '')
        ->unique(fn ($item) => \Illuminate\Support\Str::lower((string) data_get($item, 'label')))
        ->take(4)
        ->values();
    $media = collect((array) data_get($pdp, 'commerce.default_color.media', []))->values();
    $visual = (array) ($media->get(1) ?: $media->first() ?: []);
    $positions = [
        ['x' => 52, 'y' => 18],
        ['x' => 38, 'y' => 42],
        ['x' => 57, 'y' => 62],
        ['x' => 46, 'y' => 82],
    ];
@endphp

@if($facts->isNotEmpty() && data_get($visual, 'url'))
<div class="lxs-shell lxs-design-explorer" data-lxs-reveal>
    <div class="lxs-section-heading">
        <p class="lxs-kicker">Khám phá thiết kế</p>
        <h2>Chạm vào từng điểm để xem điều làm nên phom dáng.</h2>
        <p>Không cần đọc một đoạn mô tả dài. Mỗi điểm đánh dấu dẫn bạn đến đúng chi tiết đáng chú ý.</p>
    </div>

    <div class="lxs-design-explorer__layout">
        <figure class="lxs-design-explorer__visual">
            <img
                src="{{ data_get($visual, 'url') }}"
                alt="{{ data_get($pdp, 'identity.name') }} - chi tiết thiết kế"
                loading="lazy"
                decoding="async"
                data-lxs-design-image
            >
            @foreach($facts as $index => $fact)
                @php $position = $positions[$index] ?? $positions[0]; @endphp
                <button
                    type="button"
                    class="lxs-hotspot {{ $index === 0 ? 'is-active' : '' }}"
                    style="--lxs-hotspot-x:{{ $position['x'] }}%;--lxs-hotspot-y:{{ $position['y'] }}%;"
                    data-lxs-hotspot="{{ $index }}"
                    aria-label="{{ data_get($fact, 'label') }}: {{ data_get($fact, 'value') }}"
                    aria-pressed="{{ $index === 0 ? 'true' : 'false' }}"
                ><span>{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span></button>
            @endforeach
        </figure>

        <div class="lxs-design-explorer__cards">
            @foreach($facts as $index => $fact)
                <article class="lxs-design-card {{ $index === 0 ? 'is-active' : '' }}" data-lxs-hotspot-card="{{ $index }}">
                    <span>{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                    <small>{{ data_get($fact, 'label') }}</small>
                    <h3>{{ data_get($fact, 'value') }}</h3>
                    <p>Xem trực tiếp trên hình ảnh để cảm nhận vị trí và tỷ lệ của chi tiết này.</p>
                </article>
            @endforeach
        </div>
    </div>
</div>
@endif
