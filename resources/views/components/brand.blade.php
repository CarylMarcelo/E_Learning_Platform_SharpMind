@props(['href' => null, 'caption' => false])
<div {{ $attributes }}>
    <a class="brand" href="{{ $href ?? route('dashboard') }}" aria-label="SharpMind home">
        <svg class="brand-mark" viewBox="0 0 44 44" fill="none" aria-hidden="true">
            <rect width="44" height="44" rx="13" fill="currentColor" fill-opacity=".14" />
            <path d="M9 14.5c5-1 9 .5 13 4v15c-4-3.5-8-5-13-4v-15Zm26 0c-5-1-9 .5-13 4v15c4-3.5 8-5 13-4v-15Z" fill="currentColor" />
            <path d="m24 5 1.8 4.2L30 11l-4.2 1.8L24 17l-1.8-4.2L18 11l4.2-1.8L24 5Z" fill="currentColor" />
            <path d="M22 20v11" stroke="#a576d9" stroke-width="1.5" />
        </svg>
        <span class="brand-name">Sharp<span>Mind</span><span class="sr-only"> learning management</span></span>
    </a>
    @if($caption)<p class="brand-caption">LEARN. GROW. BELONG.</p>@endif
</div>