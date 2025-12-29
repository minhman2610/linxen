@if(!empty($attributes))

@php
    /*
    |--------------------------------------------------------------------------
    | 🔑 MAP SIZE → PRODUCT_ID (SKU CON) TỪ VARIANTS ERP
    |--------------------------------------------------------------------------
    | ERP mới trả:
    | variants = [
    |   [
    |     product_id: xxx,
    |     attributes: [
    |       'SIZE' => 'L',
    |       'MÀU SẮC' => 'Nâu'
    |     ]
    |   ]
    | ]
    |
    | 👉 attributes là associative array, KHÔNG phải [{name,value}]
    */
    $sizeProductMap = [];

    foreach ($variants ?? [] as $variant) {
        if (
            isset($variant['product_id'], $variant['attributes'])
            && is_array($variant['attributes'])
            && isset($variant['attributes']['SIZE'])
        ) {
            $size = $variant['attributes']['SIZE'];
            $sizeProductMap[$size] = $variant['product_id'];
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
                        {{-- =====================================================
                             SIZE VARIANT – SKU CON
                        ===================================================== --}}
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
                        {{-- =====================================================
                             OTHER VARIANTS (COLOR, MATERIAL…)
                        ===================================================== --}}
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
