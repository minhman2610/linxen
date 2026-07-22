<section class="lxh3-ticker" aria-label="Thông tin mua hàng">
    <div class="lxh3-ticker__track">
        @foreach([false, true] as $duplicate)
            <div @if($duplicate) aria-hidden="true" @endif>
                <span>Ảnh sản phẩm đã duyệt</span>
                <i aria-hidden="true"></i>
                <span>Giá và tồn kho chính thức</span>
                <i aria-hidden="true"></i>
                <span>Đúng màu · đúng size · đúng SKU</span>
                <i aria-hidden="true"></i>
                <span>Giao hàng toàn quốc</span>
                <i aria-hidden="true"></i>
            </div>
        @endforeach
    </div>
</section>
