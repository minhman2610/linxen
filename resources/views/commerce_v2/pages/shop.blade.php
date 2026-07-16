@extends('commerce_v2.layouts.app')

@section('content')
<section class="lxv2-page-head">
    <p class="lxv2-eyebrow">Danh mục</p>
    <h1>Sản phẩm LIN XÉN</h1>
    <p>Khám phá các thiết kế đang sẵn sàng theo giá và tồn kho hiện tại.</p>
</section>

<section class="lxv2-section lxv2-section--flush">
    <div class="lxv2-grid">
        @forelse($products as $product)
            @include('commerce_v2.partials.product-card', ['product' => $product])
        @empty
            <div class="lxv2-empty">Chưa tìm thấy sản phẩm sẵn sàng bán.</div>
        @endforelse
    </div>

    @include('commerce_v2.partials.pagination', ['pagination' => $pagination ?? []])
</section>
@endsection
