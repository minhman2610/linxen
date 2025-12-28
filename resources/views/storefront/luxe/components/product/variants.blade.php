@if(!empty($attributes))
<div class="lx-product-variants" id="lxVariants">

    @foreach($attributes as $attr => $values)
        @php
            $attrKey   = Str::slug($attr, '_');
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

                    @if($isSizeAttr && is_array($val))
                        {{-- 🔑 SIZE VARIANT – SKU CON --}}
                        <button
                            type="button"
                            class="variant-option"
                            data-attr="{{ $attr }}"
                            data-attr-key="{{ $attrKey }}"
                            data-value="{{ $val['label'] }}"
                            data-product-id="{{ $val['product_id'] }}"
                        >
                            {{ $val['label'] }}
                        </button>
                    @else
                        {{-- OTHER VARIANTS (COLOR, MATERIAL...) --}}
                        <button
                            type="button"
                            class="variant-option"
                            data-attr="{{ $attr }}"
                            data-attr-key="{{ $attrKey }}"
                            data-value="{{ is_array($val) ? ($val['label'] ?? '') : $val }}"
                        >
                            {{ is_array($val) ? ($val['label'] ?? '') : $val }}
                        </button>
                    @endif

                @endforeach
            </div>

        </div>
    @endforeach

</div>
@endif
