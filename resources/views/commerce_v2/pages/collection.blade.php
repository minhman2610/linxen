@extends('commerce_v2.layouts.app')

@section('content')
<section class="lxv2-page-head lxv2-collection-head" @if($collection['hero_image']) style="--hero-image:url('{{ $collection['hero_image'] }}')" @endif>
    <p class="lxv2-eyebrow">Bộ sưu tập</p>
    <h1>{{ $collection['name'] }}</h1>
    @if($collection['description'])
        <p>{{ $collection['description'] }}</p>
    @endif
</section>

<section class="lxv2-section lxv2-section--flush">
    <div class="lxv2-grid">
        @forelse($products as $product)
            @include('commerce_v2.partials.product-card', ['product' => $product])
        @empty
            <div class="lxv2-empty">Bộ sưu tập này chưa có sản phẩm sẵn sàng bán.</div>
        @endforelse
    </div>

    @include('commerce_v2.partials.pagination', ['pagination' => $pagination ?? []])
</section>
@endsection
