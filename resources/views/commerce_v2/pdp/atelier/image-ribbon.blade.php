@php
    $defaultColor = (array) data_get($pdp, 'commerce.default_color', []);
    $media = collect((array) data_get($defaultColor, 'media', []))->take(5);
    $angleLabel = static fn ($item): string => \Illuminate\Support\Str::squish(
        (string) (data_get($item, 'angle_label') ?: data_get($item, 'shot_angle_label') ?: 'Ảnh đã duyệt')
    );
@endphp

@if($media->isNotEmpty())
    <div class="lxa-film" data-lxa-film data-lxa-reveal aria-label="Chuỗi hình ảnh sản phẩm">
        @foreach($media as $index => $item)
            @php $label = $angleLabel($item); @endphp
            <figure class="lxa-film__item {{ $index === 0 ? 'is-lead' : '' }}" data-lxa-film-item>
                <img
                    src="{{ data_get($item, 'url') }}"
                    alt="{{ data_get($pdp, 'identity.name') }} — {{ $label }}"
                    loading="{{ $index < 2 ? 'eager' : 'lazy' }}"
                    decoding="async"
                >
                <figcaption>
                    <span>{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                    <strong>{{ $label }}</strong>
                </figcaption>
            </figure>
        @endforeach
    </div>
@endif
