@if(!empty($attributes))

@php
/*
|--------------------------------------------------------------------------
| 🔑 MAP SIZE → PRODUCT_ID + STOCK
|--------------------------------------------------------------------------
| - Normalize SIZE
| - Map product_id
| - Map stock
*/

$sizeProductMap = [];
$sizeStockMap   = [];

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

        $sizeStockMap[$size] = (int)($variant['available'] ?? 0);
    }
}
@endphp


<div class="lx-product-variants" id="lxVariants">

    @foreach($attributes as $attr => $values)

    @php
        $attrKey    = Str::slug($attr,'_');
        $isSizeAttr = mb_strtoupper($attr) === 'SIZE';
    @endphp

    <div
        class="lx-variant-row"
        data-attr="{{ $attr }}"
        data-attr-key="{{ $attrKey }}"
    >

        <div class="lx-variant-label">
            {{ Str::upper($attr) }}
        </div>

        <div class="lx-variant-options">

            @foreach($values as $val)

                @php
                    $normalizedVal = mb_strtoupper(trim($val));
                @endphp

                @if($isSizeAttr)

                    @php
                        $productId = $sizeProductMap[$normalizedVal] ?? null;
                        $stock     = $sizeStockMap[$normalizedVal] ?? 0;
                        $isOut     = $stock <= 0;
                    @endphp

                    {{-- SIZE OPTION --}}
                    <button
                        type="button"
                        class="variant-option {{ $isOut ? 'is-out' : '' }}"
                        data-attr="{{ $attr }}"
                        data-attr-key="{{ $attrKey }}"
                        data-value="{{ $val }}"
                        data-product-id="{{ $productId }}"
                        data-stock="{{ $stock }}"
                        {{ $isOut ? 'disabled' : '' }}
                    >

                        {{ $val }}

                        @if($isOut)
                        <span class="variant-x">×</span>
                        @endif

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