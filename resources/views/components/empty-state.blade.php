@props(['icon' => 'book', 'title' => 'Nothing here yet', 'description' => ''])
<div {{ $attributes->merge(['class' => 'empty-state']) }}>
    <span class="empty-icon"><x-icon :name="$icon" /></span>
    <h3>{{ $title }}</h3>
    <p>{{ $description }}</p>{{ $slot }}
</div>