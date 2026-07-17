@extends('commerce_v2.layouts.app')

@section('robots', 'noindex,nofollow')

@section('content')
<section class="lxv2-empty">
    <p class="lxv2-eyebrow">Thanh toán</p>
    <h1>Checkout đã được đơn giản hóa</h1>
    <p>
        Bước xác nhận báo giá riêng đã được ẩn.
        Giá, tồn kho và phí giao hàng được kiểm tra phía sau
        trong một lần bấm Đặt hàng.
    </p>
    <a
        class="lxv2-button"
        href="{{ route('commerce.v2.checkout.index') }}"
    >
        Quay lại thanh toán
    </a>
</section>
@endsection
