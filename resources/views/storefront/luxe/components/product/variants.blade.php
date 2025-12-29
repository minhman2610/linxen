@if(!empty($attributes))

@php
    /*
    |--------------------------------------------------------------------------
    | 🔑 MAP SIZE → PRODUCT_ID (CHỈ SKU CON)
    |--------------------------------------------------------------------------
    | ERP trả cả SKU cha + con
    | FE chỉ dùng SKU CON cho chọn size
    */
    $sizeProductMap = [];

    foreach ($variants ?? [] as $variant) {
        if (
            empty($variant['is_master'])
            && isset($variant['attributes']['SIZE'], $variant['product_id'])
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
                        {{-- SIZE – SKU CON --}}
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
