@php
    $identity = (array) data_get($pdp, 'identity', []);
    $commerce = (array) data_get($pdp, 'commerce', []);
    $studies = collect((array) data_get(
        $pdp,
        'media.product_study_by_color',
        []
    ))->values();
    $defaultColorId = (string) data_get(
        $commerce,
        'default_color_id',
        data_get($commerce, 'default_color.id')
    );
    $defaultStudy = (array) (
        $studies->firstWhere('color_id', $defaultColorId)
        ?: $studies->first(fn ($study) => ! empty(data_get($study, 'items')))
        ?: $studies->first()
        ?: []
    );
    $defaultItems = collect(
        (array) data_get($defaultStudy, 'items', [])
    )->values();
    $factItem = static function ($item): array {
        return [
            'label' => \Illuminate\Support\Str::squish((string) data_get(
                $item,
                'label',
                data_get($item, 'key', '')
            )),
            'value' => \Illuminate\Support\Str::squish((string) data_get(
                $item,
                'value',
                data_get($item, 'display_value', '')
            )),
        ];
    };
    $facts = collect((array) data_get($pdp, 'product_truth.design.items', []))
        ->concat((array) data_get($pdp, 'fit.fit_items', []))
        ->concat((array) data_get($pdp, 'product_truth.raw_specs', []))
        ->map($factItem)
        ->filter(fn ($item) => $item['label'] !== '' && $item['value'] !== '')
        ->unique('label')
        ->take(8)
        ->values();
    $materialValue = static fn ($item): string => \Illuminate\Support\Str::squish(
        is_array($item)
            ? (string) (
                data_get($item, 'family_name')
                ?: data_get($item, 'name')
                ?: data_get($item, 'label')
                ?: data_get($item, 'value', '')
            )
            : (string) $item
    );
    $materials = (array) data_get($pdp, 'product_truth.materials', []);
    $mainMaterials = collect((array) data_get($materials, 'main', []))
        ->map($materialValue)
        ->filter()
        ->unique(fn (string $material) => \Illuminate\Support\Str::lower($material))
        ->values();
    $liningMaterials = collect((array) data_get($materials, 'lining', []))
        ->map($materialValue)
        ->filter()
        ->unique(fn (string $material) => \Illuminate\Support\Str::lower($material))
        ->values();
    $materialSummary = \Illuminate\Support\Str::squish((string) (
        data_get($materials, 'layer_label')
        ?: data_get($materials, 'section.message')
    ));
    $materialSummary = str_ireplace(' theo BOM', '', $materialSummary);
    $sizeChart = (array) data_get($pdp, 'fit.garment_size_chart', []);
    $sizeOrder = ['XXS', 'XS', 'S', 'M', 'L', 'XL', 'XXL', '3XL'];
    $chartSizes = collect((array) data_get($sizeChart, 'sizes', []))
        ->map(fn ($size) => strtoupper(trim((string) $size)))
        ->filter()
        ->unique()
        ->sortBy(fn ($size) => array_search($size, $sizeOrder, true) === false
            ? 99
            : array_search($size, $sizeOrder, true))
        ->values();
    $chartPoints = collect((array) data_get(
        $sizeChart,
        'points',
        data_get($sizeChart, 'measurement_rows', [])
    ))
        ->filter(fn ($point) => trim((string) data_get($point, 'label')) !== '')
        ->values();
    $sizeHighlights = $chartPoints
        ->sortBy(fn ($point) => array_search(
            (string) data_get($point, 'code'),
            ['bust', 'waist', 'hip', 'dress_length_from_shoulder', 'length'],
            true
        ) === false ? 99 : array_search(
            (string) data_get($point, 'code'),
            ['bust', 'waist', 'hip', 'dress_length_from_shoulder', 'length'],
            true
        ))
        ->take(4)
        ->values();
    $sizeRecommendations = collect((array) data_get(
        $pdp,
        'fit.advisor_evidence.basic.recommendations',
        []
    ))
        ->filter(fn ($recommendation) => trim((string) data_get($recommendation, 'size')) !== '')
        ->sortBy(fn ($recommendation) => array_search(
            strtoupper(trim((string) data_get($recommendation, 'size'))),
            $sizeOrder,
            true
        ) === false ? 99 : array_search(
            strtoupper(trim((string) data_get($recommendation, 'size'))),
            $sizeOrder,
            true
        ))
        ->values();
    $relatedProducts = collect((array) data_get(
        $pdp,
        'discovery.related_products',
        []
    ))
        ->filter(fn ($item) => data_get($item, 'url') && data_get($item, 'cover_url'))
        ->take(4)
        ->values();
    $jsonFlags = JSON_HEX_TAG
        | JSON_HEX_APOS
        | JSON_HEX_AMP
        | JSON_HEX_QUOT
        | JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES;
