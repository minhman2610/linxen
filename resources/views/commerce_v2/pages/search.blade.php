@extends('commerce_v2.layouts.app')

@section('content')
<section class="lxv2-page-head lxv2-search-head">
    <p class="lxv2-eyebrow">Tìm kiếm</p>
    <h1>Tìm thiết kế phù hợp</h1>
    <form class="lxv2-search-form" method="get" action="{{ route('commerce.v2.search') }}">
        <input
            name="q"
            value="{{ $query }}"
            placeholder="Tên váy, mã RS, SKU hoặc màu..."
            autofocus
            autocomplete="off"
        >
        <button class="lxv2-button" type="submit">Tìm sản phẩm</button>
    </form>
    @if(!empty($validationMessage))
        <p class="lxv2-form-message">{{ $validationMessage }}</p>
    @elseif($query !== '')
        <p>Kết quả cho “{{ $query }}”</p>
    @endif
</section>

@if($query !== '')
<section class="lxv2-section lxv2-section--flush">
    <div class="lxv2-grid">
        @forelse($products as $product)
            @include('commerce_v2.partials.product-card', ['product' => $product])
        @empty
            <div class="lxv2-empty">
                Không tìm thấy sản phẩm phù hợp. Hãy thử mã RS, SKU hoặc từ khóa khác.
            </div>
        @endforelse
    </div>

    @include('commerce_v2.partials.pagination', ['pagination' => $pagination ?? []])
</section>
@endif
@endsection
