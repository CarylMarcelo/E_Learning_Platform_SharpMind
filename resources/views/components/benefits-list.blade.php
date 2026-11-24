@props(['items' => []])
<ul {{ $attributes->merge(['class' => 'benefits-list']) }}>
    @foreach($items as $item)
        <li><x-icon name="check-circle" /><span>{{ $item }}</span></li>
    @endforeach
</ul>
