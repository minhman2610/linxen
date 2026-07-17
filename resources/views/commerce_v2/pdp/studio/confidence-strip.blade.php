@php $policies = (array) data_get($pdp, 'policies', []); @endphp
<div class="lxs-shell lxs-confidence" data-lxs-reveal>
    <article>
        <svg viewBox="0 0 32 32" aria-hidden="true"><path d="M5 9h22v14H5zM9 23v3M23 23v3M11 16h10"/></svg>
        <div><strong>COD khi nhận hàng</strong><span>Kiểm tra đơn và thanh toán theo hướng dẫn.</span></div>
    </article>
    <article>
        <svg viewBox="0 0 32 32" aria-hidden="true"><path d="M8 8h16v16H8zM4 16a12 12 0 0 1 20-9M28 16a12 12 0 0 1-20 9"/></svg>
        <div><strong>{{ data_get($policies, 'exchange.label', 'Hỗ trợ đổi size') }}</strong><span>{{ data_get($policies, 'exchange.message') }}</span></div>
    </article>
    <article>
        <svg viewBox="0 0 32 32" aria-hidden="true"><path d="M5 23h22M8 23V12l8-5 8 5v11M12 16h8"/></svg>
        <div><strong>{{ data_get($policies, 'shipping.label', 'Giao hàng toàn quốc') }}</strong><span>{{ data_get($policies, 'shipping.message') }}</span></div>
    </article>
    <article>
        <svg viewBox="0 0 32 32" aria-hidden="true"><circle cx="16" cy="16" r="11"/><path d="M12 13a4 4 0 1 1 6 3c-2 1-2 2-2 4M16 24h.01"/></svg>
        <div><strong>Hỗ trợ chọn size</strong><span>Mở Size Studio hoặc gửi số đo để được gợi ý.</span></div>
    </article>
</div>
