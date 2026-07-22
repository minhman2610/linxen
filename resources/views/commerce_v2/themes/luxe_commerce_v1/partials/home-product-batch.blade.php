@foreach((array) ($products ?? []) as $product)
    @include(
        'commerce_v2.themes.luxe_commerce_v1.partials.product-card',
        [
            'product' => $product,
            'eager' => false,
        ]
    )
@endforeach
