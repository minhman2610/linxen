@php
    $identity = (array) data_get($pdp, 'identity', []);
    $description = trim((string) data_get($identity, 'description'));
    $shortName = (string) (data_get($identity, 'short_name') ?: data_get($identity, 'name'));
@endphp

<div class="lxa-manifesto" data-lxa-reveal>
    <div class="lxa-manifesto__number" aria-hidden="true">01</div>
    <div class="lxa-manifesto__copy">
        <p class="lxa-kicker lxa-kicker--light">The {{ $shortName }} edit</p>
        <h2>Đường cắt rõ ràng.<br>Chi tiết vừa đủ.<br>Một phom dáng để nhớ.</h2>
        @if($description !== '')
            <p>{{ $description }}</p>
        @endif
    </div>
</div>
