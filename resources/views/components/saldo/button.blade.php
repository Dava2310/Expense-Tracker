@props(['variant' => 'dark', 'size' => 'md'])

@php
    $variantClasses = match ($variant) {
        'ghost' => 'bg-transparent text-saldo-ink hover:bg-saldo-ghost-hover',
        'light' => 'bg-white text-saldo-ink hover:bg-saldo-ghost-hover',
        default => 'bg-saldo-ink text-white hover:bg-saldo-ink-hover',
    };

    $sizeClasses = match ($size) {
        'lg' => 'h-13 gap-2.5 rounded-xl px-6 text-base',
        default => 'h-11 rounded-[11px] px-4 text-[14.5px]',
    };
@endphp

<a {{ $attributes->class([
    'inline-flex items-center font-medium no-underline transition-colors duration-150',
    'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-saldo-ink',
    $variantClasses,
    $sizeClasses,
]) }}>
    {{ $slot }}
</a>
