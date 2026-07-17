@php $advisor = (array) data_get($pdp, 'fit.advisor', []); @endphp
<dialog class="lxpdp-advisor" data-lxpdp-size-advisor>
    <form method="dialog" class="lxpdp-advisor__close-form"><button type="submit" aria-label="Đóng tư vấn size">×</button></form>
    <div class="lxpdp-advisor__content">
        <p class="lxpdp-kicker">Tư vấn kích thước</p>
        <h2>Nhập số đo của bạn</h2>
        <p>Chiều cao và cân nặng giúp tham khảo. Để đưa gợi ý, hệ thống cần đủ vòng ngực, eo và hông.</p>
        <form data-lxpdp-size-form>
            <div class="lxpdp-advisor__grid">
                <label><span>Chiều cao</span><input type="number" name="height_cm" min="130" max="200" inputmode="decimal"><small>cm</small></label>
                <label><span>Cân nặng</span><input type="number" name="weight_kg" min="30" max="150" inputmode="decimal"><small>kg</small></label>
                <label><span>Vòng ngực</span><input type="number" name="bust_cm" min="45" max="160" inputmode="decimal"><small>cm</small></label>
                <label><span>Vòng eo</span><input type="number" name="waist_cm" min="45" max="160" inputmode="decimal"><small>cm</small></label>
                <label><span>Vòng hông</span><input type="number" name="hip_cm" min="45" max="180" inputmode="decimal"><small>cm</small></label>
                <label><span>Cách mặc mong muốn</span><select name="fit_preference"><option value="fitted">Ôm vừa</option><option value="regular" selected>Vừa vặn</option><option value="relaxed">Thoải mái</option></select></label>
            </div>
            <div class="lxpdp-advisor__actions"><button type="submit" class="lxpdp-primary-button">Kiểm tra size</button><button type="button" class="lxpdp-text-button" data-lxpdp-size-clear>Xóa số đo đã lưu</button></div>
        </form>
        <div class="lxpdp-advisor__result" data-lxpdp-size-result aria-live="polite" hidden></div>
        <p class="lxpdp-advisor__disclaimer">{{ data_get($advisor, 'disclaimer') }}</p>
    </div>
</dialog>
