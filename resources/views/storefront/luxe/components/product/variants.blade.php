@if(!empty($attributes))

@php
    /*
    |--------------------------------------------------------------------------
    | 🔑 MAP SIZE → PRODUCT_ID (SKU CON) TỪ VARIANTS
    |--------------------------------------------------------------------------
    | ERP trả SKU con trong $variants, không nằm trong $attributes
    */
    $sizeProductMap = [];

    foreach ($variants ?? [] as $variant) {
        // tuỳ ERP, có thể là 'Size' hoặc 'size'
        $sizeValue =
            $variant['attributes']['Size']
            ?? $variant['attributes']['size']
            ?? null;

        if ($sizeValue) {
            $sizeProductMap[$sizeValue] =
                $variant['product_id']
                ?? $variant['id']
                ?? null;
        }
    }
@endphp

<div class="lx-product-variants" id="lxVariants">

    @foreach($attributes as $attr => $values)
        @php
            $attrKey    = Str::slug($attr, '_');
            $isSizeAttr = Str::lower($attr) === 'size';
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
                        {{-- ✅ SIZE VARIANT – GẮN SKU CON --}}
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
                        {{-- OTHER VARIANTS (COLOR, MATERIAL...) --}}
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
