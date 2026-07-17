@php
    $occasions = collect((array) data_get($pdp, 'discovery.occasion_items', []));
    $suggestions = collect((array) data_get($pdp, 'discovery.styling_suggestions', []));
@endphp
<div class="lxpdp-story-block lxpdp-styling-story">
    <header class="lxpdp-story-heading"><p class="lxpdp-kicker">Mặc theo cách của bạn</p><h2>Từ thiết kế đến khoảnh khắc sử dụng</h2></header>
    <div class="lxpdp-styling-story__content">
        <div class="lxpdp-styling-tags">
            @foreach($occasions as $item)<span><small>{{ data_get($item, 'label') }}</small>{{ data_get($item, 'value') }}</span>@endforeach
        </div>
        @if($suggestions->isNotEmpty())
            <div class="lxpdp-styling-notes">@foreach($suggestions as $item)<article><h3>{{ data_get($item, 'title') }}</h3><p>{{ data_get($item, 'description') }}</p></article>@endforeach</div>
        @else
            <p class="lxpdp-styling-placeholder">Gợi ý phối đồ có nguồn sẽ xuất hiện ở đây khi được đội ngũ LIN XÉN duyệt.</p>
        @endif
    </div>
</div>
