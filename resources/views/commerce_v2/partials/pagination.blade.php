@if(!empty($pagination['has_more']) && !empty($pagination['next_cursor']))
    @php
        $query = array_filter(array_merge(
            request()->query(),
            ['cursor' => $pagination['next_cursor']]
        ));
    @endphp

    <div class="lxv2-load-more">
        <a class="lxv2-button lxv2-button--outline" href="{{ request()->url() . '?' . http_build_query($query) }}">
            Xem thêm sản phẩm
        </a>
    </div>
@endif
