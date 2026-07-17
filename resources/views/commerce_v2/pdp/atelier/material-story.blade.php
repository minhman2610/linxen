@php
    $materials = (array) data_get($pdp, 'product_truth.materials', []);
    $mainFamilies = collect((array) data_get($materials, 'main', []))
        ->map(fn ($item) => trim((string) data_get($item, 'family_name')))
        ->filter()
        ->unique()
        ->take(4)
        ->values();
    $liningFamilies = collect((array) data_get($materials, 'lining', []))
        ->map(fn ($item) => trim((string) data_get($item, 'family_name')))
        ->filter()
        ->unique()
        ->take(3)
        ->values();
    $customerItems = collect((array) data_get($materials, 'section.items', []))
        ->filter(fn ($item) => trim((string) data_get($item, 'value')) !== '')
        ->take(4)
        ->values();
    $careItems = collect((array) data_get($pdp, 'product_truth.care.items', []))
        ->filter(fn ($item) => trim((string) data_get($item, 'value')) !== '')
        ->take(4)
        ->values();
    $layerLabel = trim((string) data_get($materials, 'layer_label'));
    $layerLabel = trim(str_ireplace([' theo BOM', 'BOM'], '', $layerLabel));
@endphp

@if($mainFamilies->isNotEmpty() || $liningFamilies->isNotEmpty() || $customerItems->isNotEmpty())
    <div class="lxa-material" data-lxa-reveal>
        <div class="lxa-material__statement">
            <p class="lxa-kicker lxa-kicker--light">Material character</p>
            <h2>Chất liệu quyết định cách một thiết kế chuyển động.</h2>
            <p>
                LIN XÉN chỉ hiển thị những thông tin đã có nguồn cho riêng sản phẩm này — đủ để bạn hiểu cấu tạo, không biến trang mua hàng thành một bảng vật tư kỹ thuật.
            </p>
        </div>

        <div class="lxa-material__cards">
            @if($mainFamilies->isNotEmpty())
                <article class="lxa-material-card is-main">
                    <span>01</span>
                    <p>Vải chính</p>
                    <h3>{{ $mainFamilies->implode(' · ') }}</h3>
                    <i aria-hidden="true"></i>
                </article>
            @endif

            @if($liningFamilies->isNotEmpty() || $layerLabel !== '')
                <article class="lxa-material-card is-lining">
                    <span>02</span>
                    <p>Cấu tạo</p>
                    <h3>{{ $layerLabel !== '' ? $layerLabel : $liningFamilies->implode(' · ') }}</h3>
                    @if($liningFamilies->isNotEmpty())
                        <small>Lớp trong: {{ $liningFamilies->implode(' · ') }}</small>
                    @endif
                    <i aria-hidden="true"></i>
                </article>
            @endif

            @foreach($customerItems as $index => $item)
                <article class="lxa-material-card is-fact">
                    <span>{{ str_pad((string) ($index + 3), 2, '0', STR_PAD_LEFT) }}</span>
                    <p>{{ data_get($item, 'label') }}</p>
                    <h3>{{ data_get($item, 'value') }}</h3>
                    <i aria-hidden="true"></i>
                </article>
            @endforeach
        </div>

        @if($careItems->isNotEmpty())
            <div class="lxa-care-line">
                <strong>Bảo quản</strong>
                @foreach($careItems as $item)
                    <span>{{ data_get($item, 'value') }}</span>
                @endforeach
            </div>
        @endif
    </div>
@endif
