{{-- The user's photo, or their initials when there is no photo or the file cannot be loaded --}}
@props(['user', 'size' => 'size-8', 'text' => 'text-[12.5px]'])

@php($photo = $user->profile_photo_url)

@if ($photo)
    <img
        src="{{ $photo }}"
        alt=""
        onerror="const initials = this.nextElementSibling; initials.classList.replace('hidden', 'flex'); this.remove()"
        class="{{ $size }} flex-none rounded-full object-cover"
    >
@endif

<span aria-hidden="true" @class([
    $size,
    $text,
    'flex-none items-center justify-center rounded-full bg-saldo-green-tint font-semibold text-saldo-green',
    'hidden' => $photo,
    'flex' => ! $photo,
])>{{ $user->initials }}</span>
