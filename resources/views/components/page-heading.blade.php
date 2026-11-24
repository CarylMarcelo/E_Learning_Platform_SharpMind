@props(['title', 'description' => ''])
<div {{ $attributes->merge(['class' => 'page-heading']) }}>
    <div>
        <h1>{{ $title }}</h1>@if($description)<p>{{ $description }}</p>@endif
    </div>
    <div class="cluster">{{ $slot }}</div>
</div>