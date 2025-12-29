@extends('storefront.luxe.layouts.app')

@section('content')

<div class="lx-account-container lx-order-detail">

    {{-- HEADER --}}
    <div class="lx-account-header">
        <a href="{{ route('linxen.account.orders') }}" class="lx-back-link">
            ← Quay lại danh sách đơn
        </a>

        <div class="lx-order-head">
            <h2>Đơn hàng #{{ $order->code }}</h2>

            <span class="lx-order-status status-{{ $order->status['code'] ?? '' }}">
                {{ $order->status['text'] ?? 'Đang xử lý' }}
            </span>
        </div>
    </div>

    {{-- PRODUCTS --}}
    <div class="lx-order-card">
        <h3 class="lx-card-title">Sản phẩm</h3>

        <div class="lx-order-items">
            @foreach($order->items as $item)
                <div class="lx-order-item">

                    {{-- IMAGE --}}
                    <div class="lx-item-image">
                        <img src="{{ $item['image'] ?? '/themes/luxe/assets/images/placeholder.webp' }}"
                             alt="{{ $item['name'] }}"
                             onclick="openImageViewer(this.src)">
                    </div>

                    {{-- INFO --}}
                    <div class="lx-item-info">
                        <div class="lx-item-name">{{ $item['name'] }}</div>

                        <div class="lx-item-meta">
                            SL {{ $item['qty'] }}
                            × {{ number_format($item['price']) }}đ
                        </div>
                    </div>

                    {{-- TOTAL --}}
                    <div class="lx-item-total">
                        {{ number_format($item['total']) }}đ
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- SHIPPING --}}
    @if(!empty($order->delivery))
        <div class="lx-order-card">
            <h3 class="lx-card-title">Thông tin giao hàng</h3>

            <div class="lx-order-shipping">
                <div><strong>Đơn vị:</strong> {{ $order->delivery['partner'] }}</div>
                <div><strong>Mã vận đơn:</strong> {{ $order->delivery['code'] }}</div>
                <div><strong>Địa chỉ:</strong> {{ $order->delivery['address'] }}</div>
                <div><strong>Cập nhật:</strong> {{ $order->delivery['updated_at'] }}</div>
            </div>
        </div>
    @endif

    {{-- SUMMARY --}}
    <div class="lx-order-card lx-order-summary">
        <div class="lx-summary-row">
            <span>Tạm tính</span>
            <span>{{ number_format($order->summary['total']) }}đ</span>
        </div>

        <div class="lx-summary-row">
            <span>Giảm giá</span>
            <span>-{{ number_format($order->summary['discount']) }}đ</span>
        </div>

        <div class="lx-summary-row total">
            <span>Tổng thanh toán</span>
            <strong>{{ number_format($order->summary['total_payment']) }}đ</strong>
        </div>
    </div>

</div>

{{-- IMAGE VIEWER --}}
<div class="lx-image-viewer" id="lxImageViewer" onclick="closeImageViewer()">
    <img id="lxImageViewerImg">
</div>

@endsection
<script>
function openImageViewer(src) {
    const viewer = document.getElementById('lxImageViewer');
    const img    = document.getElementById('lxImageViewerImg');

    img.src = src;
    viewer.style.display = 'flex';
}

function closeImageViewer() {
    document.getElementById('lxImageViewer').style.display = 'none';
}
</script>
