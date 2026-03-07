@if(!empty($attributes))

@php
/*
|--------------------------------------------------------------------------
| 🔑 MAP SIZE → PRODUCT_ID (SKU CON)
|--------------------------------------------------------------------------
| Debug version
*/

$sizeProductMap = [];

foreach ($variants ?? [] as $variant) {

    if (!empty($variant['is_master'])) {
        continue;
    }

    $size = null;

    $attrs = $variant['attributes'] ?? [];

    if (is_array($attrs)) {

        foreach ($attrs as $k => $v) {

            // CASE attributes = ["SIZE"=>"S"]
            if (!is_array($v)) {

                if (mb_strtoupper(trim($k)) === 'SIZE') {
                    $size = trim($v);
                    break;
                }

            }
            // CASE attributes = [{name,value}]
            else {

                $name  = mb_strtoupper(trim($v['name'] ?? ''));
                $value = trim($v['value'] ?? '');

                if ($name === 'SIZE') {
                    $size = $value;
                    break;
                }

            }

        }

    }

    if ($size && !empty($variant['product_id'])) {
        $sizeProductMap[$size] = $variant['product_id'];
    }

}
@endphp


{{-- ================= DEBUG BLOCK ================= --}}
<pre style="background:#111;color:#0f0;padding:12px;font-size:12px;overflow:auto;">
SIZE MAP:
{{ json_encode($sizeProductMap, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}
</pre>

<pre style="background:#000;color:#fff;padding:12px;font-size:12px;overflow:auto;">
VARIANTS RAW:
{{ json_encode($variants, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}
</pre>
{{-- ================================================= --}}


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

@if($isSizeAttr)

<button
type="button"
class="variant-option"
data-attr="{{ $attr }}"
data-attr-key="{{ $attrKey }}"
data-value="{{ $val }}"
data-product-id="{{ $sizeProductMap[$val] ?? '' }}"
>

{{ $val }}

{{-- DEBUG ID --}}
<span style="font-size:10px;color:#888;">
({{ $sizeProductMap[$val] ?? 'NO_ID' }})
</span>

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