@php
    $identity = (array) data_get($pdp, 'identity', []);
    $advisor = (array) data_get($pdp, 'fit.advisor', []);
    $chart = (array) data_get($pdp, 'fit.garment_size_chart', []);
    $sizes = collect((array) data_get($chart, 'sizes', []));
    $points = collect((array) data_get($chart, 'points', []));
    $structured = (bool) data_get($chart, 'structured') && $sizes->isNotEmpty() && $points->isNotEmpty();
@endphp
<div class="lxpdp-story-block lxpdp-size-confidence">
    <header class="lxpdp-story-heading lxpdp-story-heading--split">
        <div>
            <p class="lxpdp-kicker">Chọn size có căn cứ</p>
            <h2>Số đo thành phẩm và tư vấn riêng</h2>
        </div>
        <button type="button" class="lxpdp-secondary-button" data-lxpdp-size-advisor-open @if(!data_get($advisor, 'enabled')) disabled @endif>Nhập số đo của bạn</button>
    </header>

    @if($structured)
        <div class="lxpdp-size-chart__source">
            <strong>Số đo thành phẩm</strong>
            <span>Nguồn: {{ data_get($chart, 'tech_pack.code', 'Tech Pack sản xuất') }} @if(data_get($chart, 'tech_pack.version'))· {{ data_get($chart, 'tech_pack.version') }}@endif</span>
        </div>
        <div class="lxpdp-size-chart__matrix" data-lxpdp-size-chart-structured>
            <div class="lxpdp-size-chart__scroll" tabindex="0">
                <table>
                    <caption class="sr-only">Bảng số đo thành phẩm {{ data_get($identity, 'name') }}</caption>
                    <thead><tr><th scope="col">Điểm đo</th>@foreach($sizes as $size)<th scope="col">{{ $size }}</th>@endforeach</tr></thead>
                    <tbody>
                        @foreach($points as $point)
                            <tr>
                                <th scope="row"><span>{{ data_get($point, 'label') }}</span>@if(data_get($point, 'note'))<small>{{ data_get($point, 'note') }}</small>@endif</th>
                                @foreach($sizes as $size)
                                    @php
                                        $display = data_get($point, 'display_values.'.$size);
                                        $raw = data_get($point, 'values.'.$size);
                                    @endphp
                                    <td>{{ $display !== null && $display !== '' ? $display : ($raw !== null && $raw !== '' ? $raw : '—') }}@if($raw !== null && $raw !== '')<small>{{ data_get($point, 'unit', 'cm') }}</small>@endif</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="lxpdp-size-chart__guidance">{{ data_get($chart, 'comparison_guidance', 'Đây là số đo thành phẩm; hãy so với một sản phẩm đang mặc vừa.') }}</p>
        </div>
    @elseif(data_get($chart, 'image_url'))
        <a class="lxpdp-size-chart__image" href="{{ data_get($chart, 'image_url') }}" target="_blank" rel="noopener"><img src="{{ data_get($chart, 'thumb_url') ?: data_get($chart, 'image_url') }}" alt="Bảng kích thước {{ data_get($identity, 'name') }}" loading="lazy"><span>Mở bảng kích thước</span></a>
    @else
        <p class="lxpdp-size-chart__empty">Bảng số đo riêng cho mẫu này đang được cập nhật.</p>
    @endif
</div>
