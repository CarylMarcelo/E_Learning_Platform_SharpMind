@props(['level' => null])
@php
    $levelTone = match (strtolower(trim($level?->name ?? ''))) {
        'basic' => 'blue',
        'silver' => 'green',
        'gold' => 'gold',
        'platinum' => 'purple',
        default => ['blue', 'green', 'gold', 'purple'][((int) ($level?->id ?? 0)) % 4],
    };
@endphp
<span {{ $attributes->merge(['class' => $level ? 'badge level-badge level-badge-'.$levelTone : 'tag']) }}>{{ $level?->name ?? '—' }}</span>
