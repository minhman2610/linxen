@php
    $chart = (array) data_get($pdp, 'fit.garment_size_chart', []);
    $sizes = collect((array) data_get($chart, 'sizes', []))->values();
    $points = collect((array) data_get($chart, 'points', []))->values();
    $pointByCode = $points->keyBy(fn ($point) => \Illuminate\Support\Str::lower((string) data_get($point, 'code')));
    $findPoint = function (array $needles) use ($points): array {
        return (array) ($points->first(function ($point) use ($needles) {
            $blob = \Illuminate\Support\Str::lower(
                (string) data_get($point, 'code').' '.(string) data_get($point, 'label')
            );
            return collect($needles)->contains(fn ($needle) => \Illuminate\Support\Str::contains($blob, $needle));
        }) ?: []);
    };
    $essential = collect([
        'bust' => $findPoint(['bust', 'ngực']),
        'waist' => $findPoint(['waist', 'eo']),
        'hip' => $findPoint(['hip', 'mông', 'hông']),
        'length' => $findPoint(['length', 'dài']),
    ])->filter(fn ($point) => $point !== []);
    $selectedSize = (string) ($sizes->first() ?: '');
    $valueFor = function (array $point, string $size): string {
        $display = data_get($point, 'display_values.'.$size);
        $raw = data_get($point, 'values.'.$size);
        $value = $display !== null && $display !== '' ? $display : $raw;
        return $value !== null && $value !== '' ? (string) $value : '—';
    };
    $lengthPoint = (array) $essential->get('length', []);
    $chartJson = json_encode([
        'sizes' => $sizes->all(),
        'points' => $points->all(),
        'essential_keys' => $essential->keys()->all(),
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
@endphp

@if(data_get($chart, 'structured') && $sizes->isNotEmpty() && $points->isNotEmpty())
<div class="lxs-size-studio" data-lxs-reveal data-lxpdp-size-chart-structured>
    <div class="lxs-shell">
        <div class="lxs-section-heading lxs-section-heading--split lxs-section-heading--light">
            <div>
                <p class="lxs-kicker">Size Studio</p>
                <h2>Nhìn số đo như một bản đồ, không phải một bảng tính.</h2>
            </div>
            <p>Chọn một size để xem các điểm đo chính trên phom sản phẩm. Đây là số đo thành phẩm, dùng để so với một món đồ đang mặc vừa.</p>
        </div>

        <div class="lxs-size-studio__size-cards" role="list" aria-label="Chọn size để xem số đo">
            @foreach($sizes as $index => $size)
                <button
                    type="button"
                    class="{{ $index === 0 ? 'is-active' : '' }}"
                    data-lxs-size-card="{{ $size }}"
                    aria-pressed="{{ $index === 0 ? 'true' : 'false' }}"
                >
                    <strong>{{ $size }}</strong>
                    @if($lengthPoint)
                        <span>{{ $valueFor($lengthPoint, (string) $size) }} {{ data_get($lengthPoint, 'unit', 'cm') }}</span>
                        <small>chiều dài</small>
                    @else
                        <small>Xem số đo</small>
                    @endif
                </button>
            @endforeach
        </div>

        <div class="lxs-size-studio__workspace">
            <div class="lxs-size-figure">
                <svg viewBox="0 0 360 520" role="img" aria-label="Sơ đồ điểm đo sản phẩm">
                    <defs>
                        <linearGradient id="lxsDressFill" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0" stop-color="#ffffff"/>
                            <stop offset="1" stop-color="#dfe3ff"/>
                        </linearGradient>
                    </defs>
                    <path class="lxs-size-figure__dress" d="M136 62c10 18 78 18 88 0l24 42-32 50 55 300H89l55-300-32-50 24-42Z"/>
                    <path class="lxs-size-figure__neck" d="M148 63c7 22 57 22 64 0"/>
                    <path class="lxs-size-figure__seam" d="M136 154h88M115 272h130"/>
                    <g data-lxs-diagram-measure="bust" class="is-active">
                        <path d="M108 164h144"/><circle cx="108" cy="164" r="5"/><circle cx="252" cy="164" r="5"/><text x="262" y="170">A</text>
                    </g>
                    <g data-lxs-diagram-measure="waist">
                        <path d="M126 232h108"/><circle cx="126" cy="232" r="5"/><circle cx="234" cy="232" r="5"/><text x="244" y="238">B</text>
                    </g>
                    <g data-lxs-diagram-measure="hip">
                        <path d="M108 302h144"/><circle cx="108" cy="302" r="5"/><circle cx="252" cy="302" r="5"/><text x="262" y="308">C</text>
                    </g>
                    <g data-lxs-diagram-measure="length">
                        <path d="M82 64v390"/><circle cx="82" cy="64" r="5"/><circle cx="82" cy="454" r="5"/><text x="56" y="266">D</text>
                    </g>
                </svg>
                <p>Chạm vào từng dòng số đo để làm nổi bật vị trí tương ứng.</p>
            </div>

            <div class="lxs-size-values">
                <div class="lxs-size-values__head">
                    <div>
                        <small>Đang xem</small>
                        <h3>Size <span data-lxs-active-size>{{ $selectedSize }}</span></h3>
                    </div>
                    <button type="button" data-lxs-size-table-open>So sánh tất cả size</button>
                </div>

                <div class="lxs-size-values__list">
                    @foreach($essential as $key => $point)
                        <button
                            type="button"
                            class="{{ $loop->first ? 'is-active' : '' }}"
                            data-lxs-measure-row="{{ $key }}"
                        >
                            <span><i>{{ ['bust' => 'A', 'waist' => 'B', 'hip' => 'C', 'length' => 'D'][$key] ?? '•' }}</i>{{ data_get($point, 'label') }}</span>
                            <strong><b data-lxs-measure-value="{{ $key }}">{{ $valueFor((array) $point, $selectedSize) }}</b> {{ data_get($point, 'unit', 'cm') }}</strong>
                        </button>
                    @endforeach
                </div>

                <div class="lxs-size-values__actions">
                    <button
                        type="button"
                        class="lxs-button lxs-button--primary"
                        data-lxpdp-size-advisor-open
                        @if(!data_get($pdp, 'fit.advisor.enabled')) disabled @endif
                    >Kiểm tra size của bạn</button>
                    <p>{{ data_get($chart, 'comparison_guidance') ?: 'So sánh với một sản phẩm cùng loại đang mặc vừa.' }}</p>
                </div>
            </div>
        </div>
    </div>

    <dialog class="lxs-size-dialog" data-lxs-size-table-dialog>
        <form method="dialog"><button type="submit" aria-label="Đóng bảng size">×</button></form>
        <div>
            <p class="lxs-kicker">Bảng số đo thành phẩm</p>
            <h2>So sánh tất cả size</h2>
            <div class="lxs-size-dialog__table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">Điểm đo</th>
                            @foreach($sizes as $size)<th scope="col">{{ $size }}</th>@endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($points as $point)
                            <tr>
                                <th scope="row">
                                    {{ data_get($point, 'label') }}
                                    @if(data_get($point, 'note'))<small>{{ data_get($point, 'note') }}</small>@endif
                                </th>
                                @foreach($sizes as $size)
                                    <td>{{ $valueFor((array) $point, (string) $size) }} <small>{{ data_get($point, 'unit', 'cm') }}</small></td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </dialog>

    <script type="application/json" data-lxs-size-chart-data>{!! $chartJson !!}</script>
</div>
@endif
