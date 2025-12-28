@if(!empty($attributes))

@php
    /*
    |--------------------------------------------------------------------------
    | 🔑 MAP SIZE → PRODUCT_ID (SKU CON) TỪ VARIANTS ERP
    |--------------------------------------------------------------------------
    | ERP trả attributes dạng mảng [{name, value}]
    */
    $sizeProductMap = [];

    foreach ($variants ?? [] as $variant) {
        $variantProductId = $variant['product_id']
            ?? $variant['id']
            ?? null;

        foreach ($variant['attributes'] ?? [] as $attr) {
            if (
                isset($attr['name'], $attr['value'])
                && mb_strtoupper($attr['name']) === 'SIZE'
            ) {
                $sizeProductMap[$attr['value']] = $variantProductId;
            }
        }
    }
@endphp

<div class="lx-product-variants" id="lxVariants">

    @foreach($attributes as $attr => $values)
        @php
            $attrKey    = Str::slug($attr, '_');
            $isSizeAttr = mb_strtoupper($attr) === 'SIZE';
        @endphp

        <div class="lx-variant-row"
             data-attr="{{ $attr }}"
             data-attr-key="{{ $attrKey }}">

            <div class="lx-variant-label">
                {{ Str::upper($attr) }}
            </div>

            <div class="lx-variant-options">
                @foreach($values as $val)

                    @if($isSizeAttr)
                        {{-- ✅ SIZE – GẮN SKU CON ĐÚNG --}}
                        <button
                            type="button"
                            class="variant-option"
                            data-attr="{{ $attr }}"
                            data-attr-key="{{ $attrKey }}"
                            data-value="{{ $val }}"
                            data-product-id="{{ $sizeProductMap[$val] ?? '' }}"
                        >
                            {{ $val }}
                        </button>
                    @else
                        {{-- OTHER VARIANTS --}}
                        <button
                            type="button"
                            class="variant-option"
                            data-attr="{{ $attr }}"
                            data-attr-key="{{ $attrKey }}"
                            data-value="{{ $val }}"
                        >
                            {{ $val }}
                        </button>
                    @endif

                @endforeach
            </div>

        </div>
    @endforeach

</div>
@endif
