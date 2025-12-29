@extends('storefront.luxe.layouts.app')

@section('content')
<section class="lx-order-error">

    <h1>Không thể hiển thị đơn hàng</h1>

    <p class="lx-error-message">
        {{ $message }}
    </p>

    <div class="lx-error-actions">
        <a href="{{ route('linxen.account.orders') }}" class="lx-btn-secondary">
            ← Quay lại danh sách đơn hàng
        </a>
    </div>

</section>
@endsection
