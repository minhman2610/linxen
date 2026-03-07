@if(!empty($attributes))

@php
/*
|--------------------------------------------------------------------------
| 🔑 MAP SIZE → PRODUCT_ID
|--------------------------------------------------------------------------
| Fix:
| - normalize size
| - ignore case
| - trim space
*/

$sizeProductMap = [];

foreach ($variants ?? [] as $variant) {

    if (!empty($variant['is_master'])) {
        continue;
    }

    $size = null;

    foreach (($variant['attributes'] ?? []) as $key => $value) {

        $normalizedKey = mb_strtoupper(trim($key));

        if (in_array($normalizedKey, ['SIZE','KÍCH THƯỚC'])) {

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
$isSizeAttr = in_array(mb_strtoupper($attr),['SIZE','KÍCH THƯỚC']);
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