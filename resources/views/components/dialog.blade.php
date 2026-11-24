@props(['id', 'title', 'description' => ''])
<dialog id="{{ $id }}" {{ $attributes->merge(['class' => 'dialog']) }} aria-labelledby="{{ $id }}-title">
    <div class="dialog-heading">
        <div>
            <h2 id="{{ $id }}-title" data-dialog-title>{{ $title }}</h2>
            @if($description)<p>{{ $description }}</p>@endif
        </div><button class="icon-button" type="button" data-close-dialog aria-label="Close dialog"><x-icon name="close" /></button>
    </div>
    {{ $slot }}
</dialog>