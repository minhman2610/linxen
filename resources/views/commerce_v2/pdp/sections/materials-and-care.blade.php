@php
    $materials = (array) data_get($pdp, 'product_truth.materials', []);
    $sectionItems = collect((array) data_get($materials, 'section.items', []));
    $main = collect((array) data_get($materials, 'main', []));
    $lining = collect((array) data_get($materials, 'lining', []));
    $care = collect((array) data_get($pdp, 'product_truth.care.items', []));
@endphp
<div class="lxpdp-story-block lxpdp-material-story">
    <header class="lxpdp-story-heading"><p class="lxpdp-kicker">Chạm bằng thông tin</p><h2>Chất liệu, kết cấu và cách chăm sóc</h2></header>
    <div class="lxpdp-material-story__grid">
        <article>
            <h3>Vải chính</h3>
            @forelse($main as $item)<p><strong>{{ data_get($item, 'family_name') ?: data_get($item, 'name') }}</strong><span>{{ data_get($item, 'name') }}</span></p>@empty
                @foreach($sectionItems->filter(fn ($item) => str_contains(mb_strtolower((string) data_get($item, 'label')), 'vải chính')) as $item)<p><strong>{{ data_get($item, 'value') }}</strong></p>@endforeach
            @endforelse
        </article>
        <article>
            <h3>Lớp lót</h3>
            @forelse($lining as $item)<p><strong>{{ data_get($item, 'family_name') ?: data_get($item, 'name') }}</strong><span>{{ data_get($item, 'name') }}</span></p>@empty<p>{{ data_get($materials, 'layer_label') ?: 'Chưa có dữ liệu lớp lót được xác minh.' }}</p>@endforelse
        </article>
        <article>
            <h3>Cảm giác và đặc tính</h3>
            @forelse($sectionItems->reject(fn ($item) => str_contains(mb_strtolower((string) data_get($item, 'label')), 'vải')) as $item)<p><span>{{ data_get($item, 'label') }}</span><strong>{{ data_get($item, 'value') }}</strong></p>@empty<p>Thông tin độ dày, co giãn và bề mặt đang được chuẩn hóa.</p>@endforelse
        </article>
        <article>
            <h3>Bảo quản</h3>
            @forelse($care as $item)<p>{{ data_get($item, 'value') }}</p>@empty<p>Chưa có hướng dẫn bảo quản được xác minh trong nguồn hiện tại.</p>@endforelse
        </article>
    </div>
</div>
