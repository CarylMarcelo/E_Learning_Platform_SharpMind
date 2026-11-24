@props(['status'])
<span {{ $attributes->merge(['class' => 'badge badge-'.strtolower($status)]) }}>{{ $status }}</span>