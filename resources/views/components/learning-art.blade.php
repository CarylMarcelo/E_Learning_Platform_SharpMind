@props(['id' => 'learning'])
<svg {{ $attributes->merge(['class' => 'learning-art']) }} viewBox="0 0 430 285" fill="none" aria-hidden="true">
    <defs>
        <linearGradient id="{{ $id }}-book" x1="138" y1="120" x2="290" y2="260" gradientUnits="userSpaceOnUse">
            <stop stop-color="#e5d4ff" />
            <stop offset="1" stop-color="#c5a2f0" />
        </linearGradient>
        <linearGradient id="{{ $id }}-cap" x1="164" y1="40" x2="291" y2="154" gradientUnits="userSpaceOnUse">
            <stop stop-color="#695091" />
            <stop offset="1" stop-color="#3c2a5c" />
        </linearGradient>
        <linearGradient id="{{ $id }}-white" x1="142" y1="135" x2="270" y2="220" gradientUnits="userSpaceOnUse">
            <stop stop-color="#fff" />
            <stop offset="1" stop-color="#e8ddf7" />
        </linearGradient>
        <filter id="{{ $id }}-shadow" x="0" y="0" width="200%" height="200%">
            <feDropShadow dx="0" dy="13" stdDeviation="9" flood-color="#4b2469" flood-opacity=".16" />
        </filter>
    </defs>
    <circle cx="254" cy="145" r="120" fill="white" fill-opacity=".045" />
    <circle cx="254" cy="145" r="94" stroke="white" stroke-opacity=".08" />
    <ellipse cx="247" cy="248" rx="129" ry="16" fill="#663aa8" fill-opacity=".16" />
    <g filter="url(#{{ $id }}-shadow)">
        <path d="m129 210 126-48 101 53-126 50-101-55Z" fill="#9a70d5" />
        <path d="m137 206 118-42 95 47v18l-119 46-94-51v-18Z" fill="#b491e6" />
        <path d="m142 209 90 45 109-42v13l-109 42-90-46v-12Z" fill="url(#{{ $id }}-white)" />
        <path d="m130 200 125-47 102 52-126 50-101-55Z" fill="#d9c5fa" />
        <path d="m151 177 113-41 81 42v18l-113 43-81-43v-19Z" fill="#e7d7fd" />
        <path d="m157 180 76 38 103-39v13l-103 39-76-39v-12Z" fill="url(#{{ $id }}-white)" />
        <path d="m145 171 118-44 86 46-116 45-88-47Z" fill="url(#{{ $id }}-book)" />
        <path d="m193 107 1 45c31 26 67 26 97-2v-43h-98Z" fill="#483163" />
        <path d="m193 107 49 24 49-24v43c-31 21-64 24-97 2l-1-45Z" fill="url(#{{ $id }}-cap)" />
        <path d="m149 93 93-44 101 45-100 47-94-48Z" fill="#4e386e" />
        <path d="m149 87 93-44 101 45-100 47-94-48Z" fill="url(#{{ $id }}-cap)" />
        <path d="m242 88 70 16v47" stroke="#f2c576" stroke-width="4" stroke-linecap="round" />
        <circle cx="242" cy="88" r="6" fill="#f3cd8c" />
        <path d="M312 146c-7 0-9 10-8 18h17c0-8-2-18-9-18Z" fill="#f2c576" />
        <path d="m177 215 14 7 15-5v25l-15-5-14 4v-26Z" fill="#b482e2" />
    </g>
    <g transform="rotate(-14 115 135)">
        <rect x="79" y="101" width="63" height="73" rx="12" fill="#ffffff" fill-opacity=".95" />
        <rect x="90" y="114" width="40" height="6" rx="3" fill="#dcc6f5" />
        <rect x="90" y="127" width="28" height="4" rx="2" fill="#eadcf9" />
        <circle cx="110" cy="151" r="11" fill="#eee3fc" />
        <path d="m105 151 4 4 7-8" stroke="#a174d5" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
    </g>
    <path d="m344 46 3.5 10 10 3.5-10 3.5-3.5 10-3.5-10-10-3.5 10-3.5 3.5-10Z" fill="#ffe0a1" />
    <path d="m128 48 2 6 6 2-6 2-2 6-2-6-6-2 6-2 2-6Z" fill="#e7d4ff" />
    <circle cx="370" cy="158" r="7" fill="#8cdec3" />
    <circle cx="179" cy="29" r="4" fill="#bbefd9" />
    <circle cx="104" cy="218" r="5" fill="#e9b6d7" />
    <path d="m369 99 7 9m-220 128 6 8m165-3 8-5" stroke="#e9b0d3" stroke-width="5" stroke-linecap="round" />
</svg>