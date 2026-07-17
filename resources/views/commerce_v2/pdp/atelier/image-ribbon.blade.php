@php
    $defaultColor = (array) data_get($pdp, 'commerce.default_color', []);
    $media = collect((array) data_get($defaultColor, 'media', []))->take(5);
    $roleLabels = [
        'hero' => 'Tổng thể',
        'front' => 'Mặt trước',
        'side' => 'Góc nghiêng',
        'back' => 'Mặt sau',
        'detail' => 'Chi tiết',
        'lifestyle' => 'Trên người mẫu',
    ];
@endphp

@if($media->isNotEmpty())
    <div class="lxa-film" data-lxa-film data-lxa-reveal aria-label="Chuỗi hình ảnh sản phẩm">
        @foreach($media as $index => $item)
            @php $role = (string) data_get($item, 'role'); @endphp
            <figure class="lxa-film__item {{ $index === 0 ? 'is-lead' : '' }}" data-lxa-film-item>
                <img
                    src="{{ data_get($item, 'url') }}"
                    alt="{{ data_get($pdp, 'identity.name') }} — {{ $roleLabels[$role] ?? 'Hình ảnh sản phẩm' }}"
                    loading="{{ $index < 2 ? 'eager' : 'lazy' }}"
                    decoding="async"
                >
                <figcaption>
                    <span>{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                    <strong>{{ $roleLabels[$role] ?? 'Chi tiết sản phẩm' }}</strong>
                </figcaption>
            </figure>
        @endforeach
    </div>
@endif
