@props(['name', 'filled' => false])
<svg {{ $attributes->merge(['class' => 'icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
    @case('dashboard')
    <rect x="3" y="3" width="7" height="7" rx="1.6" />
    <rect x="14" y="3" width="7" height="7" rx="1.6" />
    <rect x="3" y="14" width="7" height="7" rx="1.6" />
    <rect x="14" y="14" width="7" height="7" rx="1.6" /> @break
    @case('members')
    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m20 0v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
    <circle cx="9" cy="7" r="4" /> @break
    @case('user-check')
    <path d="M15 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2m15-10 2 2 4-4" />
    <circle cx="8" cy="7" r="4" /> @break
    @case('user-plus')
    <path d="M15 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2m18-13v6m-3-3h6" />
    <circle cx="8" cy="7" r="4" /> @break
    @case('transactions')
    <rect x="3" y="5" width="18" height="14" rx="3" />
    <path d="M3 10h18m-13 5h3m4 0h1" /> @break
    @case('categories')
    <rect x="3" y="3" width="7" height="7" rx="2" />
    <rect x="14" y="3" width="7" height="7" rx="2" />
    <rect x="3" y="14" width="7" height="7" rx="2" />
    <path d="M14 17.5h7m-3.5-3.5v7" /> @break
    @case('levels')
    <path d="m12 3 9 5-9 5-9-5 9-5Zm-9 9 9 5 9-5M3 16l9 5 9-5" /> @break
    @case('book')
    <path d="M12 6C9 3 5 3 2 4v15c4-1 7-1 10 2m0-15c3-3 7-3 10-2v15c-4-1-7-1-10 2V6Z" /> @break
    @case('cap')
    <path d="m2 9 10-5 10 5-10 5-10-5Zm4 2v6c4 3 8 3 12 0v-6m4-2v8" /> @break
    @case('search')
    <circle cx="10.8" cy="10.8" r="7" />
    <path d="m16 16 4.5 4.5" /> @break
    @case('bell')
    <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9m-8 12a2 2 0 0 0 4 0" /> @break
    @case('calendar')
    <rect x="3" y="5" width="18" height="16" rx="3" />
    <path d="M16 3v4M8 3v4M3 11h18m-13 5h2m4 0h2" /> @break
    @case('clock')
    <circle cx="12" cy="12" r="9" />
    <path d="M12 7v5l3 2" /> @break
    @case('map-pin')
    <path d="M20 10c0 6-8 11-8 11S4 16 4 10a8 8 0 1 1 16 0Z" /><circle cx="12" cy="10" r="2.5" /> @break
    @case('cloud-sun')
    <path d="M8 3v2M3 8H1m3-5L2.5 1.5M13 3l1.5-1.5M4 13l-1.5 1.5" /><circle cx="8" cy="8" r="4" /><path d="M8 20a4 4 0 1 1 1-7.9 5 5 0 0 1 9.7 1.4A3.3 3.3 0 1 1 19 20H8Z" /> @break
    @case('arrow-right')
    <path d="M4 12h16m-6-6 6 6-6 6" /> @break
    @case('arrow-up-right')
    <path d="M6 18 18 6M6 6h12v12" /> @break
    @case('chevron-right')
    <path d="m9 5 7 7-7 7" /> @break
    @case('chevron-left')
    <path d="m15 5-7 7 7 7" /> @break
    @case('chevron-down')
    <path d="m6 9 6 6 6-6" /> @break
    @case('plus')
    <path d="M12 5v14M5 12h14" /> @break
    @case('logout')
    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4m7 14 5-5-5-5M21 12H9" /> @break
    @case('sparkles')
    <path d="m12 3 2.5 6.5L21 12l-6.5 2.5L12 21l-2.5-6.5L3 12l6.5-2.5L12 3Zm8 0v4m-2-2h4" /> @break
    @case('check')
    <path d="m5 12 4 4L19 6" /> @break
    @case('check-circle')
    <circle cx="12" cy="12" r="9" />
    <path d="m8 12 3 3 5-6" /> @break
    @case('close')
    <path d="m6 6 12 12M6 18 18 6" /> @break
    @case('mail')
    <rect x="3" y="5" width="18" height="14" rx="3" />
    <path d="m3 7 9 6 9-6" /> @break
    @case('lock')
    <rect x="4" y="10" width="16" height="11" rx="3" />
    <path d="M8 10V7a4 4 0 1 1 8 0v3m-4 5v2" /> @break
    @case('eye')
    <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z" />
    <circle cx="12" cy="12" r="3" /> @break
    @case('edit')
    <path d="m16 3 5 5-12 12-6 1 1-6L16 3Zm-2 2 5 5" /> @break
    @case('trash')
    <path d="M3 6h18M9 6V3h6v3m-10 0 1 15h12l1-15M10 10v7m4-7v7" /> @break
    @case('download')
    <path d="M12 3v12m-5-5 5 5 5-5M4 16v5h16v-5" /> @break
    @case('filter')
    <path d="M4 6h16M7 12h10m-7 6h4" /> @break
    @case('menu')
    <path d="M4 6h16M4 12h16M4 18h16" /> @break
    @case('shield')
    <path d="m12 3 9 4v5c0 5-9 9-9 9s-9-4-9-9V7l9-4Z" />
    <path d="m8 12 3 3 5-6" /> @break
    @case('wallet')
    <path d="M21 8V6a2 2 0 0 0-2-2H5a3 3 0 0 0 0 6h16v10H5a3 3 0 0 1-3-3V7m19 5h-5v5h5m-2-2.5h.01" /> @break
    @case('info')
    <circle cx="12" cy="12" r="9" />
    <path d="M12 11v6m0-10h.01" /> @break
    @case('refresh')
    <path d="M20 7v5h-5M4 17v-5h5m10.5-4A8 8 0 0 0 6 5m-1.5 11A8 8 0 0 0 18 19" /> @break
    @case('print')
    <path d="M6 8V3h12v5M6 17H3V8h18v9h-3M6 14h12v7H6v-7Zm11-3h.01" /> @break
    @default
    <circle cx="12" cy="12" r="8" />
    @endswitch
</svg>