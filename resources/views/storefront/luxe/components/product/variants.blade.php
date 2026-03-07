@if(!empty($attributes))

@php
/*
|--------------------------------------------------------------------------
| 🔑 MAP SIZE → PRODUCT_ID
|--------------------------------------------------------------------------
| Fix:
| - Không loại master SKU
| - Normalize SIZE
| - Hỗ trợ key SIZE / Size / size
*/

$sizeProductMap = [];

foreach ($variants ?? [] as $variant) {

    $size = null;

    foreach (($variant['attributes'] ?? []) as $key => $value) {

        if (mb_strtoupper(trim($key)) === 'SIZE') {
            $size = mb_strtoupper(trim($value));
            break;
        }

    }

    if ($size && !empty($variant['product_id'])) {
        $sizeProductMap[$size] = $variant['product_id'];
    }
}
@endphp


<div class="lx-product-variants" id="lxVariants">

    @foreach($attributes as $attr => $values)

    @php
    $attrKey = Str::slug($attr,'_');
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

        @php
        $normalizedVal = mb_strtoupper(trim($val));
        @endphp

        @if($isSizeAttr)

        {{-- SIZE – SKU --}}
        <button
        type="button"
        class="variant-option"
        data-attr="{{ $attr }}"
        data-attr-key="{{ $attrKey }}"
        data-value="{{ $val }}"
        data-product-id="{{ $sizeProductMap[$normalizedVal] ?? '' }}"
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