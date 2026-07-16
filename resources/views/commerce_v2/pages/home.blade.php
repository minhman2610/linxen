@extends('commerce_v2.layouts.app')

@section('content')
<section class="lxv2-hero">
    <div class="lxv2-hero__content">
        <p class="lxv2-eyebrow">LIN XÉN · STOREFRONT V2</p>
        <h1>Thiết kế giúp anh tự tin trong từng khoảnh khắc.</h1>
        <p>Giá, màu, kích thước và tồn kho được lấy trực tiếp từ hệ thống thương mại chính thức.</p>
        <div class="lxv2-actions">
            <a class="lxv2-button" href="{{ route('commerce.v2.shop') }}">Khám phá sản phẩm</a>
            <a class="lxv2-button lxv2-button--ghost" href="{{ route('commerce.v2.search') }}">Tìm theo mã hoặc tên</a>
        </div>
    </div>
    <div class="lxv2-hero__visual" aria-hidden="true">
        <span>LIN</span>
        <span>XÉN</span>
    </div>
</section>

@if(!empty($collections))
<section class="lxv2-section">
    <div class="lxv2-section__head">
        <div>
            <p class="lxv2-eyebrow">Chọn nhanh</p>
            <h2>Bộ sưu tập</h2>
        </div>
    </div>
    <div class="lxv2-collection-row">
        @foreach($collections as $collection)
            <a class="lxv2-collection-pill" href="{{ $collection['url'] }}">
                <strong>{{ $collection['name'] }}</strong>
                @if($collection['description'])
                    <small>{{ $collection['description'] }}</small>
                @endif
            </a>
        @endforeach
    </div>
</section>
@endif

<section class="lxv2-section">
    <div class="lxv2-section__head">
        <div>
            <p class="lxv2-eyebrow">Mới cập nhật</p>
            <h2>Sản phẩm nổi bật</h2>
        </div>
        <a href="{{ route('commerce.v2.shop') }}">Xem tất cả →</a>
    </div>

    <div class="lxv2-grid">
        @forelse($products as $product)
            @include('commerce_v2.partials.product-card', ['product' => $product])
        @empty
            <div class="lxv2-empty">Chưa có sản phẩm sẵn sàng hiển thị.</div>
        @endforelse
    </div>
</section>

<section class="lxv2-trust">
    <article><strong>Dữ liệu thật</strong><span>Giá và tồn kho từ ERP Commerce V2.</span></article>
    <article><strong>Ảnh đã duyệt</strong><span>Chỉ sử dụng media được phép bán hàng.</span></article>
    <article><strong>Chọn đúng SKU</strong><span>Màu và size gắn với SKU bán chính xác.</span></article>
</section>
@endsection
