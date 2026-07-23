@php
    $campaign = collect((array) data_get($pdp, 'commerce.default_color.media', []))
        ->filter(fn ($item) => data_get($item, 'url'))
        ->take(6)
        ->values();
    $truth = collect((array) data_get($pdp, 'media.production_truth', []))
        ->filter(fn ($item) => data_get($item, 'url'))
        ->take(6)
        ->values();
    $angleLabel = static fn ($item): string => \Illuminate\Support\Str::squish(
        (string) (data_get($item, 'angle_label') ?: data_get($item, 'shot_angle_label') ?: 'Ảnh đã duyệt')
    );
@endphp

@if($campaign->isNotEmpty() || $truth->isNotEmpty())
<div class="lxs-shell lxs-media-lab" data-lxs-reveal>
    <div class="lxs-section-heading lxs-section-heading--split">
        <div>
            <p class="lxs-kicker">Xem thật kỹ</p>
            <h2>Chuyển giữa cảm hứng và hình ảnh sản phẩm.</h2>
        </div>
        <p>Hai lớp hình ảnh giúp bạn vừa cảm nhận phong cách, vừa kiểm tra những chi tiết thực tế.</p>
    </div>

    <div class="lxs-media-lab__tabs" role="tablist" aria-label="Loại hình ảnh">
        @if($campaign->isNotEmpty())
            <button type="button" class="is-active" data-lxs-media-tab="campaign" role="tab" aria-selected="true">Ảnh cảm hứng</button>
        @endif
        @if($truth->isNotEmpty())
            <button type="button" data-lxs-media-tab="truth" role="tab" aria-selected="{{ $campaign->isEmpty() ? 'true' : 'false' }}">Ảnh sản phẩm thực tế</button>
        @endif
    </div>

    @if($campaign->isNotEmpty())
        <div class="lxs-media-grid is-active" data-lxs-media-panel="campaign" role="tabpanel" data-lxs-campaign-grid>
            @foreach($campaign as $index => $item)
                <figure class="lxs-media-grid__item lxs-media-grid__item--{{ ($index % 5) + 1 }}">
                    <img src="{{ data_get($item, 'url') }}" alt="{{ data_get($pdp, 'identity.name') }}" loading="lazy" decoding="async">
                    <figcaption>{{ $angleLabel($item) }}</figcaption>
                </figure>
            @endforeach
        </div>
    @endif

    @if($truth->isNotEmpty())
        <div class="lxs-media-grid {{ $campaign->isEmpty() ? 'is-active' : '' }}" data-lxs-media-panel="truth" role="tabpanel" @if($campaign->isNotEmpty()) hidden @endif>
            @foreach($truth as $index => $item)
                <figure class="lxs-media-grid__item lxs-media-grid__item--{{ ($index % 5) + 1 }}">
                    <img src="{{ data_get($item, 'url') }}" alt="{{ data_get($pdp, 'identity.name') }} - ảnh thực tế" loading="lazy" decoding="async">
                    <figcaption>{{ $angleLabel($item) }}</figcaption>
                </figure>
            @endforeach
        </div>
    @endif
</div>
@endif
