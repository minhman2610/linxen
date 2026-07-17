@php
    $facts = collect((array) data_get($pdp, 'product_truth.highlights', []))
        ->concat((array) data_get($pdp, 'product_truth.design.items', []))
        ->concat((array) data_get($pdp, 'fit.fit_items', []))
        ->concat((array) data_get($pdp, 'product_truth.materials.section.items', []))
        ->map(fn ($item) => [
            'key' => (string) (data_get($item, 'key') ?: data_get($item, 'label')),
            'label' => trim((string) data_get($item, 'label')),
            'value' => trim((string) data_get($item, 'value')),
        ])
        ->filter(fn ($item) => data_get($item, 'label') !== '' && data_get($item, 'value') !== '')
        ->unique(fn ($item) => \Illuminate\Support\Str::lower((string) data_get($item, 'label')))
        ->take(4)
        ->values();
@endphp

@if($facts->isNotEmpty())
<div class="lxs-shell lxs-quick-read" data-lxs-reveal>
    <div class="lxs-section-heading lxs-section-heading--compact">
        <p class="lxs-kicker">Nhìn nhanh</p>
        <h2>Hiểu sản phẩm trong vài giây.</h2>
    </div>

    <div class="lxs-quick-read__grid">
        @foreach($facts as $index => $fact)
            @php $semantic = \Illuminate\Support\Str::lower(data_get($fact, 'key').' '.data_get($fact, 'label')); @endphp
            <article class="lxs-fact-card">
                <span class="lxs-fact-card__icon" aria-hidden="true">
                    @if(\Illuminate\Support\Str::contains($semantic, ['form', 'dáng', 'silhouette']))
                        <svg viewBox="0 0 48 48"><path d="M18 6h12l3 8 7 26H8l7-26 3-8Z"/><path d="M18 6c1 4 11 4 12 0"/></svg>
                    @elseif(\Illuminate\Support\Str::contains($semantic, ['dài', 'length', 'mini', 'midi', 'maxi']))
                        <svg viewBox="0 0 48 48"><path d="M13 8h22M13 40h22M24 8v32"/><path d="m20 13 4-5 4 5M20 35l4 5 4-5"/></svg>
                    @elseif(\Illuminate\Support\Str::contains($semantic, ['vải', 'material', 'fabric', 'lót']))
                        <svg viewBox="0 0 48 48"><path d="M8 13 24 6l16 7-16 8L8 13Z"/><path d="m8 21 16 8 16-8M8 29l16 9 16-9"/></svg>
                    @else
                        <svg viewBox="0 0 48 48"><path d="M24 5 29 17l13 1-10 8 3 13-11-7-11 7 3-13-10-8 13-1 5-12Z"/></svg>
                    @endif
                </span>
                <div>
                    <small>{{ data_get($fact, 'label') }}</small>
                    <strong>{{ data_get($fact, 'value') }}</strong>
                </div>
            </article>
        @endforeach
    </div>
</div>
@endif
