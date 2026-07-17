@php
    $identity = (array) data_get($pdp, 'identity', []);
    $items = collect((array) data_get($pdp, 'product_truth.highlights', []));
    if ($items->isEmpty()) {
        $items = collect((array) data_get($pdp, 'product_truth.design.items', []));
    }
@endphp
<div class="lxpdp-story-block lxpdp-story-block--highlights">
    <header class="lxpdp-story-heading">
        <p class="lxpdp-kicker">Điểm chạm đầu tiên</p>
        <h2>Vì sao {{ data_get($identity, 'short_name') ?: data_get($identity, 'name') }} đáng để thử?</h2>
    </header>
    <div class="lxpdp-editorial-highlight-grid">
        @foreach($items->take(5) as $index => $item)
            <article>
                <span>{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                <p>{{ data_get($item, 'label') }}</p>
                <h3>{{ data_get($item, 'value') }}</h3>
            </article>
        @endforeach
    </div>
</div>
