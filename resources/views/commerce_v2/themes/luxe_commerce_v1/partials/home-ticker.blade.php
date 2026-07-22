<section class="lxh3-ticker" aria-label="Lời nhắn từ LIN XÉN">
    <div class="lxh3-ticker__track">
        @foreach([false, true] as $duplicate)
            <div @if($duplicate) aria-hidden="true" @endif>
                <span>LIN XÉN · Váy BASIC mặc lên không cần suy nghĩ</span>
                <i aria-hidden="true"></i>
                <span>Đi làm, đi chơi · che hết khuyết điểm</span>
                <i aria-hidden="true"></i>
                <span>Tự hào sản xuất bởi LIN XÉN Việt Nam</span>
                <i aria-hidden="true"></i>
                <span>Vừa đủ dễ chịu để mặc mỗi ngày</span>
                <i aria-hidden="true"></i>
            </div>
        @endforeach
    </div>
</section>
