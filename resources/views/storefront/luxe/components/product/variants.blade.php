@if(!empty($attributes))
<div class="lx-product-variants" id="lxVariants">

    @foreach($attributes as $attr => $values)
        @php $attrKey = Str::slug($attr, '_'); @endphp

        <div class="lx-variant-row"
             data-attr="{{ $attr }}"
             data-attr-key="{{ $attrKey }}">

            <div class="lx-variant-label">
                {{ Str::upper($attr) }}
            </div>

            <div class="lx-variant-options">
                @foreach($values as $val)
                    <button
                        type="button"
                        class="variant-option"
                        data-attr="{{ $attr }}"
                        data-attr-key="{{ $attrKey }}"
                        data-value="{{ $val }}">
                        {{ $val }}
                    </button>
                @endforeach
            </div>

        </div>
    @endforeach

</div>
@endif
