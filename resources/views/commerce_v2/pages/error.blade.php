@extends('commerce_v2.layouts.app')

@section('robots', 'noindex,nofollow')

@section('content')
<section class="lxv2-error">
    <span>{{ $status }}</span>
    <p class="lxv2-eyebrow">{{ $errorCode }}</p>
    <h1>{{ $status === 404 ? 'Không tìm thấy sản phẩm' : 'Hệ thống đang cập nhật' }}</h1>
    <p>{{ $message }}</p>
    @if($requestId)
        <small>Mã kiểm tra: {{ $requestId }}</small>
    @endif
    <div class="lxv2-actions">
        <a class="lxv2-button" href="{{ route('commerce.v2.home') }}">Về trang chủ</a>
        <a class="lxv2-button lxv2-button--outline" href="{{ route('commerce.v2.shop') }}">Xem sản phẩm</a>
    </div>
</section>
@endsection
