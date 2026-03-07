<section class="lx-ai-suggested">

    <div class="lx-ai-head">

        <h3>
            Có thể bạn sẽ thích chiếc váy này hơn
        </h3>

        <p>
            Những mẫu khách LIN XÉN thường chọn khi xem thiết kế này
        </p>

    </div>


    <div class="lx-ai-scroll">

        @foreach($suggestedProducts as $item)

        @php
        $url   = $item['url'] ?? '#';
        $name  = $item['name'] ?? '';
        $price = $item['price'] ?? null;

        $thumb = $item['thumb_mobile']
        ?? $item['thumb']
        ?? asset('images/no-image.png');
        @endphp


        <a href="{{ $url }}" class="lx-ai-card">

            <div class="lx-ai-image">

                <img
                src="{{ $thumb }}"
                alt="{{ $name }}"
                loading="lazy">

                @if(!empty($item['badge']))
                <span class="lx-ai-badge">
                    {{ $item['badge'] }}
                </span>
                @endif

            </div>


            <div class="lx-ai-info">

                <div class="lx-ai-name">
                    {{ $name }}
                </div>

                @if($price)
                <div class="lx-ai-price">
                    {{ number_format($price) }}₫
                </div>
                @endif

            </div>

        </a>

        @endforeach

    </div>

</section>