@endphp

<div
    class="lxl-study"
    data-lxl-product-study
>
    <div class="lxl-study__shell">
        <header class="lxl-study__header">
            <div>
                <p class="lxl-study__eyebrow">Cảm nhận thiết kế</p>
                <h2>Đẹp ở từng góc nhìn</h2>
            </div>
            <div class="lxl-study__intro">
                <span data-lxl-study-color>
                    {{ data_get($defaultStudy, 'color_label', 'Màu đang chọn') }}
                </span>
                <p>
                    Cùng ngắm phom dáng, chất liệu và những chi tiết khiến thiết kế này trở nên khác biệt.
                </p>
            </div>
        </header>

        @if($facts->isNotEmpty() || $mainMaterials->isNotEmpty() || $liningMaterials->isNotEmpty() || $materialSummary !== '')
            <section class="lxl-knowledge" aria-label="Điểm nhấn thiết kế và chất liệu">
                @if($facts->isNotEmpty())
                    <article class="lxl-knowledge__card">
                        <p>Điểm nhấn thiết kế</p>
                        <dl>
                            @foreach($facts as $fact)
                                <div>
                                    <dt>{{ $fact['label'] }}</dt>
                                    <dd>{{ $fact['value'] }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </article>
                @endif

                @if($mainMaterials->isNotEmpty() || $liningMaterials->isNotEmpty() || $materialSummary !== '')
                    <article class="lxl-knowledge__card lxl-knowledge__card--materials">
                        <p>Chất liệu & cảm giác mặc</p>
                        @if($materialSummary !== '')
                            <strong>{{ $materialSummary }}</strong>
                        @endif
                        <div>
                            @if($mainMaterials->isNotEmpty())
                                <span><small>Vải chính</small>{{ $mainMaterials->implode(' · ') }}</span>
                            @endif
                            @if($liningMaterials->isNotEmpty())
                                <span><small>Lớp lót</small>{{ $liningMaterials->implode(' · ') }}</span>
                            @endif
                        </div>
                    </article>
                @endif
            </section>
        @endif

        @if($chartSizes->isNotEmpty() && $chartPoints->isNotEmpty())
            <section class="lxl-size-chart" aria-labelledby="lxlSizeChartTitle">
                <header class="lxl-size-chart__header">
                    <div>
                        <p>Chọn size thật dễ</p>
                        <h3 id="lxlSizeChartTitle">Bảng thông số theo size</h3>
                    </div>
                    <span>{{ data_get($sizeChart, 'measurement_type') === 'garment' ? 'Đơn vị cm' : 'Thông số sản phẩm' }}</span>
                </header>

                <div class="lxl-size-chart__cards" aria-label="Tóm tắt thông số theo size">
                    @foreach($chartSizes as $size)
                        <article class="lxl-size-chart__card">
                            <strong>{{ $size }}</strong>
                            <dl>
                                @foreach($sizeHighlights as $point)
                                    @php
                                        $display = data_get($point, 'display_values.' . $size, data_get($point, 'values.' . $size));
                                    @endphp
                                    @if($display !== null && $display !== '')
                                        <div>
                                            <dt>{{ data_get($point, 'label') }}</dt>
                                            <dd>{{ $display }}{{ data_get($point, 'unit') ? ' ' . data_get($point, 'unit') : '' }}</dd>
                                        </div>
                                    @endif
                                @endforeach
                            </dl>
                        </article>
                    @endforeach
                </div>

                @if($sizeRecommendations->isNotEmpty())
                    <section class="lxl-size-chart__advice" aria-labelledby="lxlSizeAdviceTitle">
                        <header>
                            <div>
                                <p>Gợi ý nhanh</p>
                                <h4 id="lxlSizeAdviceTitle">Chọn size theo chiều cao &amp; cân nặng</h4>
                            </div>
                            <span>Để tham khảo</span>
                        </header>
                        <div class="lxl-size-chart__advice-grid">
                            @foreach($sizeRecommendations as $recommendation)
                                <article>
                                    <strong>Size {{ data_get($recommendation, 'size') }}</strong>
                                    <dl>
                                        <div>
                                            <dt>Chiều cao</dt>
                                            <dd>{{ data_get($recommendation, 'height_range', '—') }} cm</dd>
                                        </div>
                                        <div>
                                            <dt>Cân nặng</dt>
                                            <dd>{{ data_get($recommendation, 'weight_range', '—') }} kg</dd>
                                        </div>
                                    </dl>
                                    @if(data_get($recommendation, 'body_summary'))
                                        <p>{{ data_get($recommendation, 'body_summary') }}</p>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                        <button type="button" data-lxpdp-size-advisor-open>
                            Nhập số đo để nhận gợi ý chính xác hơn
                        </button>
                    </section>
                @endif

                <div class="lxl-size-chart__table-wrap" tabindex="0">
                    <table>
                        <thead>
                            <tr>
                                <th scope="col">Thông số</th>
                                @foreach($chartSizes as $size)
                                    <th scope="col">{{ $size }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($chartPoints as $point)
                                <tr>
                                    <th scope="row">
                                        {{ data_get($point, 'label') }}
                                        @if(data_get($point, 'unit'))
                                            <small>{{ data_get($point, 'unit') }}</small>
                                        @endif
                                    </th>
                                    @foreach($chartSizes as $size)
                                        @php
                                            $display = data_get($point, 'display_values.' . $size, data_get($point, 'values.' . $size));
                                        @endphp
                                        <td>{{ $display ?? '—' }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="lxl-size-chart__mobile-details" aria-label="Thông số đầy đủ theo từng size">
                    @foreach($chartSizes as $size)
                        <article>
                            <strong>Size {{ $size }}</strong>
                            <dl>
                                @foreach($chartPoints as $point)
                                    @php
                                        $display = data_get($point, 'display_values.' . $size, data_get($point, 'values.' . $size));
                                    @endphp
                                    <div>
                                        <dt>{{ data_get($point, 'label') }}</dt>
                                        <dd>{{ $display ?? '—' }}{{ data_get($point, 'unit') ? ' ' . data_get($point, 'unit') : '' }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </article>
                    @endforeach
                </div>

                @if(data_get($sizeChart, 'message'))
                    <p class="lxl-size-chart__note">Số đo là kích thước thành phẩm; hãy so sánh với một chiếc váy bạn đang mặc vừa để chọn size thoải mái nhất.</p>
                @endif
            </section>
        @endif

        <nav
            class="lxl-study__nav"
            data-lxl-study-nav
            aria-label="Các góc ảnh sản phẩm"
            hidden
        >
            @foreach($defaultItems as $index => $item)
                <button
                    type="button"
                    data-lxl-study-jump="{{ $index }}"
                    aria-label="Đi tới {{ data_get($item, 'angle_label') }}"
                >
                    <span>{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                    {{ data_get($item, 'angle_label') }}
                </button>
            @endforeach
        </nav>

        <div
            class="lxl-study__list"
            data-lxl-study-list
            @if($defaultItems->isEmpty()) hidden @endif
        >
            @foreach($defaultItems as $index => $item)
                        @php $hero = (array) data_get($item, 'hero', []); @endphp
                <article
                    class="lxl-study-card lxl-study-card--{{ ($index % 4) + 1 }}"
                    data-lxl-study-item="{{ $index }}"
                >
                    <figure class="lxl-study-card__media">
                        <img
                            src="{{ data_get($hero, 'url') }}"
                            alt="{{ data_get($identity, 'name') }} — {{ data_get($defaultStudy, 'color_label') }} — {{ data_get($item, 'angle_label') }}"
                            loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                            decoding="async"
                        >
                    </figure>

                    <div class="lxl-study-card__copy">
                        <small>Góc nhìn sản phẩm</small>
                        <h3>{{ data_get($item, 'angle_label') }}</h3>

                    </div>
                </article>
            @endforeach
        </div>

        <div
            class="lxl-study__empty"
            data-lxl-study-empty
            role="status"
            @if($defaultItems->isNotEmpty()) hidden @endif
        >
            <svg viewBox="0 0 48 48" aria-hidden="true">
                <path d="M8 11h32v26H8z"/>
                <circle cx="18" cy="21" r="4"/>
                <path d="m12 33 8-8 6 5 5-4 5 7"/>
            </svg>
            <div>
                <strong>Hình ảnh màu này đang được hoàn thiện</strong>
                <p>
                    LIN XÉN không dùng ảnh của màu khác để thay thế, để bạn luôn xem đúng những gì mình chọn.
                </p>
            </div>
        </div>

        @if($relatedProducts->isNotEmpty())
            <section class="lxl-related" aria-labelledby="lxlRelatedTitle">
                <header>
                    <p>Cùng nhịp thiết kế</p>
                    <h3 id="lxlRelatedTitle">Có thể bạn sẽ thích</h3>
                </header>
                <div class="lxl-related__grid">
                    @foreach($relatedProducts as $related)
                        <a href="{{ data_get($related, 'url') }}">
                            <img
                                src="{{ data_get($related, 'cover_url') }}"
                                alt="{{ data_get($related, 'name') }}"
                                loading="lazy"
                                decoding="async"
                            >
                            <span>{{ data_get($related, 'name') }}</span>
                            <strong>{{ number_format((float) data_get($related, 'price_min'), 0, ',', '.') }}₫</strong>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    <script type="application/json" data-lxl-study-data>{!! json_encode($studies->all(), $jsonFlags) !!}</script>
</div>
