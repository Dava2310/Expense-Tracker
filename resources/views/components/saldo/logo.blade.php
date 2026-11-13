@props(['variant' => 'dark', 'size' => 28])

@php
    $isLight = $variant === 'light';
@endphp

<svg {{ $attributes }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 28 28" aria-hidden="true">
    <rect width="28" height="28" rx="8" fill="{{ $isLight ? '#FFFFFF' : '#16181B' }}"></rect>
    <rect x="7" y="9" width="14" height="3" rx="1.5" fill="{{ $isLight ? '#16181B' : '#FFFFFF' }}"></rect>
    <rect x="7" y="16" width="9" height="3" rx="1.5" fill="{{ $isLight ? '#0B7A55' : '#3FBF8A' }}"></rect>
</svg>
