@php
    $identity = (array) data_get($pdp, 'identity', []);
    $media = collect((array) data_get($pdp, 'media.production_truth', []));
    if ($media->count() < 3) {
        $media = $media
            ->concat((array) data_get($pdp, 'commerce.default_color.media', []))
            ->unique('url')
            ->values();
    }
    $media = $media->take(5);
    $roleLabels = [
        'hero' => 'Tổng thể',
        'front' => 'Mặt trước',
        'side' => 'Góc nghiêng',
        'back' => 'Mặt sau',
        'detail' => 'Chi tiết đường cắt',
        'lifestyle' => 'Trên người mẫu',
    ];
@endphp

@if($media->isNotEmpty())
    <div class="lxa-truth" data-lxa-reveal>
        <header class="lxa-truth__head">
            <div>
                <p class="lxa-kicker">Look closer</p>
                <h2>Xem kỹ trước khi chọn.</h2>
            </div>
            <p>Ảnh tạo cảm hứng giúp bạn hình dung phong cách. Các góc sản phẩm rõ ràng giúp xác nhận phom, mặt sau và chi tiết trước khi mua.</p>
        </header>

        <div class="lxa-mosaic">
            @foreach($media as $index => $item)
                @php $role = (string) data_get($item, 'role'); @endphp
                <figure class="lxa-mosaic__item lxa-mosaic__item--{{ $index + 1 }}">
                    <img
                        src="{{ data_get($item, 'url') }}"
                        alt="{{ data_get($identity, 'name') }} — {{ $roleLabels[$role] ?? 'Chi tiết sản phẩm' }}"
                        loading="lazy"
                        decoding="async"
                    >
                    <figcaption>{{ $roleLabels[$role] ?? (data_get($item, 'shot_angle') ?: 'Chi tiết sản phẩm') }}</figcaption>
                </figure>
            @endforeach
        </div>
    </div>
@endif
