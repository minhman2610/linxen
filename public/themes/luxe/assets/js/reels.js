// Vertical products
const reelsVertical = new Swiper('.reels-vertical', {
    direction: 'vertical',
    slidesPerView: 1,
    resistanceRatio: 0,
    preloadImages: false,
    lazy: true,
});

// Horizontal images (init per slide)
document.querySelectorAll('.reels-images').forEach(el => {
    new Swiper(el, {
        direction: 'horizontal',
        slidesPerView: 1,
        nested: true,
    });
});

// ADD TO CART
document.querySelectorAll('.lx-btn-add-cart').forEach(btn => {
    btn.addEventListener('click', () => {
        fetch('/cart/add', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document
                    .querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                sku: btn.dataset.id,
                name: btn.dataset.name,
                price: btn.dataset.price,
                image: btn.dataset.image,
                qty: 1
            })
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                document.getElementById('lxCartCount').innerText = res.cart_count;
            }
        });
    });
});
