@php $policies = (array) data_get($pdp, 'policies', []); @endphp
<div class="lxpdp-final-reassurance">
    <div><p class="lxpdp-kicker">Sẵn sàng lựa chọn?</p><h2>Mua với thông tin rõ ràng</h2><p>Giá, tồn kho và SKU được kiểm tra lại trước khi sản phẩm được thêm vào giỏ.</p></div>
    <div class="lxpdp-final-reassurance__policies">
        <article><strong>{{ data_get($policies, 'cod.label') }}</strong><span>Nhận hàng rồi thanh toán</span></article>
        <article><strong>{{ data_get($policies, 'shipping.label') }}</strong><span>{{ data_get($policies, 'shipping.message') }}</span></article>
        <article><strong>{{ data_get($policies, 'exchange.label') }}</strong><span>{{ data_get($policies, 'exchange.message') }}</span></article>
    </div>
    <button type="button" class="lxpdp-primary-button" data-pdp-scroll-to-purchase>Chọn màu và kích thước</button>
</div>
