<div class="lx-announcement-bar">
    {{ $message ?? 'End of season sale — up to 50% OFF!' }}

    @if(!empty($link))
        <a href="{{ $link }}" class="lx-announcement-link">
            {{ $link_text ?? 'Xem ngay' }}
        </a>
    @endif
</div>

<style>
/* ---------------------------------------------
   Announcement Bar — LUXE Theme
----------------------------------------------*/
.lx-announcement-bar {
    width: 100%;
    background: #000;
    color: #fff;
    padding: 6px 12px;
    text-align: center;
    font-size: 12px;
    line-height: 1.4;
    font-weight: 400;
    letter-spacing: 0.3px;
}

.lx-announcement-bar a {
    color: white;
    text-decoration: underline;
    margin-left: 4px;
}

.lx-announcement-bar a:hover {
    opacity: 0.8;
}

/* Mobile optimization */
@media (max-width: 480px) {
    .lx-announcement-bar {
        font-size: 11px;
        padding: 5px 8px;
    }
}
</style>
