@php
    $chart = (array) data_get($pdp, 'fit.garment_size_chart', []);
    $sizes = collect((array) data_get($chart, 'sizes', []))->take(6)->values();
    $points = collect((array) data_get($chart, 'points', []))->take(8)->values();
    $valueFor = function (array $point, string $size): string {
        $display = data_get($point, 'display_values.'.$size);
        $raw = data_get($point, 'values.'.$size);
        $value = $display !== null && $display !== '' ? $display : $raw;

        return $value !== null && $value !== ''
            ? (string) $value
            : '—';
    };
@endphp

@if(data_get($chart, 'structured') && $sizes->isNotEmpty() && $points->isNotEmpty())
    <div class="lxa-size-story" data-lxa-reveal data-lxpdp-size-chart-structured>
        <div class="lxa-size-story__intro">
            <p class="lxa-kicker">Made to measure</p>
            <h2>Số đo thành phẩm,<br>không phải một phỏng đoán.</h2>
            <p>
                Đặt một sản phẩm đang mặc vừa lên mặt phẳng, đo cùng vị trí rồi so sánh. Cách này giúp bạn hiểu phom thật rõ hơn chỉ nhìn chữ S, M hay L.
            </p>

            <button
                type="button"
                class="lxa-outline-button lxa-outline-button--dark"
                data-lxpdp-size-advisor-open
                @if(!data_get($pdp, 'fit.advisor.enabled')) disabled @endif
            >Đối chiếu với số đo của bạn</button>

            <p class="lxa-source-note">Hồ sơ số đo riêng của mẫu · đã xác minh</p>
        </div>

        <div class="lxa-size-story__table-wrap" tabindex="0" aria-label="Bảng số đo thành phẩm">
            <table class="lxa-size-table">
                <thead>
                    <tr>
                        <th scope="col">Điểm đo</th>
                        @foreach($sizes as $size)
                            <th scope="col">{{ $size }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($points as $point)
                        <tr>
                            <th scope="row">
                                <span>{{ data_get($point, 'label') }}</span>
                                @if(data_get($point, 'note'))
                                    <small>{{ data_get($point, 'note') }}</small>
                                @endif
                            </th>
                            @foreach($sizes as $size)
                                <td>
                                    <strong>{{ $valueFor((array) $point, (string) $size) }}</strong>
                                    <small>{{ data_get($point, 'unit', 'cm') }}</small>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
